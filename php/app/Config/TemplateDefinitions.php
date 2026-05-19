<?php

namespace Config;

/**
 * Pre-built configuration templates for different operator types.
 * Templates are code (version-controlled), not database rows.
 */
class TemplateDefinitions
{
    public static function get(string $id): ?array
    {
        $templates = self::getAll();
        return $templates[$id] ?? null;
    }

    public static function getAll(): array
    {
        return [
            'solo_roof_inspector' => [
                'id'          => 'solo_roof_inspector',
                'label'       => 'Solo Roof Inspector',
                'description' => 'Minimal form, inspection-focused workflow for a single operator.',
                'icon'        => 'bi-house-check',
                'modules'     => ['planning' => true, 'compliance' => true, 'team' => false, 'analytics' => true],
                'solo_mode'   => true,
                'guide_mode'  => false,
                'default_drone_model' => 'mini_4_pro',
                'active_job_types' => ['inspection', 'survey', 'construction', 'emergency_insurance'],
                'form_fields' => [
                    'customer_type' => 'hidden', 'customer_name' => 'required', 'customer_email' => 'required',
                    'customer_phone' => 'required', 'customer_company' => 'hidden', 'business_abn' => 'hidden',
                    'billing_contact' => 'hidden', 'billing_email' => 'hidden', 'purchase_order' => 'hidden',
                    'heard_about' => 'hidden', 'job_type' => 'required', 'urgency' => 'hidden',
                    'job_description' => 'required', 'preferred_dates' => 'visible', 'time_window' => 'hidden',
                    'special_requirements' => 'visible', 'attachments' => 'visible', 'location_address' => 'required',
                    'area_polygon' => 'visible', 'altitude_preset' => 'visible', 'camera_angle' => 'hidden',
                    'video_resolution' => 'hidden', 'photo_mode' => 'hidden', 'no_fly_notes' => 'visible',
                    'privacy_notes' => 'hidden', 'footage_purpose' => 'hidden', 'output_format' => 'hidden',
                    'video_duration' => 'hidden', 'shot_types' => 'hidden', 'delivery_timeline' => 'hidden',
                ],
                'planning_panels' => [
                    'route_stats' => true, 'path_tools' => true, 'gsd' => true, 'patterns' => true,
                    'grid' => false, 'oblique' => false, 'facade' => true, 'coverage' => true, 'quality' => false,
                ],
                'pilot_steps' => ['flight_params' => true, 'risk_assessment' => true],
            ],

            'wedding_event_videographer' => [
                'id'          => 'wedding_event_videographer',
                'label'       => 'Wedding & Event Videographer',
                'description' => 'Creative focus with video and shot type fields. No technical planning tools.',
                'icon'        => 'bi-camera-reels',
                'modules'     => ['planning' => false, 'compliance' => false, 'team' => false, 'analytics' => false],
                'solo_mode'   => true,
                'guide_mode'  => false,
                'default_drone_model' => 'air_3s',
                'active_job_types' => ['event_celebration', 'aerial_photo', 'real_estate'],
                'form_fields' => [
                    'customer_type' => 'visible', 'customer_name' => 'required', 'customer_email' => 'required',
                    'customer_phone' => 'visible', 'customer_company' => 'visible', 'business_abn' => 'hidden',
                    'billing_contact' => 'hidden', 'billing_email' => 'hidden', 'purchase_order' => 'hidden',
                    'heard_about' => 'visible', 'job_type' => 'required', 'urgency' => 'hidden',
                    'job_description' => 'required', 'preferred_dates' => 'required', 'time_window' => 'visible',
                    'special_requirements' => 'visible', 'attachments' => 'visible', 'location_address' => 'required',
                    'area_polygon' => 'hidden', 'altitude_preset' => 'hidden', 'camera_angle' => 'hidden',
                    'video_resolution' => 'visible', 'photo_mode' => 'hidden', 'no_fly_notes' => 'hidden',
                    'privacy_notes' => 'hidden', 'footage_purpose' => 'required', 'output_format' => 'required',
                    'video_duration' => 'required', 'shot_types' => 'required', 'delivery_timeline' => 'required',
                ],
                'planning_panels' => [
                    'route_stats' => false, 'path_tools' => false, 'gsd' => false, 'patterns' => false,
                    'grid' => false, 'oblique' => false, 'facade' => false, 'coverage' => false, 'quality' => false,
                ],
                'pilot_steps' => ['flight_params' => false, 'risk_assessment' => false],
            ],

            'survey_mapping_company' => [
                'id'          => 'survey_mapping_company',
                'label'       => 'Survey & Mapping Company',
                'description' => 'Full planning tools, compliance, and multi-pilot team management.',
                'icon'        => 'bi-building',
                'modules'     => ['planning' => true, 'compliance' => true, 'team' => true, 'analytics' => true],
                'solo_mode'   => false,
                'guide_mode'  => false,
                'default_drone_model' => 'mavic_3_pro',
                'active_job_types' => ['survey', 'inspection', 'construction', 'agriculture', 'aerial_photo'],
                'form_fields' => [
                    'customer_type' => 'hidden', 'customer_name' => 'required', 'customer_email' => 'required',
                    'customer_phone' => 'required', 'customer_company' => 'required', 'business_abn' => 'visible',
                    'billing_contact' => 'visible', 'billing_email' => 'visible', 'purchase_order' => 'visible',
                    'heard_about' => 'hidden', 'job_type' => 'required', 'urgency' => 'visible',
                    'job_description' => 'required', 'preferred_dates' => 'visible', 'time_window' => 'visible',
                    'special_requirements' => 'visible', 'attachments' => 'visible', 'location_address' => 'required',
                    'area_polygon' => 'required', 'altitude_preset' => 'required', 'camera_angle' => 'visible',
                    'video_resolution' => 'hidden', 'photo_mode' => 'visible', 'no_fly_notes' => 'visible',
                    'privacy_notes' => 'visible', 'footage_purpose' => 'hidden', 'output_format' => 'hidden',
                    'video_duration' => 'hidden', 'shot_types' => 'hidden', 'delivery_timeline' => 'visible',
                ],
                'planning_panels' => [
                    'route_stats' => true, 'path_tools' => true, 'gsd' => true, 'patterns' => true,
                    'grid' => true, 'oblique' => true, 'facade' => true, 'coverage' => true, 'quality' => true,
                ],
                'pilot_steps' => ['flight_params' => true, 'risk_assessment' => true],
            ],

            'general' => [
                'id'          => 'general',
                'label'       => 'General Drone Operator',
                'description' => 'Balanced defaults for versatile operators. Most features enabled.',
                'icon'        => 'bi-drone',
                'modules'     => ['planning' => true, 'compliance' => true, 'team' => false, 'analytics' => true],
                'solo_mode'   => true,
                'guide_mode'  => true,
                'default_drone_model' => 'mini_4_pro',
                'active_job_types' => ['aerial_photo', 'inspection', 'survey', 'event_celebration', 'real_estate', 'construction', 'agriculture', 'emergency_insurance', 'custom_other'],
                'form_fields' => [
                    'customer_type' => 'visible', 'customer_name' => 'required', 'customer_email' => 'required',
                    'customer_phone' => 'visible', 'customer_company' => 'visible', 'business_abn' => 'hidden',
                    'billing_contact' => 'hidden', 'billing_email' => 'hidden', 'purchase_order' => 'hidden',
                    'heard_about' => 'visible', 'job_type' => 'required', 'urgency' => 'hidden',
                    'job_description' => 'required', 'preferred_dates' => 'visible', 'time_window' => 'visible',
                    'special_requirements' => 'visible', 'attachments' => 'visible', 'location_address' => 'required',
                    'area_polygon' => 'visible', 'altitude_preset' => 'visible', 'camera_angle' => 'hidden',
                    'video_resolution' => 'hidden', 'photo_mode' => 'hidden', 'no_fly_notes' => 'hidden',
                    'privacy_notes' => 'hidden', 'footage_purpose' => 'visible', 'output_format' => 'visible',
                    'video_duration' => 'hidden', 'shot_types' => 'hidden', 'delivery_timeline' => 'visible',
                ],
                'planning_panels' => [
                    'route_stats' => true, 'path_tools' => true, 'gsd' => true, 'patterns' => true,
                    'grid' => true, 'oblique' => false, 'facade' => false, 'coverage' => false, 'quality' => false,
                ],
                'pilot_steps' => ['flight_params' => true, 'risk_assessment' => true],
            ],

            'custom' => [
                'id'          => 'custom',
                'label'       => 'Custom Setup',
                'description' => 'Full control — configure everything manually from scratch.',
                'icon'        => 'bi-sliders',
                'modules'     => ['planning' => true, 'compliance' => true, 'team' => true, 'analytics' => true],
                'solo_mode'   => false,
                'guide_mode'  => false,
                'default_drone_model' => 'mini_4_pro',
                'active_job_types' => ['aerial_photo', 'inspection', 'survey', 'event_celebration', 'real_estate', 'construction', 'agriculture', 'emergency_insurance', 'custom_other'],
                'form_fields' => [
                    'customer_type' => 'visible', 'customer_name' => 'required', 'customer_email' => 'required',
                    'customer_phone' => 'visible', 'customer_company' => 'visible', 'business_abn' => 'visible',
                    'billing_contact' => 'visible', 'billing_email' => 'visible', 'purchase_order' => 'visible',
                    'heard_about' => 'visible', 'job_type' => 'required', 'urgency' => 'visible',
                    'job_description' => 'required', 'preferred_dates' => 'visible', 'time_window' => 'visible',
                    'special_requirements' => 'visible', 'attachments' => 'visible', 'location_address' => 'required',
                    'area_polygon' => 'visible', 'altitude_preset' => 'visible', 'camera_angle' => 'visible',
                    'video_resolution' => 'visible', 'photo_mode' => 'visible', 'no_fly_notes' => 'visible',
                    'privacy_notes' => 'visible', 'footage_purpose' => 'visible', 'output_format' => 'visible',
                    'video_duration' => 'visible', 'shot_types' => 'visible', 'delivery_timeline' => 'visible',
                ],
                'planning_panels' => [
                    'route_stats' => true, 'path_tools' => true, 'gsd' => true, 'patterns' => true,
                    'grid' => true, 'oblique' => true, 'facade' => true, 'coverage' => true, 'quality' => true,
                ],
                'pilot_steps' => ['flight_params' => true, 'risk_assessment' => true],
            ],
        ];
    }

    /**
     * Get template list for UI display (id, label, description, icon only).
     */
    public static function getList(): array
    {
        $list = [];
        foreach (self::getAll() as $t) {
            $list[] = [
                'id' => $t['id'],
                'label' => $t['label'],
                'description' => $t['description'],
                'icon' => $t['icon'],
            ];
        }
        return $list;
    }
}
