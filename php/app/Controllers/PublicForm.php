<?php

namespace App\Controllers;

use App\Models\FlightPlanModel;
use App\Models\PoiModel;
use App\Models\UploadModel;
use App\Models\JobTypeModel;

class PublicForm extends BaseController
{
    public function form()
    {
        return view('public/form');
    }

    public function submit()
    {
        // Validation
        $name = $this->request->getPost('customer_name');
        $email = $this->request->getPost('customer_email');
        $jobType = $this->request->getPost('job_type');
        $jobDesc = $this->request->getPost('job_description');
        $locationLat = $this->request->getPost('location_lat');
        $locationLng = $this->request->getPost('location_lng');
        $consent = $this->request->getPost('consent_given');

        $errors = [];
        if (empty($name)) $errors[] = 'Customer name is required.';
        if (empty($email)) $errors[] = 'Customer email is required.';
        if (empty($jobDesc)) $errors[] = 'Job description is required.';
        if (empty($locationLat) || empty($locationLng)) $errors[] = 'Location pin must be placed on the map.';
        if (empty($consent)) $errors[] = 'You must give consent to proceed.';

        // Validate job type
        if (!empty($jobType)) {
            $jtModel = new JobTypeModel();
            $validJt = $jtModel->where('value', $jobType)->where('is_active', 1)->first();
            if (!$validJt) $errors[] = 'Valid job type is required.';
        } else {
            $errors[] = 'Valid job type is required.';
        }

        if (!empty($errors)) {
            foreach ($errors as $err) {
                session()->setFlashdata('flash_danger', $err);
            }
            return redirect()->to('/');
        }

        $fpModel = new FlightPlanModel();

        // Parse polygon and calculate area
        $areaPolygon = $this->request->getPost('area_polygon');
        $estimatedAreaSqm = null;
        if (!empty($areaPolygon)) {
            $coords = json_decode($areaPolygon, true);
            if (is_array($coords) && count($coords) >= 3) {
                $estimatedAreaSqm = $this->calculatePolygonArea($coords);
            }
        }

        // Parse shot types
        $shotTypesJson = $this->request->getPost('shot_types_json');
        $shotTypes = null;
        if (!empty($shotTypesJson)) {
            $decoded = json_decode($shotTypesJson, true);
            if (is_array($decoded)) {
                $shotTypes = json_encode($decoded);
            }
        }

        $data = [
            'reference'          => $fpModel->generateReference(),
            'status'             => 'new',
            'customer_name'      => $name,
            'customer_email'     => $email,
            'customer_phone'     => $this->request->getPost('customer_phone'),
            'customer_company'   => $this->request->getPost('customer_company'),
            'heard_about'        => $this->request->getPost('heard_about'),
            'job_type'           => $jobType,
            'job_description'    => $jobDesc,
            'preferred_dates'    => $this->request->getPost('preferred_dates'),
            'time_window'        => $this->request->getPost('time_window'),
            'urgency'            => $this->request->getPost('urgency') ?: 'normal',
            'special_requirements' => $this->request->getPost('special_requirements'),
            'location_address'   => $this->request->getPost('location_address'),
            'location_lat'       => (float) $locationLat,
            'location_lng'       => (float) $locationLng,
            'area_polygon'       => $areaPolygon,
            'estimated_area_sqm' => $estimatedAreaSqm,
            'altitude_preset'    => $this->request->getPost('altitude_preset'),
            'altitude_custom_m'  => $this->request->getPost('altitude_custom_m') ?: null,
            'camera_angle'       => $this->request->getPost('camera_angle'),
            'video_resolution'   => $this->request->getPost('video_resolution'),
            'photo_mode'         => $this->request->getPost('photo_mode'),
            'no_fly_notes'       => $this->request->getPost('no_fly_notes'),
            'privacy_notes'      => $this->request->getPost('privacy_notes'),
            'customer_type'      => $this->request->getPost('customer_type') ?: 'private',
            'business_abn'       => $this->request->getPost('business_abn'),
            'billing_contact'    => $this->request->getPost('billing_contact'),
            'billing_email'      => $this->request->getPost('billing_email'),
            'purchase_order'     => $this->request->getPost('purchase_order'),
            'footage_purpose'    => $this->request->getPost('footage_purpose'),
            'footage_purpose_other' => $this->request->getPost('footage_purpose_other'),
            'output_format'      => $this->request->getPost('output_format'),
            'video_duration'     => $this->request->getPost('video_duration'),
            'shot_types'         => $shotTypes,
            'delivery_timeline'  => $this->request->getPost('delivery_timeline'),
            'consent_given'      => 1,
        ];

        $fpId = $fpModel->insert($data);

        // POIs
        $poisJson = $this->request->getPost('pois_json');
        if (!empty($poisJson)) {
            $pois = json_decode($poisJson, true);
            if (is_array($pois)) {
                $poiModel = new PoiModel();
                foreach ($pois as $i => $poi) {
                    if (isset($poi['lat'], $poi['lng'])) {
                        $poiModel->insert([
                            'flight_plan_id' => $fpId,
                            'lat'            => (float) $poi['lat'],
                            'lng'            => (float) $poi['lng'],
                            'label'          => $poi['label'] ?? null,
                            'sort_order'     => $i,
                        ]);
                    }
                }
            }
        }

        // File uploads
        $files = $this->request->getFileMultiple('attachments');
        if ($files) {
            $uploadDir = WRITEPATH . 'uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $uploadModel = new UploadModel();
            $allowedExt = ['png', 'jpg', 'jpeg', 'gif', 'pdf', 'doc', 'docx'];

            foreach ($files as $file) {
                if ($file->isValid() && !$file->hasMoved()) {
                    $ext = strtolower($file->getExtension());
                    if (in_array($ext, $allowedExt)) {
                        $storedName = $file->getRandomName();
                        $file->move($uploadDir, $storedName);
                        $uploadModel->insert([
                            'flight_plan_id'  => $fpId,
                            'original_filename' => $file->getClientName(),
                            'stored_filename'   => $storedName,
                            'file_size'         => $file->getSize(),
                            'mime_type'         => $file->getClientMimeType(),
                        ]);
                    }
                }
            }
        }

        return redirect()->to('/confirmation?ref=' . $data['reference']);
    }

    public function confirmation()
    {
        $ref = $this->request->getGet('ref');
        return view('public/confirmation', ['reference' => $ref]);
    }

    /**
     * Calculate polygon area using Shoelace formula with equirectangular projection.
     */
    private function calculatePolygonArea(array $coords): float
    {
        if (count($coords) < 3) return 0;

        $centerLat = array_sum(array_column($coords, 0)) / count($coords);
        $metersPerDegLat = 110540;
        $metersPerDegLng = 111320 * cos(deg2rad($centerLat));

        $area = 0;
        $n = count($coords);
        for ($i = 0; $i < $n; $i++) {
            $j = ($i + 1) % $n;
            $x1 = $coords[$i][1] * $metersPerDegLng;
            $y1 = $coords[$i][0] * $metersPerDegLat;
            $x2 = $coords[$j][1] * $metersPerDegLng;
            $y2 = $coords[$j][0] * $metersPerDegLat;
            $area += $x1 * $y2 - $x2 * $y1;
        }

        return abs($area) / 2;
    }
}
