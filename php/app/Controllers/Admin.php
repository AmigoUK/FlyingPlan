<?php

namespace App\Controllers;

use App\Models\FlightPlanModel;
use App\Models\WaypointModel;
use App\Models\PoiModel;
use App\Models\SharedLinkModel;
use App\Models\OrderActivityModel;
use App\Services\DroneProfiles;
use App\Services\GsdCalculator;
use App\Services\MissionPatterns;
use App\Services\FacadeScanner;
use App\Services\GridGenerator;
use App\Services\ObliqueGrid;
use App\Services\CoverageAnalyzer;
use App\Services\PhotogrammetryEstimator;
use App\Services\TerrainFollower;
use App\Services\TerrainMesh;
use App\Services\Elevation;
use App\Services\Weather;
use App\Services\Airspace;
use App\Services\KmzGenerator;
use App\Services\KmzParser;
use App\Services\ExportFormats;
use App\Services\LitchiExport;
use App\Services\PhotoPositions;

class Admin extends BaseController
{
    // ── Dashboard & Detail ──────────────────────────────────────

    public function dashboard()
    {
        $db = \Config\Database::connect();
        $builder = $db->table('flight_plans');

        $statusFilter = $this->request->getGet('status');
        if ($statusFilter) $builder->where('status', $statusFilter);

        $jobTypeFilter = $this->request->getGet('job_type');
        if ($jobTypeFilter) $builder->where('job_type', $jobTypeFilter);

        $search = $this->request->getGet('q');
        if ($search) {
            $builder->groupStart()
                ->like('customer_name', $search)
                ->orLike('reference', $search)
                ->orLike('customer_email', $search)
                ->orLike('customer_company', $search)
            ->groupEnd();
        }

        $plans = $builder->orderBy('created_at', 'DESC')->get()->getResult();
        $pilots = $db->table('users')->where('role', 'pilot')->orderBy('display_name')->get()->getResult();

        // Attach order to each plan
        foreach ($plans as &$fp) {
            $fp->order = $db->table('orders')->where('flight_plan_id', $fp->id)->get()->getRow();
        }

        return view('admin/dashboard', [
            'plans'         => $plans,
            'pilots'        => $pilots,
            'status_filter' => $statusFilter,
            'job_type_filter' => $jobTypeFilter,
            'search'        => $search,
        ]);
    }

    public function detail($planId)
    {
        $db = \Config\Database::connect();
        $fp = $db->table('flight_plans')->where('id', $planId)->get()->getRow();
        if (!$fp) throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();

        $waypoints = $db->table('waypoints')->where('flight_plan_id', $planId)->orderBy('`index`')->get()->getResult();
        $pois = $db->table('pois')->where('flight_plan_id', $planId)->orderBy('sort_order')->get()->getResult();
        $order = $db->table('orders')->where('flight_plan_id', $planId)->get()->getRow();
        $pilots = $db->table('users')->where('role', 'pilot')->orderBy('display_name')->get()->getResult();
        $sharedLinks = $db->table('shared_links')->where('flight_plan_id', $planId)->orderBy('created_at', 'DESC')->get()->getResult();

        return view('admin/detail', [
            'flight_plan'    => $fp,
            'waypoints'      => $waypoints,
            'pois'           => $pois,
            'order'          => $order,
            'pilots'         => $pilots,
            'shared_links'   => $sharedLinks,
            'waypoints_json' => json_encode(array_map(fn($w) => WaypointModel::toArray($w), $waypoints)),
            'pois_json'      => json_encode($pois),
            'drone_choices'  => DroneProfiles::getChoices(),
            'drone_profiles' => DroneProfiles::PROFILES,
        ]);
    }

    // ── Waypoint Management ─────────────────────────────────────

    public function saveWaypoints($planId)
    {
        $fp = $this->getFp($planId);
        $data = $this->request->getJSON(true);

        if (!is_array($data)) {
            return $this->response->setJSON(['success' => false, 'error' => 'Invalid data'])->setStatusCode(400);
        }

        $this->replaceWaypoints($planId, $data);
        return $this->response->setJSON(['success' => true, 'count' => count($data)]);
    }

