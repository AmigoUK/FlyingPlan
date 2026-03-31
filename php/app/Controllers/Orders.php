<?php

namespace App\Controllers;

use App\Models\OrderModel;
use App\Models\OrderActivityModel;
use App\Models\FlightPlanModel;
use App\Models\UserModel;
use App\Models\WaypointModel;

class Orders extends BaseController
{
    private const ADMIN_VALID_TRANSITIONS = [
        'pending_assignment' => ['assigned', 'cancelled', 'closed'],
        'assigned'           => ['accepted', 'declined', 'pending_assignment', 'closed'],
        'accepted'           => ['in_progress', 'declined', 'assigned', 'closed'],
        'in_progress'        => ['flight_complete', 'closed'],
        'flight_complete'    => ['delivered', 'closed'],
        'delivered'          => ['closed'],
        'declined'           => ['assigned', 'pending_assignment', 'closed'],
        'closed'             => [],
    ];

    public function index()
    {
        $db = \Config\Database::connect();
        $builder = $db->table('orders o')
            ->select('o.*, fp.reference, fp.customer_name, fp.job_type, u.display_name as pilot_name')
            ->join('flight_plans fp', 'fp.id = o.flight_plan_id')
            ->join('users u', 'u.id = o.pilot_id', 'left');

        $statusFilter = $this->request->getGet('status');
        if ($statusFilter) $builder->where('o.status', $statusFilter);

        $pilotFilter = $this->request->getGet('pilot_id');
        if ($pilotFilter) $builder->where('o.pilot_id', $pilotFilter);

        $orders = $builder->orderBy('o.created_at', 'DESC')->get()->getResult();
        $pilots = $db->table('users')->where('role', 'pilot')->orderBy('display_name')->get()->getResult();

        return view('admin/orders/list', [
            'orders'        => $orders,
            'pilots'        => $pilots,
            'status_filter' => $statusFilter,
            'pilot_filter'  => $pilotFilter,
        ]);
    }

    public function create($planId)
    {
        $db = \Config\Database::connect();
        $fp = $db->table('flight_plans')->where('id', $planId)->get()->getRow();
        if (!$fp) throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();

        // Check if order already exists
        $existing = $db->table('orders')->where('flight_plan_id', $planId)->get()->getRow();
        if ($existing) {
            return redirect()->to('/orders/' . $existing->id)
                ->with('flash_warning', 'An order already exists for this flight plan.');
        }

        $orderModel = new OrderModel();
        $pilotId = $this->request->getPost('pilot_id') ?: null;
        $scheduledDate = $this->request->getPost('scheduled_date') ?: null;

        $status = $pilotId ? 'assigned' : 'pending_assignment';
        $now = date('Y-m-d H:i:s');

        $orderId = $orderModel->insert([
            'flight_plan_id'   => $planId,
            'pilot_id'         => $pilotId,
            'assigned_by_id'   => session('user_id'),
            'status'           => $status,
            'scheduled_date'   => $scheduledDate,
            'scheduled_time'   => $this->request->getPost('scheduled_time'),
            'assignment_notes' => $this->request->getPost('assignment_notes'),
            'assigned_at'      => $pilotId ? $now : null,
        ]);

        $this->logActivity($orderId, 'created');
        if ($pilotId) {
            $pilot = $db->table('users')->where('id', $pilotId)->get()->getRow();
            $this->logActivity($orderId, 'assigned', null, $pilot->display_name ?? '');
        }

        // Update flight plan status
        (new FlightPlanModel())->update($planId, ['status' => 'in_review']);

        return redirect()->to('/orders/' . $orderId)->with('flash_success', 'Order created.');
    }

    public function detail($orderId)
    {
        $db = \Config\Database::connect();
        $order = $db->table('orders')->where('id', $orderId)->get()->getRow();
        if (!$order) throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();

        $fp = $db->table('flight_plans')->where('id', $order->flight_plan_id)->get()->getRow();
        $waypoints = $db->table('waypoints')->where('flight_plan_id', $fp->id)->orderBy('`index`')->get()->getResult();
        $pois = $db->table('pois')->where('flight_plan_id', $fp->id)->orderBy('sort_order')->get()->getResult();
        $pilots = $db->table('users')->where('role', 'pilot')->orderBy('display_name')->get()->getResult();
        $activities = $db->table('order_activities oa')
            ->select('oa.*, u.display_name as user_name')
            ->join('users u', 'u.id = oa.user_id', 'left')
            ->where('oa.order_id', $orderId)
            ->orderBy('oa.created_at', 'DESC')
            ->get()->getResult();
        $deliverables = $db->table('order_deliverables')->where('order_id', $orderId)->get()->getResult();
        $riskAssessment = $db->table('risk_assessments')->where('order_id', $orderId)->get()->getRow();

        return view('admin/orders/detail', [
            'order'            => $order,
            'flight_plan'      => $fp,
            'waypoints'        => $waypoints,
            'pois'             => $pois,
            'pilots'           => $pilots,
            'activities'       => $activities,
            'deliverables'     => $deliverables,
            'risk_assessment'  => $riskAssessment,
            'waypoints_json'   => json_encode(array_map(fn($w) => WaypointModel::toArray($w), $waypoints)),
            'pois_json'        => json_encode(array_map(fn($p) => ['lat' => $p->lat, 'lng' => $p->lng, 'label' => $p->label], $pois)),
            'valid_transitions' => self::ADMIN_VALID_TRANSITIONS[$order->status] ?? [],
        ]);
    }

