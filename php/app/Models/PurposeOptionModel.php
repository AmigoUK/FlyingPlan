<?php

namespace App\Models;

use CodeIgniter\Model;

class PurposeOptionModel extends Model
{
    protected $table = 'purpose_options';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $allowedFields = [
        'value', 'label', 'icon', 'is_active', 'sort_order',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = '';

    public function getActive(): array
    {
        return $this->where('is_active', 1)->orderBy('sort_order')->findAll();
    }
}