    // ── Status & Notes ──────────────────────────────────────────

    public function updateStatus($planId)
    {
        $fp = $this->getFp($planId);
        $data = $this->request->getJSON(true);
        $newStatus = $data['status'] ?? '';

        if (!in_array($newStatus, FlightPlanModel::STATUSES)) {
            return $this->response->setJSON(['success' => false, 'error' => 'Invalid status'])->setStatusCode(400);
        }

        (new FlightPlanModel())->update($planId, ['status' => $newStatus]);
        return $this->response->setJSON(['success' => true, 'status' => $newStatus]);
    }

    public function saveNotes($planId)
    {
        $fp = $this->getFp($planId);
        $data = $this->request->getJSON(true);

        (new FlightPlanModel())->update($planId, ['admin_notes' => $data['notes'] ?? '']);
        return $this->response->setJSON(['success' => true]);
    }

    // ── GSD Calculator ──────────────────────────────────────────

    public function calculateGsd($planId)
    {
        $fp = $this->getFp($planId);
        $data = $this->request->getJSON(true);

        $result = GsdCalculator::calculateGsd(
            $fp->drone_model ?? 'mini_4_pro',
            (float) ($data['altitude_m'] ?? 30),
            (int) ($data['overlap_pct'] ?? 70),
            $fp->estimated_area_sqm ? (float) $fp->estimated_area_sqm : null
        );

        return $this->response->setJSON($result);
    }

    // ── Pattern Generators ──────────────────────────────────────

    public function generatePattern($planId)
    {
        $fp = $this->getFp($planId);
        $data = $this->request->getJSON(true);
        $type = $data['type'] ?? '';
        $config = $data['config'] ?? [];

        $centerLat = $fp->location_lat ?? 0;
        $centerLng = $fp->location_lng ?? 0;

        $waypoints = match ($type) {
            'orbit' => MissionPatterns::generateOrbit(
                $config['center_lat'] ?? $centerLat, $config['center_lng'] ?? $centerLng,
                (float) ($config['radius_m'] ?? 30), (float) ($config['altitude_m'] ?? 30),
                (int) ($config['num_points'] ?? 12), (float) ($config['speed_ms'] ?? 5),
                $config['direction'] ?? 'cw', (float) ($config['gimbal_pitch'] ?? -45),
                $config['action_type'] ?? 'takePhoto'
            ),
            'spiral' => MissionPatterns::generateSpiral(
                $config['center_lat'] ?? $centerLat, $config['center_lng'] ?? $centerLng,
                (float) ($config['radius_m'] ?? 30), (float) ($config['start_altitude_m'] ?? 20),
                (float) ($config['end_altitude_m'] ?? 60), (int) ($config['num_revolutions'] ?? 3),
                (int) ($config['points_per_rev'] ?? 12), (float) ($config['speed_ms'] ?? 4),
                $config['direction'] ?? 'cw', (float) ($config['gimbal_pitch'] ?? -45)
            ),
            'cable_cam' => MissionPatterns::generateCableCam(
                (float) ($config['start_lat'] ?? $centerLat), (float) ($config['start_lng'] ?? $centerLng),
                (float) ($config['end_lat'] ?? $centerLat + 0.001), (float) ($config['end_lng'] ?? $centerLng),
                (float) ($config['altitude_m'] ?? 30), (int) ($config['num_points'] ?? 10),
                (float) ($config['speed_ms'] ?? 3), (float) ($config['gimbal_pitch'] ?? -30)
            ),
            default => null,
        };

        if ($waypoints === null) {
            return $this->response->setJSON(['success' => false, 'error' => "Unknown pattern type: {$type}"])->setStatusCode(400);
        }

        $this->replaceWaypoints($planId, $waypoints);
        return $this->response->setJSON(['success' => true, 'count' => count($waypoints), 'waypoints' => $waypoints]);
    }

    // ── Grid Generators ─────────────────────────────────────────

