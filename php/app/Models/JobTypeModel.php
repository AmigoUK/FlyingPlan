<?php

namespace App\Models;

use CodeIgniter\Model;

class JobTypeModel extends Model
{
    protected $table = 'job_types';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $allowedFields = [
        'value', 'label', 'icon', 'category', 'is_active', 'sort_order',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = '';

    /**
     * Get all active job types ordered by sort_order.
     */
    public function getActive(): array
    {
        return $this->where('is_active', 1)->orderBy('sort_order')->findAll();
    }
}
