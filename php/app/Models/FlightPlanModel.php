<?php

namespace App\Models;

use CodeIgniter\Model;

class FlightPlanModel extends Model
{
    protected $table = 'flight_plans';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $allowedFields = [
        'reference', 'status', 'customer_name', 'customer_email', 'customer_phone',
        'customer_company', 'heard_about', 'job_type', 'job_description',
        'preferred_dates', 'time_window', 'urgency', 'special_requirements',
        'location_address', 'location_lat', 'location_lng', 'area_polygon',
        'estimated_area_sqm', 'altitude_preset', 'altitude_custom_m',
        'camera_angle', 'video_resolution', 'photo_mode', 'no_fly_notes',
        'privacy_notes', 'customer_type', 'business_abn', 'billing_contact',
        'billing_email', 'purchase_order', 'footage_purpose', 'footage_purpose_other',
        'output_format', 'video_duration', 'shot_types', 'delivery_timeline',
        'drone_model', 'admin_notes', 'consent_given', 'source',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    public const STATUSES = ['new', 'in_review', 'route_planned', 'completed', 'cancelled'];

    /**
     * Generate a unique reference like FP-20260329-1234.
     */
    public function generateReference(): string
    {
        do {
            $ref = 'FP-' . date('Ymd') . '-' . str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        } while ($this->where('reference', $ref)->first());

        return $ref;
    }
}
