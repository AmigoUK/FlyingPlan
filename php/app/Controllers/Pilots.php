<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\PilotCertificationModel;
use App\Models\PilotMembershipModel;
use App\Models\PilotEquipmentModel;
use App\Models\PilotDocumentModel;
use App\Libraries\WerkzeugHash;

class Pilots extends BaseController
{
    private const ALLOWED_DOC_EXT = ['png', 'jpg', 'jpeg', 'gif', 'pdf', 'doc', 'docx'];

    public function index()
    {
        $pilots = \Config\Database::connect()->table('users')
            ->where('role', 'pilot')->orderBy('display_name')->get()->getResult();
        return view('admin/pilots/list', ['pilots' => $pilots]);
    }

    public function create()
    {
        if ($this->request->getMethod() === 'POST') {
            $username = $this->request->getPost('username');
            $displayName = $this->request->getPost('display_name');
            $password = $this->request->getPost('password');

            if (empty($username) || empty($displayName) || empty($password)) {
                return redirect()->to('/pilots/new')
                    ->with('flash_danger', 'Username, display name, and password are required.');
            }

            $userModel = new UserModel();
            if ($userModel->findByUsername($username)) {
                return redirect()->to('/pilots/new')
                    ->with('flash_danger', "Username '{$username}' already exists.");
            }

            $data = $this->buildPilotData();
            $data['username'] = $username;
            $data['display_name'] = $displayName;
            $data['password_hash'] = WerkzeugHash::hash($password);
            $data['role'] = 'pilot';
            $data['is_active_user'] = 1;

            $pilotId = $userModel->insert($data);
            return redirect()->to('/pilots/' . $pilotId)
                ->with('flash_success', "Pilot '{$displayName}' created.");
        }

        return view('admin/pilots/new');
    }

    public function view($pilotId)
    {
        $db = \Config\Database::connect();
        $pilot = $db->table('users')->where('id', $pilotId)->get()->getRow();
        if (!$pilot || $pilot->role !== 'pilot') {
            return redirect()->to('/pilots')->with('flash_warning', 'User is not a pilot.');
        }

        $certs = $db->table('pilot_certifications')->where('user_id', $pilotId)->get()->getResult();
        $memberships = $db->table('pilot_memberships')->where('user_id', $pilotId)->get()->getResult();
        $equipment = $db->table('pilot_equipment')->where('user_id', $pilotId)->get()->getResult();
        $documents = $db->table('pilot_documents')->where('user_id', $pilotId)->get()->getResult();

        return view('admin/pilots/detail', [
            'pilot'       => $pilot,
            'certs'       => $certs,
            'memberships' => $memberships,
            'equipment'   => $equipment,
            'documents'   => $documents,
        ]);
    }

    public function edit($pilotId)
    {
        $userModel = new UserModel();
        $pilot = $userModel->find($pilotId);
        if (!$pilot) throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();

        $data = $this->buildPilotData();
        $password = $this->request->getPost('password');
        if (!empty($password)) {
            $data['password_hash'] = WerkzeugHash::hash($password);
        }

        $userModel->update($pilotId, $data);
        return redirect()->to('/pilots/' . $pilotId)->with('flash_success', 'Pilot profile updated.');
    }

