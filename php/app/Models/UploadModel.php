<?php

namespace App\Models;

use CodeIgniter\Model;

class UploadModel extends Model
{
    protected $table = 'uploads';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $allowedFields = [
        'flight_plan_id', 'original_filename', 'stored_filename',
        'file_size', 'mime_type',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = '';
}