    public function generateGrid($planId)
    {
        $fp = $this->getFp($planId);
        $data = $this->request->getJSON(true);
        $polygon = $this->resolvePolygon($data, $fp);

        if (!$polygon || count($polygon) < 3) {
            return $this->response->setJSON(['success' => false, 'error' => 'No polygon defined'])->setStatusCode(400);
        }

        $waypoints = GridGenerator::generateGrid($polygon, $data['config'] ?? $data);
        $this->replaceWaypoints($planId, $waypoints);
        return $this->response->setJSON(['success' => true, 'count' => count($waypoints), 'waypoints' => $waypoints]);
    }

    public function generateObliqueGrid($planId)
    {
        $fp = $this->getFp($planId);
        $data = $this->request->getJSON(true);
        $polygon = $this->resolvePolygon($data, $fp);

        if (!$polygon || count($polygon) < 3) {
            return $this->response->setJSON(['success' => false, 'error' => 'No polygon defined'])->setStatusCode(400);
        }

        $waypoints = ObliqueGrid::generateObliqueGrid($polygon, $data['config'] ?? $data);
        $this->replaceWaypoints($planId, $waypoints);
        return $this->response->setJSON(['success' => true, 'count' => count($waypoints), 'waypoints' => $waypoints]);
    }

    public function generateFacadeScan($planId)
    {
        $fp = $this->getFp($planId);
        $data = $this->request->getJSON(true);
        $mode = $data['mode'] ?? 'single';
        $config = $data['config'] ?? $data;

        if ($mode === 'multi' || $mode === 'multi_face') {
            $polygon = $this->resolvePolygon($data, $fp);
            if (!$polygon || count($polygon) < 2) {
                return $this->response->setJSON(['success' => false, 'error' => 'No polygon defined'])->setStatusCode(400);
            }
            $waypoints = FacadeScanner::generateMultiFaceScan($polygon, $config);
        } else {
            $faceStart = $data['face_start'] ?? null;
            $faceEnd = $data['face_end'] ?? null;
            if (!$faceStart || !$faceEnd) {
                return $this->response->setJSON(['success' => false, 'error' => 'Face start and end required'])->setStatusCode(400);
            }
            $waypoints = FacadeScanner::generateFacadeScan($faceStart, $faceEnd, $config);
        }

        $this->replaceWaypoints($planId, $waypoints);
        return $this->response->setJSON(['success' => true, 'count' => count($waypoints), 'waypoints' => $waypoints]);
    }

    public function generateMultiOrbit($planId)
    {
        $fp = $this->getFp($planId);
        $data = $this->request->getJSON(true);
        $config = $data['config'] ?? $data;
        $config['center_lat'] = $config['center_lat'] ?? $fp->location_lat;
        $config['center_lng'] = $config['center_lng'] ?? $fp->location_lng;

        $waypoints = FacadeScanner::generateMultiAltitudeOrbit(
            (float) $config['center_lat'], (float) $config['center_lng'], $config
        );

        $this->replaceWaypoints($planId, $waypoints);
        return $this->response->setJSON(['success' => true, 'count' => count($waypoints), 'waypoints' => $waypoints]);
    }

    // ── Airspace, Weather, Terrain, Elevation ───────────────────

    public function getAirspace($planId)
    {
        $fp = $this->getFp($planId);
        $wps = $this->getWaypointsData($planId);

        $geojson = Airspace::getAirspaceGeojson();
        $violations = Airspace::checkRouteAirspace($wps, $geojson);

        return $this->response->setJSON(['geojson' => $geojson, 'violations' => $violations]);
    }

    public function getWeather($planId)
    {
        $fp = $this->getFp($planId);
        if (empty($fp->location_lat) || empty($fp->location_lng)) {
            return $this->response->setJSON(['error' => 'No location set']);
        }

        $weather = Weather::getWeather((float) $fp->location_lat, (float) $fp->location_lng);
        $profile = DroneProfiles::getProfile($fp->drone_model ?? 'mini_4_pro');
        $warnings = Weather::checkDroneWarnings($weather['current'], $profile);
        $weather['drone_warnings'] = $warnings;

        return $this->response->setJSON($weather);
    }