    public function toggleActive($pilotId)
    {
        $db = \Config\Database::connect();
        $pilot = $db->table('users')->where('id', $pilotId)->get()->getRow();
        if (!$pilot) throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();

        $newActive = $pilot->is_active_user ? 0 : 1;
        $db->table('users')->where('id', $pilotId)->update(['is_active_user' => $newActive]);
        $msg = $newActive ? 'Pilot activated.' : 'Pilot deactivated.';

        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['ok' => true, 'is_active' => $newActive, 'message' => $msg]);
        }
        return redirect()->to('/pilots')->with('flash_success', $msg);
    }

    public function setAvailability($pilotId)
    {
        $status = $this->request->getPost('status');
        \Config\Database::connect()->table('users')->where('id', $pilotId)->update(['availability_status' => $status]);

        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['ok' => true, 'status' => $status]);
        }
        return redirect()->to('/pilots/' . $pilotId)->with('flash_success', 'Availability updated.');
    }

    // ── Certifications ──────────────────────────────────────────

    public function addCertification($pilotId)
    {
        $certName = $this->request->getPost('cert_name');
        if (empty($certName)) {
            return redirect()->to('/pilots/' . $pilotId)->with('flash_danger', 'Certification name is required.');
        }

        (new PilotCertificationModel())->insert([
            'user_id'      => $pilotId,
            'cert_name'    => $certName,
            'issuing_body' => $this->request->getPost('issuing_body'),
            'cert_number'  => $this->request->getPost('cert_number'),
            'issue_date'   => $this->request->getPost('issue_date') ?: null,
            'expiry_date'  => $this->request->getPost('expiry_date') ?: null,
        ]);

        return redirect()->to('/pilots/' . $pilotId)->with('flash_success', 'Certification added.');
    }

    public function deleteCertification($pilotId, $certId)
    {
        $cert = (new PilotCertificationModel())->find($certId);
        if (!$cert || $cert->user_id != $pilotId) {
            return redirect()->to('/pilots/' . $pilotId)->with('flash_danger', 'Invalid certification.');
        }
        (new PilotCertificationModel())->delete($certId);
        return redirect()->to('/pilots/' . $pilotId)->with('flash_success', 'Certification deleted.');
    }

    // ── Memberships ─────────────────────────────────────────────

    public function addMembership($pilotId)
    {
        $orgName = $this->request->getPost('org_name');
        if (empty($orgName)) {
            return redirect()->to('/pilots/' . $pilotId)->with('flash_danger', 'Organisation name is required.');
        }

        (new PilotMembershipModel())->insert([
            'user_id'           => $pilotId,
            'org_name'          => $orgName,
            'membership_number' => $this->request->getPost('membership_number'),
            'membership_type'   => $this->request->getPost('membership_type'),
            'expiry_date'       => $this->request->getPost('expiry_date') ?: null,
        ]);

        return redirect()->to('/pilots/' . $pilotId)->with('flash_success', 'Membership added.');
    }

    public function deleteMembership($pilotId, $memId)
    {
        $mem = (new PilotMembershipModel())->find($memId);
        if (!$mem || $mem->user_id != $pilotId) {
            return redirect()->to('/pilots/' . $pilotId)->with('flash_danger', 'Invalid membership.');
        }
        (new PilotMembershipModel())->delete($memId);
        return redirect()->to('/pilots/' . $pilotId)->with('flash_success', 'Membership deleted.');
    }

    // ── Equipment ───────────────────────────────────────────────

    public function addEquipment($pilotId)
    {
        $droneModel = $this->request->getPost('drone_model');
        if (empty($droneModel)) {
            return redirect()->to('/pilots/' . $pilotId)->with('flash_danger', 'Drone model is required.');
        }

        $classMark = $this->request->getPost('class_mark');
        if ($classMark && !in_array($classMark, PilotEquipmentModel::CLASS_MARKS)) {
            $classMark = null;
        }

        (new PilotEquipmentModel())->insert([
            'user_id'                 => $pilotId,
            'drone_model'             => $droneModel,
            'serial_number'           => $this->request->getPost('serial_number'),
            'registration_id'         => $this->request->getPost('registration_id'),
            'notes'                   => $this->request->getPost('notes'),
            'class_mark'              => $classMark,
            'mtom_grams'              => $this->request->getPost('mtom_grams') ? (int) $this->request->getPost('mtom_grams') : null,
            'has_camera'              => $this->request->getPost('has_camera') ? 1 : 0,
            'green_light_type'        => $this->request->getPost('green_light_type') ?: 'none',
            'green_light_weight_grams' => $this->request->getPost('green_light_weight_grams') ? (int) $this->request->getPost('green_light_weight_grams') : null,
            'has_low_speed_mode'      => $this->request->getPost('has_low_speed_mode') ? 1 : 0,
            'remote_id_capable'       => $this->request->getPost('remote_id_capable') ? 1 : 0,
            'max_speed_ms'            => $this->request->getPost('max_speed_ms') ? (float) $this->request->getPost('max_speed_ms') : null,
            'max_dimension_m'         => $this->request->getPost('max_dimension_m') ? (float) $this->request->getPost('max_dimension_m') : null,
        ]);

        return redirect()->to('/pilots/' . $pilotId)->with('flash_success', 'Equipment added.');
    }

    public function deleteEquipment($pilotId, $equipId)
    {
        $equip = (new PilotEquipmentModel())->find($equipId);
        if (!$equip || $equip->user_id != $pilotId) {
            return redirect()->to('/pilots/' . $pilotId)->with('flash_danger', 'Invalid equipment.');
        }
        (new PilotEquipmentModel())->delete($equipId);
        return redirect()->to('/pilots/' . $pilotId)->with('flash_success', 'Equipment removed.');
    }

    // ── Documents ───────────────────────────────────────────────

    public function uploadDocument($pilotId)
    {
        $file = $this->request->getFile('file');
        if (!$file || !$file->isValid()) {
            return redirect()->to('/pilots/' . $pilotId)->with('flash_danger', 'No file selected.');
        }

        $ext = strtolower($file->getExtension());
        if (!in_array($ext, self::ALLOWED_DOC_EXT)) {
            return redirect()->to('/pilots/' . $pilotId)->with('flash_danger', 'File type not allowed.');
        }

        $uploadDir = WRITEPATH . 'uploads/pilots/' . $pilotId . '/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $storedName = $file->getRandomName();
        $file->move($uploadDir, $storedName);

        (new PilotDocumentModel())->insert([
            'user_id'           => $pilotId,
            'doc_type'          => $this->request->getPost('doc_type') ?: 'other',
            'label'             => $this->request->getPost('label') ?: $file->getClientName(),
            'original_filename' => $file->getClientName(),
            'stored_filename'   => $storedName,
            'file_size'         => $file->getSize(),
            'mime_type'         => $file->getClientMimeType(),
            'expiry_date'       => $this->request->getPost('expiry_date') ?: null,
        ]);

        return redirect()->to('/pilots/' . $pilotId)->with('flash_success', 'Document uploaded.');
    }

    public function downloadDocument($pilotId, $docId)
    {
        $doc = (new PilotDocumentModel())->find($docId);
        if (!$doc || $doc->user_id != $pilotId) {
            return redirect()->to('/pilots/' . $pilotId)->with('flash_danger', 'Invalid document.');
        }

        $path = WRITEPATH . 'uploads/pilots/' . $pilotId . '/' . $doc->stored_filename;
        if (!file_exists($path)) {
            return redirect()->to('/pilots/' . $pilotId)->with('flash_danger', 'File not found.');
        }

        return $this->response->download($path, null)->setFileName($doc->original_filename);
    }

    public function deleteDocument($pilotId, $docId)
    {
        $doc = (new PilotDocumentModel())->find($docId);
        if (!$doc || $doc->user_id != $pilotId) {
            return redirect()->to('/pilots/' . $pilotId)->with('flash_danger', 'Invalid document.');
        }

        $path = WRITEPATH . 'uploads/pilots/' . $pilotId . '/' . $doc->stored_filename;
        if (file_exists($path)) unlink($path);

        (new PilotDocumentModel())->delete($docId);
        return redirect()->to('/pilots/' . $pilotId)->with('flash_success', 'Document deleted.');
    }

    // ── Helper ──────────────────────────────────────────────────

    private function buildPilotData(): array
    {
        $data = [
            'display_name'       => $this->request->getPost('display_name'),
            'email'              => $this->request->getPost('email'),
            'phone'              => $this->request->getPost('phone'),
            'flying_id'          => $this->request->getPost('flying_id'),
            'operator_id'        => $this->request->getPost('operator_id'),
            'insurance_provider' => $this->request->getPost('insurance_provider'),
            'insurance_policy_no' => $this->request->getPost('insurance_policy_no'),
            'pilot_bio'          => $this->request->getPost('pilot_bio'),
            'a2_cofc_number'     => $this->request->getPost('a2_cofc_number'),
            'gvc_level'          => $this->request->getPost('gvc_level') ?: null,
            'gvc_cert_number'    => $this->request->getPost('gvc_cert_number'),
            'oa_type'            => $this->request->getPost('oa_type') ?: null,
            'oa_reference'       => $this->request->getPost('oa_reference'),
            'mentor_examiner'    => $this->request->getPost('mentor_examiner'),
            'article16_agreed'   => $this->request->getPost('article16_agreed') ? 1 : 0,
            'address_line1'      => $this->request->getPost('address_line1'),
            'address_line2'      => $this->request->getPost('address_line2'),
            'address_city'       => $this->request->getPost('address_city'),
            'address_county'     => $this->request->getPost('address_county'),
            'address_postcode'   => $this->request->getPost('address_postcode'),
            'address_country'    => $this->request->getPost('address_country') ?: 'United Kingdom',
        ];

        // Date fields
        $dateFields = [
            'insurance_expiry', 'flying_id_expiry', 'operator_id_expiry',
            'a2_cofc_expiry', 'gvc_mr_expiry', 'gvc_fw_expiry',
            'practical_competency_date', 'article16_agreed_date', 'oa_expiry',
        ];
        foreach ($dateFields as $field) {
            $val = $this->request->getPost($field);
            $data[$field] = !empty($val) ? $val : null;
        }

        return $data;
    }
}
