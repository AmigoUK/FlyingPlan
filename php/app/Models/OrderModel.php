<?php

namespace App\Models;

use CodeIgniter\Model;

class OrderModel extends Model
{
    protected $table = 'orders';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $allowedFields = [
        'flight_plan_id', 'pilot_id', 'assigned_by_id', 'status',
        'scheduled_date', 'scheduled_time', 'assignment_notes', 'pilot_notes',
        'completion_notes', 'decline_reason', 'risk_assessment_completed',
        'assigned_at', 'accepted_at', 'started_at', 'completed_at',
        'delivered_at', 'closed_at', 'equipment_id', 'time_of_day',
        'proximity_to_people', 'environment_type', 'proximity_to_buildings',
        'airspace_type', 'vlos_type', 'speed_mode', 'operational_category',
        'category_determined_at', 'category_blockers',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    public const STATUSES = [
        'pending_assignment', 'assigned', 'accepted', 'in_progress',
        'flight_complete', 'delivered', 'closed', 'declined',
    ];

    public const ADMIN_VALID_TRANSITIONS = [
        'pending_assignment' => ['assigned'],
        'assigned'           => ['pending_assignment'],
        'accepted'           => [],
        'in_progress'        => [],
        'flight_complete'    => ['delivered'],
        'delivered'          => ['closed'],
        'closed'             => [],
        'declined'           => ['pending_assignment'],
    ];
}
