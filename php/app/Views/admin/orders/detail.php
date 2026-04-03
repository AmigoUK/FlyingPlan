<?= $this->extend('layouts/base') ?>
<?= $this->section('title') ?>Order #<?= $order->id ?><?= $this->endSection() ?>

<?= $this->section('head_extra') ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <a href="<?= site_url('orders') ?>" class="btn btn-sm btn-outline-secondary me-2"><i class="bi bi-arrow-left"></i> Orders</a>
        <code class="fs-5"><?= esc($flight_plan->reference) ?></code>
        <span class="badge badge-<?= $order->status ?> ms-2"><?= ucwords(str_replace('_', ' ', $order->status)) ?></span>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-5">
        <!-- Order Info -->
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-clipboard2-check"></i> Order Details</div>
            <div class="card-body">
                <p class="mb-1"><strong>Customer:</strong> <?= esc($flight_plan->customer_name) ?></p>
                <p class="mb-1"><strong>Job Type:</strong> <?= ucwords(str_replace('_', ' ', $flight_plan->job_type)) ?></p>
                <p class="mb-1"><strong>Status:</strong> <span class="badge badge-<?= $order->status ?>"><?= ucwords(str_replace('_', ' ', $order->status)) ?></span></p>
                <?php if ($order->scheduled_date): ?>
                <p class="mb-1"><strong>Scheduled:</strong> <?= date('d M Y', strtotime($order->scheduled_date)) ?> <?= $order->scheduled_time ? 'at ' . esc($order->scheduled_time) : '' ?></p>
                <?php endif; ?>
                <?php if ($order->assignment_notes): ?>
                <p class="mb-1"><strong>Notes:</strong> <?= esc($order->assignment_notes) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Status Update -->
        <?php if (!empty($valid_transitions)): ?>
        <div class="card mb-3">
            <div class="card-body">
                <form method="POST" action="<?= site_url('orders/' . $order->id . '/status') ?>">
                    <?= csrf_field() ?>
                    <div class="input-group">
                        <select name="status" class="form-select form-select-sm">
                            <?php foreach ($valid_transitions as $s): ?>
                            <option value="<?= $s ?>"><?= ucwords(str_replace('_', ' ', $s)) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-sm btn-primary">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- Assign/Reassign -->
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-person-check"></i> Pilot Assignment</div>
            <div class="card-body">
                <form method="POST" action="<?= site_url('orders/' . $order->id . '/assign') ?>">
                    <?= csrf_field() ?>
                    <div class="mb-2">
                        <select name="pilot_id" class="form-select form-select-sm" required>
                            <option value="">Select Pilot...</option>
                            <?php foreach ($pilots as $p): ?>
                            <option value="<?= $p->id ?>" <?= $order->pilot_id == $p->id ? 'selected' : '' ?>><?= esc($p->display_name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col"><input type="date" name="scheduled_date" class="form-control form-control-sm" value="<?= $order->scheduled_date ?>"></div>
                        <div class="col"><input type="text" name="scheduled_time" class="form-control form-control-sm" value="<?= esc($order->scheduled_time ?? '') ?>" placeholder="Time"></div>
                    </div>
                    <textarea name="assignment_notes" class="form-control form-control-sm mb-2" rows="2" placeholder="Notes..."><?= esc($order->assignment_notes ?? '') ?></textarea>
                    <button type="submit" class="btn btn-sm btn-primary w-100">Assign</button>
                </form>
            </div>
        </div>

        <!-- Deliverables -->
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-folder"></i> Deliverables (<?= count($deliverables) ?>)</div>
            <div class="card-body">
                <?php if (empty($deliverables)): ?>
                <p class="text-muted small mb-0">No deliverables uploaded yet.</p>
                <?php else: foreach ($deliverables as $d): ?>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <a href="<?= site_url('orders/' . $order->id . '/deliverables/' . $d->id . '/download') ?>"><?= esc($d->original_filename) ?></a>
                        <small class="text-muted">(<?= round($d->file_size / 1024, 1) ?>KB)</small>
                    </div>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </div>

        <!-- Risk Assessment -->
        <?php if ($risk_assessment): ?>
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-shield-check"></i> Risk Assessment</div>
            <div class="card-body">
                <p class="mb-1"><strong>Level:</strong> <span class="badge bg-<?= $risk_assessment->risk_level === 'low' ? 'success' : ($risk_assessment->risk_level === 'medium' ? 'warning' : 'danger') ?>"><?= ucfirst($risk_assessment->risk_level) ?></span></p>
                <p class="mb-1"><strong>Decision:</strong> <?= ucwords(str_replace('_', ' ', $risk_assessment->decision)) ?></p>
                <?php if ($risk_assessment->mitigation_notes): ?>
                <p class="mb-0"><strong>Mitigations:</strong> <?= esc($risk_assessment->mitigation_notes) ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Activity Log -->
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-clock-history"></i> Activity Log</div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush" style="max-height: 300px; overflow-y: auto;">
                    <?php foreach ($activities as $act): ?>
                    <div class="list-group-item py-2">
                        <div class="d-flex justify-content-between">
                            <strong><?= ucwords(str_replace('_', ' ', $act->action)) ?></strong>
                            <small class="text-muted"><?= date('d M H:i', strtotime($act->created_at)) ?></small>
                        </div>
                        <?php if ($act->user_name): ?><small class="text-muted">by <?= esc($act->user_name) ?></small><?php endif; ?>
                        <?php if ($act->details): ?><small class="d-block"><?= esc($act->details) ?></small><?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <?php if ($flight_plan->location_lat && $flight_plan->location_lng): ?>
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-map"></i> Location</span>
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" id="toggle-satellite">
                    <label class="form-check-label small" for="toggle-satellite">Satellite</label>
                </div>
            </div>
            <div class="card-body p-0">
                <input type="hidden" id="plan-lat" value="<?= $flight_plan->location_lat ?>">
                <input type="hidden" id="plan-lng" value="<?= $flight_plan->location_lng ?>">
                <input type="hidden" id="plan-polygon" value="<?= esc($flight_plan->area_polygon ?? '') ?>">
                <input type="hidden" id="plan-pois" value='<?= $pois_json ?>'>
                <input type="hidden" id="plan-waypoints" value='<?= $waypoints_json ?>'>
                <div id="pilot-map" style="height: 300px; border-radius: 0 0 8px 8px;"></div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<?php if ($flight_plan->location_lat && $flight_plan->location_lng): ?>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="<?= base_url('static/js/map-pilot.js') ?>"></script>
<?php endif; ?>
<?= $this->endSection() ?>
