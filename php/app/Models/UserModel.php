<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $allowedFields = [
        'username', 'display_name', 'password_hash', 'is_active_user', 'role',
        'email', 'phone', 'flying_id', 'operator_id', 'flying_id_expiry',
        'operator_id_expiry', 'insurance_provider', 'insurance_policy_no',
        'insurance_expiry', 'availability_status', 'pilot_bio',
        'a2_cofc_expiry', 'a2_cofc_number', 'gvc_mr_expiry', 'gvc_fw_expiry',
        'gvc_level', 'gvc_cert_number', 'oa_type', 'oa_reference', 'oa_expiry',
        'practical_competency_date', 'mentor_examiner', 'article16_agreed',
        'article16_agreed_date', 'address_line1', 'address_line2',
        'address_city', 'address_county', 'address_postcode', 'address_country',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = '';

    private const ROLE_RANK = ['pilot' => 0, 'manager' => 1, 'admin' => 2];

    /**
     * Check if user has at least the given role level.
     */
    public static function hasRoleAtLeast(object $user, string $minimumRole): bool
    {
        $userRank = self::ROLE_RANK[$user->role] ?? -1;
        $minRank = self::ROLE_RANK[$minimumRole] ?? 999;
        return $userRank >= $minRank;
    }

    /**
     * Find user by username.
     */
    public function findByUsername(string $username): ?object
    {
        return $this->where('username', $username)->first();
    }
}
