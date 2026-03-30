<?php

namespace App\Controllers;

use App\Models\OrderModel;
use App\Models\OrderActivityModel;
use App\Models\OrderDeliverableModel;
use App\Models\WaypointModel;
use App\Models\RiskAssessmentModel;
use App\Models\PilotCertificationModel;
use App\Models\PilotMembershipModel;
use App\Models\PilotEquipmentModel;
use App\Models\PilotDocumentModel;
use App\Models\UserModel;
use App\Models\FlightPlanModel;
use App\Libraries\WerkzeugHash;
use App\Services\CategoryEngine;
use App\Services\DroneProfile;
use App\Services\PilotProfile;
use App\Services\FlightParams;
use App\Services\DroneProfiles;
use App\Services\Weather;
use App\Services\Elevation;
use App\Services\KmzParser;
use App\Services\KmzGenerator;

class Pilot extends BaseController
{
    private const ALLOWED_DELIVERABLE_EXT = ['png', 'jpg', 'jpeg', 'gif', 'mp4', 'mov', 'avi', 'pdf', 'zip', 'tiff', 'tif'];
    private const ALLOWED_DOC_EXT = ['png', 'jpg', 'jpeg', 'gif', 'pdf', 'doc', 'docx'];
    private const FORWARD_STATUSES = [
        'assigned'        => ['accepted', 'declined'],
        'accepted'        => ['in_progress'],
        'in_progress'     => ['flight_complete'],
        'flight_complete' => ['delivered'],
    ];

    // ── Dashboard & Profile ─────────────────────────────────────

    public function dashboard()
    {
        $orders = \Config\Database::connect()->table('orders o')
            ->select('o.*, fp.reference, fp.customer_name, fp.job_type, fp.location_lat, fp.location_lng')
            ->join('flight_plans fp', 'fp.id = o.flight_plan_id')
            ->where('o.pilot_id', session('user_id'))
            ->orderBy('o.created_at', 'DESC')
            ->get()->getResult();

        return view('pilot/dashboard', ['orders' => $orders]);
    }

    public function profile()
    {
        $db = \Config\Database::connect();
        $userId = session('user_id');
        $user = $db->table('users')->where('id', $userId)->get()->getRow();

        if ($this->request->getMethod() === 'POST') {
            $data = [
                'display_name' => $this->request->getPost('display_name') ?: $user->display_name,
                'email' => $this->request->getPost('email'),
                'phone' => $this->request->getPost('phone'),
                'flying_id' => $this->request->getPost('flying_id'),
                'operator_id' => $this->request->getPost('operator_id'),
                'insurance_provider' => $this->request->getPost('insurance_provider'),
                'insurance_policy_no' => $this->request->getPost('insurance_policy_no'),
                'pilot_bio' => $this->request->getPost('pilot_bio'),
                'a2_cofc_number' => $this->request->getPost('a2_cofc_number'),
                'gvc_level' => $this->request->getPost('gvc_level') ?: null,
                'gvc_cert_number' => $this->request->getPost('gvc_cert_number'),
                'oa_type' => $this->request->getPost('oa_type') ?: null,
                'oa_reference' => $this->request->getPost('oa_reference'),
                'mentor_examiner' => $this->request->getPost('mentor_examiner'),
                'article16_agreed' => $this->request->getPost('article16_agreed') ? 1 : 0,
                'address_line1' => $this->request->getPost('address_line1'),
                'address_line2' => $this->request->getPost('address_line2'),
                'address_city' => $this->request->getPost('address_city'),
                'address_county' => $this->request->getPost('address_county'),
                'address_postcode' => $this->request->getPost('address_postcode'),
                'address_country' => $this->request->getPost('address_country') ?: 'United Kingdom',
            ];

            $dateFields = ['insurance_expiry', 'flying_id_expiry', 'operator_id_expiry',
                'a2_cofc_expiry', 'gvc_mr_expiry', 'gvc_fw_expiry',
                'practical_competency_date', 'article16_agreed_date', 'oa_expiry'];
            foreach ($dateFields as $f) {
                $val = $this->request->getPost($f);
                $data[$f] = !empty($val) ? $val : null;
            }

            $password = $this->request->getPost('password');
            if (!empty($password)) {
                $data['password_hash'] = WerkzeugHash::hash($password);
            }

            (new UserModel())->update($userId, $data);
            session()->set('display_name', $data['display_name']);
            return redirect()->to('/pilot/profile')->with('flash_success', 'Profile updated.');
        }

        $certs = $db->table('pilot_certifications')->where('user_id', $userId)->get()->getResult();
        $memberships = $db->table('pilot_memberships')->where('user_id', $userId)->get()->getResult();
        $equipment = $db->table('pilot_equipment')->where('user_id', $userId)->get()->getResult();
        $documents = $db->table('pilot_documents')->where('user_id', $userId)->get()->getResult();

        return view('pilot/profile', [
            'user' => $user, 'certs' => $certs, 'memberships' => $memberships,
            'equipment' => $equipment, 'documents' => $documents,
        ]);
    }

