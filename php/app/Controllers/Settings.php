<?php

namespace App\Controllers;

use App\Models\AppSettingsModel;
use App\Models\JobTypeModel;
use App\Models\PurposeOptionModel;
use App\Models\HeardAboutOptionModel;

class Settings extends BaseController
{
    public function index()
    {
        $settings = (new AppSettingsModel())->getSettings();
        $jobTypes = (new JobTypeModel())->orderBy('sort_order')->findAll();
        $purposes = (new PurposeOptionModel())->orderBy('sort_order')->findAll();
        $heardAbout = (new HeardAboutOptionModel())->orderBy('sort_order')->findAll();

        return view('admin/settings', [
            'settings'    => $settings,
            'job_types'   => $jobTypes,
            'purposes'    => $purposes,
            'heard_about' => $heardAbout,
        ]);
    }

    public function branding()
    {
        $model = new AppSettingsModel();
        $settings = $model->getSettings();

        $color = $this->request->getPost('primary_color') ?: '#0d6efd';
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
            $color = '#0d6efd';
        }

        $model->update($settings->id, [
            'business_name' => $this->request->getPost('business_name') ?: 'FlyingPlan',
            'logo_url'      => $this->request->getPost('logo_url') ?: '',
            'contact_email' => $this->request->getPost('contact_email') ?: '',
            'tagline'       => $this->request->getPost('tagline') ?: 'Drone Flight Brief',
            'primary_color' => $color,
            'dark_mode'     => $this->request->getPost('dark_mode') ? 1 : 0,
        ]);

