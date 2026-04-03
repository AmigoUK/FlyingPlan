<?php

namespace App\Models;

use CodeIgniter\Model;

class OrderActivityModel extends Model
{
    protected $table = 'order_activities';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $allowedFields = [
        'order_id', 'user_id', 'action', 'old_value', 'new_value', 'details',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = '';
}