    // ── Order Actions ───────────────────────────────────────────

    public function orderDetail($orderId)
    {
        $order = $this->getPilotOrder($orderId);
        $db = \Config\Database::connect();
        $fp = $db->table('flight_plans')->where('id', $order->flight_plan_id)->get()->getRow();
        $waypoints = $db->table('waypoints')->where('flight_plan_id', $fp->id)->orderBy('`index`')->get()->getResult();
        $pois = $db->table('pois')->where('flight_plan_id', $fp->id)->orderBy('sort_order')->get()->getResult();
        $activities = $db->table('order_activities oa')
            ->select('oa.*, u.display_name as user_name')
            ->join('users u', 'u.id = oa.user_id', 'left')
            ->where('oa.order_id', $orderId)->orderBy('oa.created_at', 'DESC')->get()->getResult();
        $deliverables = $db->table('order_deliverables')->where('order_id', $orderId)->get()->getResult();
        $ra = $db->table('risk_assessments')->where('order_id', $orderId)->get()->getRow();
        $equipment = $db->table('pilot_equipment')->where('user_id', session('user_id'))->get()->getResult();

        $allowedNext = self::FORWARD_STATUSES[$order->status] ?? [];

        return view('pilot/order_detail', [
            'order' => $order, 'flight_plan' => $fp, 'waypoints' => $waypoints,
            'pois' => $pois, 'activities' => $activities, 'deliverables' => $deliverables,
            'risk_assessment' => $ra, 'equipment' => $equipment,
            'allowed_next' => $allowedNext,
            'waypoints_json' => json_encode(array_map(fn($w) => WaypointModel::toArray($w), $waypoints)),
            'pois_json' => json_encode($pois),
        ]);
    }

    public function acceptOrder($orderId)
    {
        $order = $this->getPilotOrder($orderId);
        if ($order->status !== 'assigned') {
            return redirect()->to('/pilot/orders/' . $orderId)->with('flash_warning', 'Cannot accept this order.');
        }
        (new OrderModel())->update($orderId, ['status' => 'accepted', 'accepted_at' => date('Y-m-d H:i:s')]);
        $this->logActivity($orderId, 'accepted');
        return redirect()->to('/pilot/orders/' . $orderId)->with('flash_success', 'Order accepted.');
    }

    public function declineOrder($orderId)
    {
        $order = $this->getPilotOrder($orderId);
        if ($order->status !== 'assigned') {
            return redirect()->to('/pilot/orders/' . $orderId)->with('flash_warning', 'Cannot decline this order.');
        }
        $reason = $this->request->getPost('reason');
        (new OrderModel())->update($orderId, ['status' => 'declined', 'decline_reason' => $reason]);
        $this->logActivity($orderId, 'declined', null, null, $reason);
        return redirect()->to('/pilot')->with('flash_info', 'Order declined.');
    }

