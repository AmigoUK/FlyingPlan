<?php

namespace App\Models;

use CodeIgniter\Model;

class AppSettingsModel extends Model
{
    protected $table = 'app_settings';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $allowedFields = [
        'business_name', 'logo_url', 'primary_color', 'contact_email', 'tagline',
        'show_heard_about', 'show_customer_type_toggle', 'show_purpose_fields',
        'show_output_format', 'guide_mode', 'dark_mode',
        // Template system (Phase 1)
        'active_template', 'modules_json', 'solo_mode', 'default_drone_model',
        'form_fields_json', 'planning_panels_json', 'pilot_steps_json',
    ];

    /**
     * Get the singleton settings row (creates default if missing).
     */
    public function getSettings(): object
    {
        $settings = $this->find(1);
        if (!$settings) {
            $this->insert([
                'id'            => 1,
                'business_name' => 'FlyingPlan',
                'primary_color' => '#0d6efd',
                'tagline'       => 'Drone Flight Brief',
            ]);
            $settings = $this->find(1);
        }
        return $settings;
    }

    // ── Template System Helpers ────────────────────────────────

    /**
     * Check if a module is enabled.
     */
    public function isModuleEnabled(string $module): bool
    {
        $settings = $this->getSettings();
        $modules = json_decode($settings->modules_json ?? '{}', true) ?: [];
        return !empty($modules[$module]);
    }

    /**
     * Get form field mode: 'required', 'visible', or 'hidden'.
     */
    public function getFieldMode(string $fieldName): string
    {
        $settings = $this->getSettings();
        $fields = json_decode($settings->form_fields_json ?? '{}', true) ?: [];
        return $fields[$fieldName] ?? 'visible';
    }

    /**
     * Check if a planning panel is enabled.
     */
    public function isPanelEnabled(string $panelKey): bool
    {
        $settings = $this->getSettings();
        $panels = json_decode($settings->planning_panels_json ?? '{}', true) ?: [];
        return $panels[$panelKey] ?? true;
    }

    /**
     * Check if a pilot workflow step is enabled.
     */
    public function isPilotStepEnabled(string $step): bool
    {
        $settings = $this->getSettings();
        $steps = json_decode($settings->pilot_steps_json ?? '{}', true) ?: [];
        return $steps[$step] ?? true;
    }

    /**
     * Apply a template: write JSON columns and toggle job types.
     */
    public function applyTemplate(string $templateId): bool
    {
        $template = \Config\TemplateDefinitions::get($templateId);
        if (!$template) return false;

        $this->update(1, [
            'active_template'      => $templateId,
            'modules_json'         => json_encode($template['modules']),
            'solo_mode'            => $template['solo_mode'] ? 1 : 0,
            'guide_mode'           => $template['guide_mode'] ? 1 : 0,
            'default_drone_model'  => $template['default_drone_model'],
            'form_fields_json'     => json_encode($template['form_fields']),
            'planning_panels_json' => json_encode($template['planning_panels']),
            'pilot_steps_json'     => json_encode($template['pilot_steps']),
        ]);

        // Toggle job types
        $db = \Config\Database::connect();
        $db->table('job_types')->update(['is_active' => 0]);
        if (!empty($template['active_job_types'])) {
            $db->table('job_types')->whereIn('value', $template['active_job_types'])->update(['is_active' => 1]);
        }

        return true;
    }

    // ── Utility ─────────────────────────────────────────────────

    /**
     * Convert hex color to comma-separated RGB string.
     */
    public static function primaryColorRgb(object $settings): string
    {
        $hex = ltrim($settings->primary_color ?? '#0d6efd', '#');
        if (strlen($hex) === 6) {
            return implode(', ', [
                hexdec(substr($hex, 0, 2)),
                hexdec(substr($hex, 2, 2)),
                hexdec(substr($hex, 4, 2)),
            ]);
        }
        return '13, 110, 253';
    }
}
