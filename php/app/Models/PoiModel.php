<?php

namespace App\Models;

use CodeIgniter\Model;

class PoiModel extends Model
{
    protected $table = 'pois';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $allowedFields = [
        'flight_plan_id', 'lat', 'lng', 'label', 'sort_order',
    ];
}
