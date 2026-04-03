<?php

namespace App\Models;

use CodeIgniter\Model;

class OrderDeliverableModel extends Model
{
    protected $table = 'order_deliverables';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $allowedFields = [
        'order_id', 'uploaded_by_id', 'original_filename', 'stored_filename',
        'file_size', 'mime_type', 'description',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = '';

    public const ALLOWED_EXTENSIONS = [
        'png', 'jpg', 'jpeg', 'gif', 'mp4', 'mov', 'avi', 'pdf', 'zip', 'tiff', 'tif',
    ];
}
