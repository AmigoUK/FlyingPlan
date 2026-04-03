<?php

namespace App\Models;

use CodeIgniter\Model;

class PilotDocumentModel extends Model
{
    protected $table = 'pilot_documents';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $allowedFields = [
        'user_id', 'doc_type', 'label', 'original_filename', 'stored_filename',
        'file_size', 'mime_type', 'expiry_date',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = '';

    public const DOC_TYPES = ['certificate', 'insurance', 'license', 'other'];
}
