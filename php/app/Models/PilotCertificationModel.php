<?php

namespace App\Models;

use CodeIgniter\Model;

class PilotCertificationModel extends Model
{
    protected $table = 'pilot_certifications';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $allowedFields = [
        'user_id', 'cert_name', 'issuing_body', 'cert_number',
        'issue_date', 'expiry_date',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = '';
}