    public function updateStatus($orderId)
    {
        $order = $this->getPilotOrder($orderId);
        $newStatus = $this->request->getPost('status');
        $allowed = self::FORWARD_STATUSES[$order->status] ?? [];

        if (!in_array($newStatus, $allowed)) {
            return redirect()->to('/pilot/orders/' . $orderId)->with('flash_danger', 'Invalid status transition.');
        }

        // Check risk assessment before starting flight
        if ($newStatus === 'in_progress') {
            if (!$order->risk_assessment_completed) {
                return redirect()->to('/pilot/orders/' . $orderId)
                    ->with('flash_danger', 'Complete the risk assessment before starting the flight.');
            }
            $ra = \Config\Database::connect()->table('risk_assessments')->where('order_id', $orderId)->get()->getRow();
            if ($ra && $ra->decision === 'abort') {
                return redirect()->to('/pilot/orders/' . $orderId)
                    ->with('flash_danger', 'Flight was aborted in risk assessment. Cannot proceed.');
            }
        }

        $update = ['status' => $newStatus];
        $now = date('Y-m-d H:i:s');
        $tsMap = ['in_progress' => 'started_at', 'flight_complete' => 'completed_at', 'delivered' => 'delivered_at'];
        if (isset($tsMap[$newStatus]) && empty($order->{$tsMap[$newStatus]})) {
            $update[$tsMap[$newStatus]] = $now;
        }

        (new OrderModel())->update($orderId, $update);
        $this->logActivity($orderId, 'status_changed', $order->status, $newStatus);

        $label = ucwords(str_replace('_', ' ', $newStatus));
        return redirect()->to('/pilot/orders/' . $orderId)->with('flash_success', "Status updated to {$label}.");
    }

    public function saveNotes($orderId)
    {
        $order = $this->getPilotOrder($orderId);
        (new OrderModel())->update($orderId, ['pilot_notes' => $this->request->getPost('pilot_notes')]);
        $this->logActivity($orderId, 'note_added', null, null, 'Pilot notes updated');
        return redirect()->to('/pilot/orders/' . $orderId)->with('flash_success', 'Notes saved.');
    }

    // ── Flight Params & Category ────────────────────────────────

    public function flightParams($orderId)
    {
        $order = $this->getPilotOrder($orderId);
        $db = \Config\Database::connect();
        $equipment = $db->table('pilot_equipment')->where('user_id', session('user_id'))->get()->getResult();

        if ($this->request->getMethod() === 'POST') {
            $equipId = $this->request->getPost('equipment_id');
            $params = [
                'equipment_id' => $equipId ?: null,
                'time_of_day' => $this->request->getPost('time_of_day'),
                'proximity_to_people' => $this->request->getPost('proximity_to_people'),
                'environment_type' => $this->request->getPost('environment_type'),
                'proximity_to_buildings' => $this->request->getPost('proximity_to_buildings'),
                'airspace_type' => $this->request->getPost('airspace_type'),
                'vlos_type' => $this->request->getPost('vlos_type'),
                'speed_mode' => $this->request->getPost('speed_mode'),
            ];

            // Run category engine
            $equip = $equipId ? $db->table('pilot_equipment')->where('id', $equipId)->get()->getRow() : null;
            $user = $db->table('users')->where('id', session('user_id'))->get()->getRow();

            $drone = new DroneProfile(
                class_mark: $equip->class_mark ?? 'legacy',
                mtom_grams: (int) ($equip->mtom_grams ?? 0),
                has_camera: (bool) ($equip->has_camera ?? true),
                green_light_type: $equip->green_light_type ?? 'none',
                green_light_weight_grams: (int) ($equip->green_light_weight_grams ?? 0),
                has_low_speed_mode: (bool) ($equip->has_low_speed_mode ?? false),
                remote_id_capable: (bool) ($equip->remote_id_capable ?? false),
            );

            $pilot = new PilotProfile(
                has_flyer_id: !empty($user->flying_id),
                has_a2_cofc: !empty($user->a2_cofc_expiry) && $user->a2_cofc_expiry >= date('Y-m-d'),
                gvc_level: $user->gvc_level ?: null,
                oa_type: $user->oa_type ?: null,
                has_insurance: !empty($user->insurance_expiry) && $user->insurance_expiry >= date('Y-m-d'),
            );

            $flight = new FlightParams(
                time_of_day: $params['time_of_day'] ?? 'day',
                proximity_to_people: $params['proximity_to_people'] ?? '50m_plus',
                environment_type: $params['environment_type'] ?? 'open_countryside',
                proximity_to_buildings: $params['proximity_to_buildings'] ?? 'over_150m',
                airspace_type: $params['airspace_type'] ?? 'uncontrolled',
                vlos_type: $params['vlos_type'] ?? 'vlos',
                speed_mode: $params['speed_mode'] ?? 'normal',
            );

            $result = CategoryEngine::determineCategory($drone, $pilot, $flight);

            $params['operational_category'] = $result->category;
            $params['category_determined_at'] = date('Y-m-d H:i:s');
            $params['category_blockers'] = !empty($result->blockers) ? json_encode($result->blockers) : null;

            (new OrderModel())->update($orderId, $params);
            return redirect()->to('/pilot/orders/' . $orderId)->with('flash_success', "Category: {$result->category}");
        }

        return view('pilot/flight_params', ['order' => $order, 'equipment' => $equipment]);
    }