    public function terrainFollow($planId)
    {
        $fp = $this->getFp($planId);
        $data = $this->request->getJSON(true);
        $wps = $this->getWaypointsData($planId);

        $adjusted = TerrainFollower::applyTerrainFollowing($wps, (float) ($data['target_agl_m'] ?? 30));
        $this->replaceWaypoints($planId, $adjusted);
        return $this->response->setJSON(['success' => true, 'count' => count($adjusted), 'waypoints' => $adjusted]);
    }

    public function getElevation($planId)
    {
        $wps = $this->getWaypointsData($planId);
        $enriched = Elevation::getWaypointElevations($wps);
        return $this->response->setJSON(['success' => true, 'waypoints' => $enriched]);
    }

    public function terrainMesh($planId)
    {
        $wps = $this->getWaypointsData($planId);
        $mesh = TerrainMesh::getTerrainMesh($wps);
        return $this->response->setJSON($mesh);
    }

    // ── Coverage & Quality ──────────────────────────────────────

    public function coverageAnalysis($planId)
    {
        $fp = $this->getFp($planId);
        $wps = $this->getWaypointsData($planId);
        $result = CoverageAnalyzer::computeCoverageGrid($wps, $fp->drone_model ?? 'mini_4_pro');
        return $this->response->setJSON(array_merge(['success' => true], $result));
    }

    public function qualityReport($planId)
    {
        $fp = $this->getFp($planId);
        $wps = $this->getWaypointsData($planId);
        $report = PhotogrammetryEstimator::generateQualityReport($wps, $fp->drone_model ?? 'mini_4_pro');
        return $this->response->setJSON(array_merge(['success' => true], $report));
    }

    // ── Import ──────────────────────────────────────────────────

    public function importKmz($planId)
    {
        $fp = $this->getFp($planId);
        $file = $this->request->getFile('kmz_file');

        if (!$file || !$file->isValid()) {
            return $this->response->setJSON(['success' => false, 'error' => 'No file uploaded'])->setStatusCode(400);
        }

        $result = KmzParser::parseKmz(file_get_contents($file->getTempName()));

        if ($result['error']) {
            return $this->response->setJSON(['success' => false, 'error' => $result['error']])->setStatusCode(400);
        }
        if (empty($result['waypoints'])) {
            return $this->response->setJSON(['success' => false, 'error' => 'No waypoints found in KMZ'])->setStatusCode(400);
        }

        $this->replaceWaypoints($planId, $result['waypoints']);

        if ($result['drone_model']) {
            (new FlightPlanModel())->update($planId, ['drone_model' => $result['drone_model']]);
        }

        return $this->response->setJSON([
            'success'     => true,
            'count'       => count($result['waypoints']),
            'drone_model' => $result['drone_model'],
            'waypoints'   => $result['waypoints'],
        ]);
    }

    // ── Drone Model & Duplicate ─────────────────────────────────

    public function savePolygon($planId)
    {
        $fp = $this->getFp($planId);
        $data = $this->request->getJSON(true);
        $polygon = $data['polygon'] ?? null;

        $update = ['area_polygon' => $polygon];
        if ($polygon) {
            $coords = json_decode($polygon, true);
            if (is_array($coords) && count($coords) >= 3) {
                $update['estimated_area_sqm'] = $this->calculatePolygonArea($coords);
            }
        } else {
            $update['estimated_area_sqm'] = null;
        }

        (new FlightPlanModel())->update($planId, $update);
        return $this->response->setJSON(['success' => true]);
    }

    private function resolvePolygon(array $data, $fp): ?array
    {
        $raw = $data['polygon'] ?? null;
        if (is_string($raw)) {
            $raw = json_decode($raw, true);
        }
        if (is_array($raw) && count($raw) >= 3) {
            return $raw;
        }
        if (!empty($fp->area_polygon)) {
            return json_decode($fp->area_polygon, true);
        }
        return null;
    }

    private function calculatePolygonArea(array $coords): float
    {
        // Shoelace formula with lat/lng to metres approximation
        $n = count($coords);
        if ($n < 3) return 0;
        $area = 0;
        for ($i = 0; $i < $n; $i++) {
            $j = ($i + 1) % $n;
            $xi = $coords[$i][1] * 111320 * cos(deg2rad($coords[$i][0]));
            $yi = $coords[$i][0] * 110540;
            $xj = $coords[$j][1] * 111320 * cos(deg2rad($coords[$j][0]));
            $yj = $coords[$j][0] * 110540;
            $area += ($xi * $yj - $xj * $yi);
        }
        return abs($area / 2);
    }

