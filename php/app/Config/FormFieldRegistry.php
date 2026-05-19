<?php

namespace Config;

/**
 * Registry of all configurable public form fields.
 * Single source of truth for field names, labels, wizard steps, and defaults.
 */
class FormFieldRegistry
{
    /**
     * @return array<string, array{label: string, step: int, default: string, always: bool}>
     */
    public static function getFields(): array
    {
        return [
            // Step 1: Your Details
            'customer_type'    => ['label' => 'Private/Business Toggle', 'step' => 1, 'default' => 'visible', 'always' => false],
            'customer_name'    => ['label' => 'Full Name',              'step' => 1, 'default' => 'required', 'always' => true],
            'customer_email'   => ['label' => 'Email',                  'step' => 1, 'default' => 'required', 'always' => true],
            'customer_phone'   => ['label' => 'Phone',                  'step' => 1, 'default' => 'visible',  'always' => false],
            'customer_company' => ['label' => 'Company',                'step' => 1, 'default' => 'visible',  'always' => false],
            'business_abn'     => ['label' => 'Company Reg / VAT',      'step' => 1, 'default' => 'hidden',   'always' => false],
            'billing_contact'  => ['label' => 'Billing Contact',        'step' => 1, 'default' => 'hidden',   'always' => false],
            'billing_email'    => ['label' => 'Billing Email',          'step' => 1, 'default' => 'hidden',   'always' => false],
            'purchase_order'   => ['label' => 'Purchase Order',         'step' => 1, 'default' => 'hidden',   'always' => false],
            'heard_about'      => ['label' => 'How Did You Hear',       'step' => 1, 'default' => 'visible',  'always' => false],

            // Step 2: Job Brief
            'job_type'             => ['label' => 'Job Type',            'step' => 2, 'default' => 'required', 'always' => true],
            'urgency'              => ['label' => 'Urgency',             'step' => 2, 'default' => 'hidden',   'always' => false],
            'job_description'      => ['label' => 'Job Description',     'step' => 2, 'default' => 'required', 'always' => true],
            'preferred_dates'      => ['label' => 'Preferred Dates',     'step' => 2, 'default' => 'visible',  'always' => false],
            'time_window'          => ['label' => 'Time Window',         'step' => 2, 'default' => 'visible',  'always' => false],
            'special_requirements' => ['label' => 'Special Requirements','step' => 2, 'default' => 'visible',  'always' => false],
            'attachments'          => ['label' => 'Reference Files',     'step' => 2, 'default' => 'visible',  'always' => false],

            // Step 3: Location
            'location_address' => ['label' => 'Location',      'step' => 3, 'default' => 'required', 'always' => true],
            'area_polygon'     => ['label' => 'Area Boundary',  'step' => 3, 'default' => 'visible',  'always' => false],

            // Step 4: Preferences
            'altitude_preset'   => ['label' => 'Altitude',            'step' => 4, 'default' => 'visible',  'always' => false],
            'camera_angle'      => ['label' => 'Camera Angle',        'step' => 4, 'default' => 'hidden',   'always' => false],
            'video_resolution'  => ['label' => 'Video Resolution',    'step' => 4, 'default' => 'hidden',   'always' => false],
            'photo_mode'        => ['label' => 'Photo Mode',          'step' => 4, 'default' => 'hidden',   'always' => false],
            'no_fly_notes'      => ['label' => 'Nearby Restrictions', 'step' => 4, 'default' => 'hidden',   'always' => false],
            'privacy_notes'     => ['label' => 'Privacy Notes',       'step' => 4, 'default' => 'hidden',   'always' => false],
            'footage_purpose'   => ['label' => 'Footage Purpose',     'step' => 4, 'default' => 'visible',  'always' => false],
            'output_format'     => ['label' => 'Output Format',       'step' => 4, 'default' => 'visible',  'always' => false],
            'video_duration'    => ['label' => 'Video Duration',      'step' => 4, 'default' => 'hidden',   'always' => false],
            'shot_types'        => ['label' => 'Shot Types',          'step' => 4, 'default' => 'hidden',   'always' => false],
            'delivery_timeline' => ['label' => 'Delivery Timeline',   'step' => 4, 'default' => 'visible',  'always' => false],
        ];
    }

    /**
     * Get fields grouped by step number.
     */
    public static function getFieldsByStep(): array
    {
        $grouped = [];
        foreach (self::getFields() as $name => $def) {
            $grouped[$def['step']][$name] = $def;
        }
        return $grouped;
    }

    /**
     * Get always-required field names.
     */
    public static function getAlwaysRequired(): array
    {
        return array_keys(array_filter(self::getFields(), fn($d) => $d['always']));
    }
}