    public function categoryCheck($orderId)
    {
        $order = $this->getPilotOrder($orderId);
        $data = $this->request->getJSON(true);
        $db = \Config\Database::connect();

        $equipId = $data['equipment_id'] ?? null;
        $equip = $equipId ? $db->table('pilot_equipment')->where('id', $equipId)->get()->getRow() : null;
        $user = $db->table('users')->where('id', session('user_id'))->get()->getRow();

        $drone = new DroneProfile(
            class_mark: $equip->class_mark ?? 'legacy',
            mtom_grams: (int) ($equip->mtom_grams ?? 0),
            has_camera: (bool) ($equip->has_camera ?? true),
            green_light_type: $equip->green_light_type ?? 'none',
            green_light_weight_grams: (int) ($equip->green_light_weight_grams ?? 0),
            has_low_speed_mode: (bool) ($equip->has_low_speed_mode ?? false),
            remote_id_capable: (bool) ($equip->remote_id_capable ?? false),
        );

        $pilot = new PilotProfile(
            has_flyer_id: !empty($user->flying_id),
            has_a2_cofc: !empty($user->a2_cofc_expiry) && $user->a2_cofc_expiry >= date('Y-m-d'),
            gvc_level: $user->gvc_level ?: null,
            oa_type: $user->oa_type ?: null,
            has_insurance: !empty($user->insurance_expiry) && $user->insurance_expiry >= date('Y-m-d'),
        );

        $flight = new FlightParams(
            time_of_day: $data['time_of_day'] ?? 'day',
            proximity_to_people: $data['proximity_to_people'] ?? '50m_plus',
            environment_type: $data['environment_type'] ?? 'open_countryside',
            proximity_to_buildings: $data['proximity_to_buildings'] ?? 'over_150m',
            airspace_type: $data['airspace_type'] ?? 'uncontrolled',
            vlos_type: $data['vlos_type'] ?? 'vlos',
            speed_mode: $data['speed_mode'] ?? 'normal',
        );

        $r = CategoryEngine::determineCategory($drone, $pilot, $flight);

        return $this->response->setJSON([
            'category' => $r->category, 'blockers' => $r->blockers, 'warnings' => $r->warnings,
            'min_distance_people_m' => $r->min_distance_people_m,
            'min_distance_buildings_m' => $r->min_distance_buildings_m,
            'can_overfly_people' => $r->can_overfly_people,
            'is_legal_ra_required' => $r->is_legal_ra_required,
            'legal_notes' => $r->legal_notes, 'registration_reqs' => $r->registration_reqs,
        ]);
    }

    // ── Risk Assessment ─────────────────────────────────────────