    public function assign($orderId)
    {
        $db = \Config\Database::connect();
        $order = $db->table('orders')->where('id', $orderId)->get()->getRow();
        if (!$order) throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();

        $pilotId = $this->request->getPost('pilot_id');
        if (empty($pilotId)) {
            return redirect()->to('/orders/' . $orderId)->with('flash_danger', 'Please select a pilot.');
        }

        $pilot = $db->table('users')->where('id', $pilotId)->get()->getRow();
        $now = date('Y-m-d H:i:s');

        $orderModel = new OrderModel();
        $orderModel->update($orderId, [
            'pilot_id'         => $pilotId,
            'assigned_by_id'   => session('user_id'),
            'status'           => 'assigned',
            'assigned_at'      => $now,
            'scheduled_date'   => $this->request->getPost('scheduled_date') ?: $order->scheduled_date,
            'scheduled_time'   => $this->request->getPost('scheduled_time') ?: $order->scheduled_time,
            'assignment_notes' => $this->request->getPost('assignment_notes') ?: $order->assignment_notes,
        ]);

        $this->logActivity($orderId, 'assigned', $order->status, 'assigned', $pilot->display_name ?? '');

        return redirect()->to('/orders/' . $orderId)
            ->with('flash_success', 'Order assigned to ' . ($pilot->display_name ?? 'pilot') . '.');
    }

    public function updateStatus($orderId)
    {
        $db = \Config\Database::connect();
        $order = $db->table('orders')->where('id', $orderId)->get()->getRow();
        if (!$order) throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();

        $newStatus = $this->request->getPost('status');
        $validTransitions = self::ADMIN_VALID_TRANSITIONS[$order->status] ?? [];

        if (!in_array($newStatus, $validTransitions)) {
            return redirect()->to('/orders/' . $orderId)
                ->with('flash_danger', "Cannot change status from {$order->status} to {$newStatus}.");
        }

        $update = ['status' => $newStatus];
        $now = date('Y-m-d H:i:s');

        $timestampMap = [
            'assigned' => 'assigned_at', 'accepted' => 'accepted_at',
            'in_progress' => 'started_at', 'flight_complete' => 'completed_at',
            'delivered' => 'delivered_at', 'closed' => 'closed_at',
        ];

        if (isset($timestampMap[$newStatus])) {
            $field = $timestampMap[$newStatus];
            if (empty($order->$field)) {
                $update[$field] = $now;
            }
        }

        (new OrderModel())->update($orderId, $update);
        $this->logActivity($orderId, 'status_changed', $order->status, $newStatus);

        $label = ucwords(str_replace('_', ' ', $newStatus));
        return redirect()->to('/orders/' . $orderId)->with('flash_success', "Status changed to {$label}.");
    }

    public function saveNotes($orderId)
    {
        $db = \Config\Database::connect();
        $order = $db->table('orders')->where('id', $orderId)->get()->getRow();
        if (!$order) throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();

        (new OrderModel())->update($orderId, [
            'assignment_notes' => $this->request->getPost('assignment_notes'),
        ]);

        $this->logActivity($orderId, 'note_added', null, null, 'Admin notes updated');
        return redirect()->to('/orders/' . $orderId)->with('flash_success', 'Notes saved.');
    }

    public function downloadDeliverable($orderId, $dId)
    {
        $db = \Config\Database::connect();
        $deliv = $db->table('order_deliverables')
            ->where('id', $dId)->where('order_id', $orderId)->get()->getRow();

        if (!$deliv) {
            return redirect()->to('/orders/' . $orderId)->with('flash_danger', 'Invalid deliverable.');
        }

        $dir = WRITEPATH . 'uploads/orders/' . $orderId . '/';
        $path = realpath($dir . $deliv->stored_filename);

        if (!$path || !str_starts_with($path, realpath($dir))) {
            return redirect()->to('/orders/' . $orderId)->with('flash_danger', 'File not found.');
        }

        return $this->response->download($path, null)->setFileName($deliv->original_filename);
    }

    public function reportPdf($orderId)
    {
        $db = \Config\Database::connect();
        $order = $db->table('orders')->where('id', $orderId)->get()->getRow();
        if (!$order) throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();

        $fp = $db->table('flight_plans')->where('id', $order->flight_plan_id)->get()->getRow();

        $pdf = \App\Services\PdfReport::generateReportPdf($orderId, true);
        return $this->response->setContentType('application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $fp->reference . '.pdf"')
            ->setBody($pdf);
    }

    // ── Helper ──────────────────────────────────────────────────

    private function logActivity(int $orderId, string $action, ?string $oldValue = null, ?string $newValue = null, ?string $details = null): void
    {
        (new OrderActivityModel())->insert([
            'order_id'  => $orderId,
            'user_id'   => session('user_id'),
            'action'    => $action,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'details'   => $details,
        ]);
    }
}
