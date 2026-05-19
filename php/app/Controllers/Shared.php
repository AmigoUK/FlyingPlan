<?php

namespace App\Controllers;

use App\Models\SharedLinkModel;
use App\Models\WaypointModel;
use App\Services\GeoUtils;

class Shared extends BaseController
{
    public function view($token)
    {
        $linkModel = new SharedLinkModel();
        $link = $linkModel->where('token', $token)->first();

        if (!$link) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        if (!SharedLinkModel::isValid($link)) {
            return $this->response->setStatusCode(410, 'Link expired');
        }

        $db = \Config\Database::connect();

        $flightPlan = $db->table('flight_plans')->where('id', $link->flight_plan_id)->get()->getRow();
        if (!$flightPlan) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $waypoints = $db->table('waypoints')
            ->where('flight_plan_id', $flightPlan->id)
            ->orderBy('`index`', 'ASC')
            ->get()->getResult();

        $pois = $db->table('pois')
            ->where('flight_plan_id', $flightPlan->id)
            ->orderBy('sort_order', 'ASC')
            ->get()->getResult();

        // Calculate total distance
        $totalDistanceM = 0;
        for ($i = 1; $i < count($waypoints); $i++) {
            $totalDistanceM += GeoUtils::haversine(
                $waypoints[$i - 1]->lat, $waypoints[$i - 1]->lng,
                $waypoints[$i]->lat, $waypoints[$i]->lng
            );
        }

        return view('shared/mission_view', [
            'flight_plan'      => $flightPlan,
            'waypoints'        => $waypoints,
            'pois'             => $pois,
            'waypoints_json'   => json_encode(array_map(fn($w) => WaypointModel::toArray($w), $waypoints)),
            'pois_json'        => json_encode($pois),
            'waypoint_count'   => count($waypoints),
            'total_distance_m' => round($totalDistanceM, 1),
            'total_distance_km' => round($totalDistanceM / 1000, 2),
        ]);
    }
}