    public function riskAssessment($orderId)
    {
        $order = $this->getPilotOrder($orderId);
        $db = \Config\Database::connect();
        $existingRa = $db->table('risk_assessments')->where('order_id', $orderId)->get()->getRow();

        if ($this->request->getMethod() === 'GET') {
            return view('pilot/risk_assessment', [
                'order' => $order, 'risk_assessment' => $existingRa,
            ]);
        }

        // POST — create risk assessment
        if ($existingRa) {
            return redirect()->to('/pilot/orders/' . $orderId)->with('flash_warning', 'Risk assessment already completed.');
        }

        $raData = [
            'order_id' => $orderId,
            'pilot_id' => session('user_id'),
            'operational_category' => $order->operational_category,
            'category_version' => 2,
        ];

        // All check fields
        $allChecks = RiskAssessmentModel::CHECK_FIELDS;
        $category = $order->operational_category ?? '';
        if (isset(RiskAssessmentModel::CATEGORY_CHECKS[$category])) {
            $allChecks = array_merge($allChecks, RiskAssessmentModel::CATEGORY_CHECKS[$category]);
        }
        if (in_array($order->time_of_day ?? '', ['night', 'twilight'])) {
            $allChecks = array_merge($allChecks, RiskAssessmentModel::NIGHT_CHECK_FIELDS);
        }

        $missing = 0;
        foreach ($allChecks as $field) {
            $val = $this->request->getPost($field) ? 1 : 0;
            $raData[$field] = $val;
            if (!$val) $missing++;
        }

        if ($missing > 0) {
            return redirect()->to('/pilot/orders/' . $orderId . '/risk-assessment')
                ->with('flash_danger', "All required safety checks must be confirmed. {$missing} remaining.");
        }

        $riskLevel = $this->request->getPost('risk_level');
        $decision = $this->request->getPost('decision');
        $declaration = $this->request->getPost('pilot_declaration');

        if (!$riskLevel) return redirect()->back()->with('flash_danger', 'Please select a risk level.');
        if (!$decision) return redirect()->back()->with('flash_danger', 'Please select a decision.');
        if (!$declaration) return redirect()->back()->with('flash_danger', 'You must confirm the pilot declaration.');

        if ($decision === 'proceed_with_mitigations' && empty($this->request->getPost('mitigation_notes'))) {
            return redirect()->back()->with('flash_danger', 'Mitigation notes are required when proceeding with mitigations.');
        }

        $raData['risk_level'] = $riskLevel;
        $raData['decision'] = $decision;
        $raData['mitigation_notes'] = $this->request->getPost('mitigation_notes');
        $raData['pilot_declaration'] = 1;
        $raData['gps_latitude'] = $this->request->getPost('gps_latitude') ?: null;
        $raData['gps_longitude'] = $this->request->getPost('gps_longitude') ?: null;
        $raData['airspace_planned_altitude'] = $this->request->getPost('airspace_planned_altitude') ?: null;
        $raData['weather_wind_speed'] = $this->request->getPost('weather_wind_speed') ?: null;
        $raData['weather_wind_direction'] = $this->request->getPost('weather_wind_direction');
        $raData['weather_visibility'] = $this->request->getPost('weather_visibility') ?: null;
        $raData['weather_precipitation'] = $this->request->getPost('weather_precipitation');
        $raData['weather_temperature'] = $this->request->getPost('weather_temperature') ?: null;
        $raData['equip_battery_level'] = $this->request->getPost('equip_battery_level') ?: null;

        (new RiskAssessmentModel())->insert($raData);
        (new OrderModel())->update($orderId, ['risk_assessment_completed' => 1]);
        $this->logActivity($orderId, 'risk_assessment_completed', null, $decision);

        if ($decision === 'abort') {
            return redirect()->to('/pilot/orders/' . $orderId)
                ->with('flash_warning', 'Risk assessment recorded. Flight aborted — this order cannot proceed.');
        }

        return redirect()->to('/pilot/orders/' . $orderId)
            ->with('flash_success', 'Pre-flight risk assessment completed. You may now start the flight.');
    }

    // ── Deliverables ────────────────────────────────────────────

    public function uploadDeliverable($orderId)
    {
        $order = $this->getPilotOrder($orderId);
        $file = $this->request->getFile('file');

        if (!$file || !$file->isValid()) {
            return redirect()->to('/pilot/orders/' . $orderId)->with('flash_danger', 'No file selected.');
        }

        $ext = strtolower($file->getExtension());
        if (!in_array($ext, self::ALLOWED_DELIVERABLE_EXT)) {
            return redirect()->to('/pilot/orders/' . $orderId)->with('flash_danger', 'File type not allowed.');
        }

        $dir = WRITEPATH . 'uploads/orders/' . $orderId . '/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $storedName = $file->getRandomName();
        $file->move($dir, $storedName);

        (new OrderDeliverableModel())->insert([
            'order_id' => $orderId, 'uploaded_by_id' => session('user_id'),
            'original_filename' => $file->getClientName(), 'stored_filename' => $storedName,
            'file_size' => $file->getSize(), 'mime_type' => $file->getClientMimeType(),
            'description' => $this->request->getPost('description'),
        ]);

        $this->logActivity($orderId, 'deliverable_uploaded', null, $file->getClientName());
        return redirect()->to('/pilot/orders/' . $orderId)->with('flash_success', 'Deliverable uploaded.');
    }

