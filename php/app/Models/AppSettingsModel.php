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