    public function saveDroneModel($planId)
    {
        $fp = $this->getFp($planId);
        $data = $this->request->getJSON(true);
        $model = $data['drone_model'] ?? '';

        if (!isset(DroneProfiles::PROFILES[$model])) {
            return $this->response->setJSON(['success' => false, 'error' => 'Unknown drone model'])->setStatusCode(400);
        }

        (new FlightPlanModel())->update($planId, ['drone_model' => $model]);
        return $this->response->setJSON(['success' => true, 'drone_model' => $model]);
    }

    public function duplicate($planId)
    {
        $db = \Config\Database::connect();
        $fp = $db->table('flight_plans')->where('id', $planId)->get()->getRowArray();
        if (!$fp) throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();

        $fpModel = new FlightPlanModel();
        unset($fp['id'], $fp['created_at'], $fp['updated_at']);
        $fp['reference'] = $fpModel->generateReference();
        $fp['status'] = 'new';
        $fp['admin_notes'] = '';
        $newId = $fpModel->insert($fp);

        // Copy waypoints
        $waypoints = $db->table('waypoints')->where('flight_plan_id', $planId)->get()->getResultArray();
        foreach ($waypoints as $wp) {
            unset($wp['id']);
            $wp['flight_plan_id'] = $newId;
            $db->table('waypoints')->insert($wp);
        }

        // Copy POIs
        $pois = $db->table('pois')->where('flight_plan_id', $planId)->get()->getResultArray();
        foreach ($pois as $poi) {
            unset($poi['id']);
            $poi['flight_plan_id'] = $newId;
            $db->table('pois')->insert($poi);
        }

        return $this->response->setJSON(['success' => true, 'new_plan_id' => $newId, 'reference' => $fp['reference']]);
    }

    // ── Exports ─────────────────────────────────────────────────