    public function deleteDeliverable($orderId, $dId)
    {
        $order = $this->getPilotOrder($orderId);
        $deliv = (new OrderDeliverableModel())->find($dId);
        if (!$deliv || $deliv->order_id != $orderId) {
            return $this->response->setStatusCode(403);
        }

        $path = WRITEPATH . 'uploads/orders/' . $orderId . '/' . $deliv->stored_filename;
        if (file_exists($path)) unlink($path);

        (new OrderDeliverableModel())->delete($dId);
        return redirect()->to('/pilot/orders/' . $orderId)->with('flash_success', 'Deliverable removed.');
    }

    // ── Certifications, Memberships, Equipment, Documents ───────

    public function addCertification()
    {
        $certName = $this->request->getPost('cert_name');
        if (empty($certName)) return redirect()->to('/pilot/profile')->with('flash_danger', 'Certification name is required.');

        (new PilotCertificationModel())->insert([
            'user_id' => session('user_id'), 'cert_name' => $certName,
            'issuing_body' => $this->request->getPost('issuing_body'),
            'cert_number' => $this->request->getPost('cert_number'),
            'issue_date' => $this->request->getPost('issue_date') ?: null,
            'expiry_date' => $this->request->getPost('expiry_date') ?: null,
        ]);
        return redirect()->to('/pilot/profile')->with('flash_success', 'Certification added.');
    }

    public function deleteCertification($certId)
    {
        $cert = (new PilotCertificationModel())->find($certId);
        if (!$cert || $cert->user_id != session('user_id')) return $this->response->setStatusCode(403);
        (new PilotCertificationModel())->delete($certId);
        return redirect()->to('/pilot/profile')->with('flash_success', 'Certification deleted.');
    }

    public function addMembership()
    {
        $orgName = $this->request->getPost('org_name');
        if (empty($orgName)) return redirect()->to('/pilot/profile')->with('flash_danger', 'Organisation name is required.');

        (new PilotMembershipModel())->insert([
            'user_id' => session('user_id'), 'org_name' => $orgName,
            'membership_number' => $this->request->getPost('membership_number'),
            'membership_type' => $this->request->getPost('membership_type'),
            'expiry_date' => $this->request->getPost('expiry_date') ?: null,
        ]);
        return redirect()->to('/pilot/profile')->with('flash_success', 'Membership added.');
    }

    public function deleteMembership($memId)
    {
        $mem = (new PilotMembershipModel())->find($memId);
        if (!$mem || $mem->user_id != session('user_id')) return $this->response->setStatusCode(403);
        (new PilotMembershipModel())->delete($memId);
        return redirect()->to('/pilot/profile')->with('flash_success', 'Membership deleted.');
    }

    public function addEquipment()
    {
        $droneModel = $this->request->getPost('drone_model');
        if (empty($droneModel)) return redirect()->to('/pilot/profile')->with('flash_danger', 'Drone model is required.');

        $classMark = $this->request->getPost('class_mark');
        if ($classMark && !in_array($classMark, PilotEquipmentModel::CLASS_MARKS)) $classMark = null;

        (new PilotEquipmentModel())->insert([
            'user_id' => session('user_id'), 'drone_model' => $droneModel,
            'serial_number' => $this->request->getPost('serial_number'),
            'registration_id' => $this->request->getPost('registration_id'),
            'notes' => $this->request->getPost('notes'),
            'class_mark' => $classMark,
            'mtom_grams' => $this->request->getPost('mtom_grams') ? (int) $this->request->getPost('mtom_grams') : null,
            'has_camera' => $this->request->getPost('has_camera') ? 1 : 0,
            'green_light_type' => $this->request->getPost('green_light_type') ?: 'none',
            'green_light_weight_grams' => $this->request->getPost('green_light_weight_grams') ? (int) $this->request->getPost('green_light_weight_grams') : null,
            'has_low_speed_mode' => $this->request->getPost('has_low_speed_mode') ? 1 : 0,
            'remote_id_capable' => $this->request->getPost('remote_id_capable') ? 1 : 0,
            'max_speed_ms' => $this->request->getPost('max_speed_ms') ? (float) $this->request->getPost('max_speed_ms') : null,
            'max_dimension_m' => $this->request->getPost('max_dimension_m') ? (float) $this->request->getPost('max_dimension_m') : null,
        ]);
        return redirect()->to('/pilot/profile')->with('flash_success', 'Equipment added.');
    }

