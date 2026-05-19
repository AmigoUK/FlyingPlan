<?php

namespace App\Models;

use CodeIgniter\Model;

class PilotEquipmentModel extends Model
{
    protected $table = 'pilot_equipment';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $allowedFields = [
        'user_id', 'drone_model', 'serial_number', 'registration_id', 'notes',
        'is_active', 'class_mark', 'mtom_grams', 'has_camera', 'green_light_type',
        'green_light_weight_grams', 'has_low_speed_mode', 'remote_id_capable',
        'max_speed_ms', 'max_dimension_m',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = '';

    public const CLASS_MARKS = ['C0', 'C1', 'C2', 'C3', 'C4', 'legacy'];

    /**
     * Calculate effective MTOM including external green light weight.
     */
    public static function effectiveMtomGrams(object $equip): ?int
    {
        if ($equip->mtom_grams === null) {
            return null;
        }
        $extra = ($equip->green_light_type === 'external') ? ($equip->green_light_weight_grams ?? 0) : 0;
        return $equip->mtom_grams + $extra;
    }

    /**
     * Human-readable MTOM display.
     */
    public static function mtomDisplay(object $equip): string
    {
        $grams = self::effectiveMtomGrams($equip);
        if ($grams === null) {
            return 'N/A';
        }
        return $grams >= 1000
            ? number_format($grams / 1000, 2) . ' kg'
            : $grams . ' g';
    }
}