    public function exportKmz($planId)
    {
        $wps = $this->getWaypointsData($planId);
        $fp = $this->getFp($planId);

        if (empty($wps)) {
            return redirect()->to(site_url('/admin/') . $planId)->with('flash_warning', 'No waypoints to export.');
        }

        $content = KmzGenerator::generateKmz($wps, $fp->reference, $fp->drone_model ?? 'mini_4_pro');
        return $this->response->setContentType('application/vnd.google-earth.kmz')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $fp->reference . '.kmz"')
            ->setBody($content);
    }

    public function exportKml($planId)
    {
        $wps = $this->getWaypointsData($planId);
        $fp = $this->getFp($planId);
        $content = ExportFormats::generateKml($wps, $fp->reference);
        return $this->response->setContentType('application/vnd.google-earth.kml+xml')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $fp->reference . '.kml"')
            ->setBody($content);
    }

    public function exportGeojson($planId)
    {
        $wps = $this->getWaypointsData($planId);
        $fp = $this->getFp($planId);
        $content = ExportFormats::generateGeojson($wps, $fp->reference);
        return $this->response->setContentType('application/geo+json')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $fp->reference . '.geojson"')
            ->setBody($content);
    }

    public function exportCsv($planId)
    {
        $wps = $this->getWaypointsData($planId);
        $fp = $this->getFp($planId);
        $content = ExportFormats::generateCsv($wps);
        return $this->response->setContentType('text/csv')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $fp->reference . '.csv"')
            ->setBody($content);
    }

    public function exportGpx($planId)
    {
        $wps = $this->getWaypointsData($planId);
        $fp = $this->getFp($planId);
        $content = ExportFormats::generateGpx($wps, $fp->reference);
        return $this->response->setContentType('application/gpx+xml')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $fp->reference . '.gpx"')
            ->setBody($content);
    }

    public function exportLitchi($planId)
    {
        $wps = $this->getWaypointsData($planId);
        $fp = $this->getFp($planId);
        if (empty($wps)) {
            return redirect()->to(site_url('/admin/') . $planId)->with('flash_warning', 'No waypoints to export.');
        }
        $content = LitchiExport::generateLitchiCsv($wps);
        return $this->response->setContentType('text/csv')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $fp->reference . '_litchi.csv"')
            ->setBody($content);
    }

    public function exportPhotoPositions($planId)
    {
        $wps = $this->getWaypointsData($planId);
        $fp = $this->getFp($planId);
        if (empty($wps)) {
            return redirect()->to(site_url('/admin/') . $planId)->with('flash_warning', 'No waypoints to export.');
        }
        $content = PhotoPositions::generatePhotoPositionsCsv($wps);
        return $this->response->setContentType('text/csv')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $fp->reference . '_photo_positions.csv"')
            ->setBody($content);
    }

    public function exportEnhancedGeojson($planId)
    {
        $wps = $this->getWaypointsData($planId);
        $fp = $this->getFp($planId);
        if (empty($wps)) {
            return redirect()->to(site_url('/admin/') . $planId)->with('flash_warning', 'No waypoints to export.');
        }
        $content = ExportFormats::generateEnhancedGeojson($wps, $fp->reference, $fp->drone_model ?? 'mini_4_pro');
        return $this->response->setContentType('application/geo+json')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $fp->reference . '_enhanced.geojson"')
            ->setBody($content);
    }

    // ── Share ───────────────────────────────────────────────────

    public function share($planId)
    {
        $fp = $this->getFp($planId);
        $data = $this->request->getJSON(true);
        $expiresDays = (int) ($data['expires_days'] ?? 0);

        $token = SharedLinkModel::generateToken();
        $linkModel = new SharedLinkModel();

        $insertData = [
            'flight_plan_id' => $planId,
            'token'          => $token,
            'is_active'      => 1,
        ];

        if ($expiresDays > 0) {
            $insertData['expires_at'] = date('Y-m-d H:i:s', strtotime("+{$expiresDays} days"));
        }

        $linkModel->insert($insertData);

        $url = site_url('shared/' . $token);
        return $this->response->setJSON(['success' => true, 'url' => $url, 'token' => $token]);
    }

    // ── Helpers ─────────────────────────────────────────────────

    private function getFp(int $planId): object
    {
        $fp = \Config\Database::connect()->table('flight_plans')->where('id', $planId)->get()->getRow();
        if (!$fp) throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        return $fp;
    }

    private function getWaypointsData(int $planId): array
    {
        $waypoints = \Config\Database::connect()->table('waypoints')
            ->where('flight_plan_id', $planId)
            ->orderBy('`index`')
            ->get()->getResult();

        return array_map(fn($w) => WaypointModel::toArray($w), $waypoints);
    }

    private function replaceWaypoints(int $planId, array $waypoints): void
    {
        $db = \Config\Database::connect();
        $db->table('waypoints')->where('flight_plan_id', $planId)->delete();

        foreach ($waypoints as $i => $wp) {
            $db->table('waypoints')->insert([
                'flight_plan_id'    => $planId,
                'index'             => $wp['index'] ?? $i,
                'lat'               => (float) $wp['lat'],
                'lng'               => (float) $wp['lng'],
                'altitude_m'        => (float) ($wp['altitude_m'] ?? 30),
                'speed_ms'          => (float) ($wp['speed_ms'] ?? 5),
                'heading_deg'       => isset($wp['heading_deg']) ? (float) $wp['heading_deg'] : null,
                'gimbal_pitch_deg'  => (float) ($wp['gimbal_pitch_deg'] ?? -90),
                'turn_mode'         => $wp['turn_mode'] ?? 'toPointAndStopWithDiscontinuityCurvature',
                'turn_damping_dist' => (float) ($wp['turn_damping_dist'] ?? 0),
                'hover_time_s'      => (float) ($wp['hover_time_s'] ?? 0),
                'action_type'       => $wp['action_type'] ?? null,
                'poi_lat'           => isset($wp['poi_lat']) ? (float) $wp['poi_lat'] : null,
                'poi_lng'           => isset($wp['poi_lng']) ? (float) $wp['poi_lng'] : null,
            ]);
        }
    }
}