    public function deleteEquipment($equipId)
    {
        $equip = (new PilotEquipmentModel())->find($equipId);
        if (!$equip || $equip->user_id != session('user_id')) return $this->response->setStatusCode(403);
        (new PilotEquipmentModel())->delete($equipId);
        return redirect()->to('/pilot/profile')->with('flash_success', 'Equipment removed.');
    }

    public function uploadDocument()
    {
        $file = $this->request->getFile('file');
        if (!$file || !$file->isValid()) return redirect()->to('/pilot/profile')->with('flash_danger', 'No file selected.');

        $ext = strtolower($file->getExtension());
        if (!in_array($ext, self::ALLOWED_DOC_EXT)) return redirect()->to('/pilot/profile')->with('flash_danger', 'File type not allowed.');

        $dir = WRITEPATH . 'uploads/pilots/' . session('user_id') . '/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $storedName = $file->getRandomName();
        $file->move($dir, $storedName);

        (new PilotDocumentModel())->insert([
            'user_id' => session('user_id'), 'doc_type' => $this->request->getPost('doc_type') ?: 'other',
            'label' => $this->request->getPost('label') ?: $file->getClientName(),
            'original_filename' => $file->getClientName(), 'stored_filename' => $storedName,
            'file_size' => $file->getSize(), 'mime_type' => $file->getClientMimeType(),
            'expiry_date' => $this->request->getPost('expiry_date') ?: null,
        ]);
        return redirect()->to('/pilot/profile')->with('flash_success', 'Document uploaded.');
    }

    public function deleteDocument($docId)
    {
        $doc = (new PilotDocumentModel())->find($docId);
        if (!$doc || $doc->user_id != session('user_id')) return $this->response->setStatusCode(403);

        $path = WRITEPATH . 'uploads/pilots/' . session('user_id') . '/' . $doc->stored_filename;
        if (file_exists($path)) unlink($path);

        (new PilotDocumentModel())->delete($docId);
        return redirect()->to('/pilot/profile')->with('flash_success', 'Document deleted.');
    }

    public function downloadDocument($docId)
    {
        $doc = (new PilotDocumentModel())->find($docId);
        if (!$doc || $doc->user_id != session('user_id')) return $this->response->setStatusCode(403);

        $path = WRITEPATH . 'uploads/pilots/' . session('user_id') . '/' . $doc->stored_filename;
        if (!file_exists($path)) return redirect()->to('/pilot/profile')->with('flash_danger', 'File not found.');

        return $this->response->download($path, null)->setFileName($doc->original_filename);
    }

    // ── AJAX: Weather, Elevation, KMZ, Waypoints ────────────────

    public function getWeather($orderId)
    {
        $order = $this->getPilotOrder($orderId);
        $fp = \Config\Database::connect()->table('flight_plans')->where('id', $order->flight_plan_id)->get()->getRow();

        if (empty($fp->location_lat) || empty($fp->location_lng)) {
            return $this->response->setJSON(['error' => 'No location set']);
        }

        $weather = Weather::getWeather((float) $fp->location_lat, (float) $fp->location_lng);
        $profile = DroneProfiles::getProfile($fp->drone_model ?? 'mini_4_pro');
        $weather['drone_warnings'] = Weather::checkDroneWarnings($weather['current'], $profile);
        return $this->response->setJSON($weather);
    }

    public function getElevation($orderId)
    {
        $order = $this->getPilotOrder($orderId);
        $wps = $this->getOrderWaypoints($order);
        $enriched = Elevation::getWaypointElevations($wps);
        return $this->response->setJSON(['success' => true, 'waypoints' => $enriched]);
    }

    public function importKmz($orderId)
    {
        $order = $this->getPilotOrder($orderId);
        $file = $this->request->getFile('kmz_file');

        if (!$file || !$file->isValid()) {
            return $this->response->setJSON(['success' => false, 'error' => 'No file uploaded'])->setStatusCode(400);
        }

        $result = KmzParser::parseKmz(file_get_contents($file->getTempName()));
        if ($result['error']) {
            return $this->response->setJSON(['success' => false, 'error' => $result['error']])->setStatusCode(400);
        }
        if (empty($result['waypoints'])) {
            return $this->response->setJSON(['success' => false, 'error' => 'No waypoints found'])->setStatusCode(400);
        }

        $this->replaceOrderWaypoints($order, $result['waypoints']);

        if ($result['drone_model']) {
            (new FlightPlanModel())->update($order->flight_plan_id, ['drone_model' => $result['drone_model']]);
        }

        $this->logActivity($orderId, 'waypoints_updated', null, (string) count($result['waypoints']));
        return $this->response->setJSON(['success' => true, 'count' => count($result['waypoints']),
            'drone_model' => $result['drone_model'], 'waypoints' => $result['waypoints']]);
    }