        return redirect()->to('/settings')->with('flash_success', 'Branding settings updated.');
    }

    public function formVisibility()
    {
        $model = new AppSettingsModel();
        $settings = $model->getSettings();

        $model->update($settings->id, [
            'show_heard_about'          => $this->request->getPost('show_heard_about') ? 1 : 0,
            'show_customer_type_toggle' => $this->request->getPost('show_customer_type_toggle') ? 1 : 0,
            'show_purpose_fields'       => $this->request->getPost('show_purpose_fields') ? 1 : 0,
            'show_output_format'        => $this->request->getPost('show_output_format') ? 1 : 0,
            'guide_mode'                => $this->request->getPost('guide_mode') ? 1 : 0,
        ]);

        return redirect()->to('/settings')->with('flash_success', 'Form visibility settings updated.');
    }

    // ── Job Types ────────────────────────────────────────────────

    public function createJobType()
    {
        $value = $this->request->getPost('value');
        $label = $this->request->getPost('label');

        if (empty($value) || empty($label)) {
            return redirect()->to('/settings')->with('flash_danger', 'Value and label are required.');
        }

        $model = new JobTypeModel();
        if ($model->where('value', $value)->first()) {
            return redirect()->to('/settings')->with('flash_danger', "Job type '{$value}' already exists.");
        }

        $icon = $this->request->getPost('icon') ?: 'bi-briefcase';
        if (!str_starts_with($icon, 'bi-')) $icon = 'bi-' . $icon;

        $maxSort = \Config\Database::connect()->table('job_types')->selectMax('sort_order')->get()->getRow()->sort_order ?? 0;

        $model->insert([
            'value'      => $value,
            'label'      => $label,
            'icon'       => $icon,
            'category'   => $this->request->getPost('category') ?: 'technical',
            'is_active'  => 1,
            'sort_order' => $maxSort + 1,
        ]);

        return redirect()->to('/settings')->with('flash_success', "Job type '{$label}' created.");
    }

    public function editJobType($id)
    {
        $model = new JobTypeModel();
        $jt = $model->find($id);
        if (!$jt) throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();

        $label = $this->request->getPost('label');
        if (empty($label)) {
            return redirect()->to('/settings')->with('flash_danger', 'Label is required.');
        }

        $icon = $this->request->getPost('icon') ?: $jt->icon;
        if (!str_starts_with($icon, 'bi-')) $icon = 'bi-' . $icon;

        $model->update($id, [
            'label'    => $label,
            'icon'     => $icon,
            'category' => $this->request->getPost('category') ?: $jt->category,
        ]);

        return redirect()->to('/settings')->with('flash_success', "Job type '{$label}' updated.");
    }

    public function toggleJobType($id)
    {
        $model = new JobTypeModel();
        $jt = $model->find($id);
        if (!$jt) throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();

        $newActive = $jt->is_active ? 0 : 1;
        $model->update($id, ['is_active' => $newActive]);
        $msg = "'{$jt->label}' " . ($newActive ? 'activated' : 'deactivated') . '.';

        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['ok' => true, 'is_active' => $newActive, 'message' => $msg]);
        }
        return redirect()->to('/settings')->with('flash_success', $msg);
    }

    public function deleteJobType($id)
    {
        $model = new JobTypeModel();
        $jt = $model->find($id);
        if (!$jt) throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();

        $inUse = \Config\Database::connect()->table('flight_plans')->where('job_type', $jt->value)->countAllResults();
        if ($inUse > 0) {
            return redirect()->to('/settings')->with('flash_danger', "Cannot delete '{$jt->label}' — it is used by existing flight plans.");
        }

        $model->delete($id);
        return redirect()->to('/settings')->with('flash_success', "Job type '{$jt->label}' deleted.");
    }

    // ── Purpose Options ─────────────────────────────────────────

    public function createPurpose()
    {
        $value = $this->request->getPost('value');
        $label = $this->request->getPost('label');

        if (empty($value) || empty($label)) {
            return redirect()->to('/settings')->with('flash_danger', 'Value and label are required.');
        }

        $model = new PurposeOptionModel();
        if ($model->where('value', $value)->first()) {
            return redirect()->to('/settings')->with('flash_danger', "Purpose option '{$value}' already exists.");
        }

        $icon = $this->request->getPost('icon') ?: 'bi-question-circle';
        if (!str_starts_with($icon, 'bi-')) $icon = 'bi-' . $icon;

        $maxSort = \Config\Database::connect()->table('purpose_options')->selectMax('sort_order')->get()->getRow()->sort_order ?? 0;

        $model->insert([
            'value' => $value, 'label' => $label, 'icon' => $icon,
            'is_active' => 1, 'sort_order' => $maxSort + 1,
        ]);

        return redirect()->to('/settings')->with('flash_success', "Purpose option '{$label}' created.");
    }

    public function editPurpose($id)
    {
        $model = new PurposeOptionModel();
        $po = $model->find($id);
        if (!$po) throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();

        $label = $this->request->getPost('label');
        if (empty($label)) return redirect()->to('/settings')->with('flash_danger', 'Label is required.');

        $icon = $this->request->getPost('icon') ?: $po->icon;
        if (!str_starts_with($icon, 'bi-')) $icon = 'bi-' . $icon;

        $model->update($id, ['label' => $label, 'icon' => $icon]);
        return redirect()->to('/settings')->with('flash_success', "Purpose option '{$label}' updated.");
    }

    public function togglePurpose($id)
    {
        $model = new PurposeOptionModel();
        $po = $model->find($id);
        if (!$po) throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();

        $newActive = $po->is_active ? 0 : 1;
        $model->update($id, ['is_active' => $newActive]);
        $msg = "'{$po->label}' " . ($newActive ? 'activated' : 'deactivated') . '.';

        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['ok' => true, 'is_active' => $newActive, 'message' => $msg]);
        }
        return redirect()->to('/settings')->with('flash_success', $msg);
    }

    public function deletePurpose($id)
    {
        $model = new PurposeOptionModel();
        $po = $model->find($id);
        if (!$po) throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();

        $model->delete($id);
        return redirect()->to('/settings')->with('flash_success', "Purpose option '{$po->label}' deleted.");
    }

    // ── Heard-About Options ─────────────────────────────────────

    public function createHeardAbout()
    {
        $value = $this->request->getPost('value');
        $label = $this->request->getPost('label');

        if (empty($value) || empty($label)) {
            return redirect()->to('/settings')->with('flash_danger', 'Value and label are required.');
        }

        $model = new HeardAboutOptionModel();
        if ($model->where('value', $value)->first()) {
            return redirect()->to('/settings')->with('flash_danger', "Heard-about option '{$value}' already exists.");
        }

        $icon = $this->request->getPost('icon') ?: 'bi-question-circle';
        if (!str_starts_with($icon, 'bi-')) $icon = 'bi-' . $icon;

        $maxSort = \Config\Database::connect()->table('heard_about_options')->selectMax('sort_order')->get()->getRow()->sort_order ?? 0;

        $model->insert([
            'value' => $value, 'label' => $label, 'icon' => $icon,
            'is_active' => 1, 'sort_order' => $maxSort + 1,
        ]);

        return redirect()->to('/settings')->with('flash_success', "Heard-about option '{$label}' created.");
    }

    public function editHeardAbout($id)
    {
        $model = new HeardAboutOptionModel();
        $ha = $model->find($id);
        if (!$ha) throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();

        $label = $this->request->getPost('label');
        if (empty($label)) return redirect()->to('/settings')->with('flash_danger', 'Label is required.');

        $icon = $this->request->getPost('icon') ?: $ha->icon;
        if (!str_starts_with($icon, 'bi-')) $icon = 'bi-' . $icon;

        $model->update($id, ['label' => $label, 'icon' => $icon]);
        return redirect()->to('/settings')->with('flash_success', "Heard-about option '{$label}' updated.");
    }

    public function toggleHeardAbout($id)
    {
        $model = new HeardAboutOptionModel();
        $ha = $model->find($id);
        if (!$ha) throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();

        $newActive = $ha->is_active ? 0 : 1;
        $model->update($id, ['is_active' => $newActive]);
        $msg = "'{$ha->label}' " . ($newActive ? 'activated' : 'deactivated') . '.';

        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['ok' => true, 'is_active' => $newActive, 'message' => $msg]);
        }
        return redirect()->to('/settings')->with('flash_success', $msg);
    }

    public function deleteHeardAbout($id)
    {
        $model = new HeardAboutOptionModel();
        $ha = $model->find($id);
        if (!$ha) throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();

        $model->delete($id);
        return redirect()->to('/settings')->with('flash_success', "Heard-about option '{$ha->label}' deleted.");
    }
}
