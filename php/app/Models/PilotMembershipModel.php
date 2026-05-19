<?php

namespace App\Models;

use CodeIgniter\Model;

class PilotMembershipModel extends Model
{
    protected $table = 'pilot_memberships';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $allowedFields = [
        'user_id', 'org_name', 'membership_number', 'membership_type', 'expiry_date',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = '';
}