    public function saveWaypoints($orderId)
    {
        $order = $this->getPilotOrder($orderId);
        $data = $this->request->getJSON(true);

        if (!is_array($data)) {
            return $this->response->setJSON(['success' => false, 'error' => 'Invalid data'])->setStatusCode(400);
        }

        $this->replaceOrderWaypoints($order, $data);
        $this->logActivity($orderId, 'waypoints_updated', null, (string) count($data));
        return $this->response->setJSON(['success' => true, 'count' => count($data)]);
    }

    public function exportKmz($orderId)
    {
        $order = $this->getPilotOrder($orderId);
        $wps = $this->getOrderWaypoints($order);
        $fp = \Config\Database::connect()->table('flight_plans')->where('id', $order->flight_plan_id)->get()->getRow();

        $content = KmzGenerator::generateKmz($wps, $fp->reference, $fp->drone_model ?? 'mini_4_pro');
        return $this->response->setContentType('application/vnd.google-earth.kmz')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $fp->reference . '.kmz"')
            ->setBody($content);
    }

    public function reportPdf($orderId)
    {
        return redirect()->to('/pilot/orders/' . $orderId)
            ->with('flash_warning', 'PDF generation not yet available in PHP version.');
    }

    // ── Helpers ─────────────────────────────────────────────────

    private function getPilotOrder(int $orderId): object
    {
        $order = \Config\Database::connect()->table('orders')->where('id', $orderId)->get()->getRow();
        if (!$order) throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        if ($order->pilot_id != session('user_id')) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Forbidden');
        }
        return $order;
    }

    private function getOrderWaypoints(object $order): array
    {
        $waypoints = \Config\Database::connect()->table('waypoints')
            ->where('flight_plan_id', $order->flight_plan_id)
            ->orderBy('`index`')->get()->getResult();
        return array_map(fn($w) => WaypointModel::toArray($w), $waypoints);
    }

    private function replaceOrderWaypoints(object $order, array $waypoints): void
    {
        $db = \Config\Database::connect();
        $db->table('waypoints')->where('flight_plan_id', $order->flight_plan_id)->delete();
        foreach ($waypoints as $i => $wp) {
            $db->table('waypoints')->insert([
                'flight_plan_id' => $order->flight_plan_id,
                'index' => $wp['index'] ?? $i,
                'lat' => (float) $wp['lat'], 'lng' => (float) $wp['lng'],
                'altitude_m' => (float) ($wp['altitude_m'] ?? 30),
                'speed_ms' => (float) ($wp['speed_ms'] ?? 5),
                'heading_deg' => isset($wp['heading_deg']) ? (float) $wp['heading_deg'] : null,
                'gimbal_pitch_deg' => (float) ($wp['gimbal_pitch_deg'] ?? -90),
                'turn_mode' => $wp['turn_mode'] ?? 'toPointAndStopWithDiscontinuityCurvature',
                'turn_damping_dist' => (float) ($wp['turn_damping_dist'] ?? 0),
                'hover_time_s' => (float) ($wp['hover_time_s'] ?? 0),
                'action_type' => $wp['action_type'] ?? null,
                'poi_lat' => isset($wp['poi_lat']) ? (float) $wp['poi_lat'] : null,
                'poi_lng' => isset($wp['poi_lng']) ? (float) $wp['poi_lng'] : null,
            ]);
        }
    }

    private function logActivity(int $orderId, string $action, ?string $oldValue = null, ?string $newValue = null, ?string $details = null): void
    {
        (new OrderActivityModel())->insert([
            'order_id' => $orderId, 'user_id' => session('user_id'),
            'action' => $action, 'old_value' => $oldValue,
            'new_value' => $newValue, 'details' => $details,
        ]);
    }
}
