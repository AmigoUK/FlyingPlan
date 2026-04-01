<?= $this->extend('layouts/base') ?>

<?= $this->section('title') ?>Order #<?= esc($order->id) ?><?= $this->endSection() ?>

<?= $this->section('head_extra') ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <a href="<?= site_url('pilot') ?>" class="btn btn-sm btn-outline-secondary me-2">
            <i class="bi bi-arrow-left"></i> Dashboard
        </a>
        <strong class="fs-5">Order #<?= esc($order->id) ?></strong>
        <span class="badge badge-<?= esc($order->status) ?> ms-2">
            <?= ucwords(str_replace('_', ' ', $order->status)) ?>
        </span>
    </div>
    <div>
        <a href="<?= site_url('pilot/orders/' . $order->id . '/report-pdf') ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-file-earmark-pdf"></i> Download Report
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Left: Info & Actions -->
    <div class="col-lg-5">
        <!-- Actions -->
        <?php if ($order->status == 'assigned'): ?>
        <div class="card mb-3 border-primary">
            <div class="card-body">
                <h6><i class="bi bi-bell"></i> Action Required</h6>
                <p class="small mb-2">You've been assigned this order. Accept or decline?</p>
                <div class="d-flex gap-2">
                    <form method="POST" action="<?= site_url('pilot/orders/' . $order->id . '/accept') ?>">
                        <?= csrf_field() ?>
                        <button class="btn btn-success"><i class="bi bi-check-lg"></i> Accept</button>
                    </form>
                    <button class="btn btn-outline-danger" data-bs-toggle="collapse" data-bs-target="#declineForm">
                        <i class="bi bi-x-lg"></i> Decline
                    </button>
                </div>
                <div class="collapse mt-2" id="declineForm">
                    <form method="POST" action="<?= site_url('pilot/orders/' . $order->id . '/decline') ?>">
                        <?= csrf_field() ?>
                        <textarea name="reason" class="form-control form-control-sm mb-2"
                                  placeholder="Reason for declining..." rows="2"></textarea>
                        <button class="btn btn-sm btn-danger">Confirm Decline</button>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($order->status == 'accepted'): ?>
        <!-- Flight Parameters + Category -->
        <?php if (empty($order->operational_category)): ?>
        <div class="card mb-3 border-info">
            <div class="card-body">
                <h6><i class="bi bi-sliders text-info"></i> Set Flight Parameters</h6>
                <p class="small mb-2">Select your drone and set flight parameters to determine the operational category before proceeding.</p>
                <a href="<?= site_url('pilot/orders/' . $order->id . '/flight-params') ?>" class="btn btn-sm btn-info">
                    <i class="bi bi-sliders"></i> Set Flight Parameters
                </a>
            </div>
        </div>
        <?php else: ?>
        <!-- Category Determined -->
        <div class="card mb-3 border-<?= !empty($order->category_blockers) ? 'danger' : 'success' ?>">
            <div class="card-body">
                <h6>
                    <i class="bi bi-shield-check"></i> Category:
                    <?php
                        if (strpos($order->operational_category, 'open_') === 0) {
                            $cat_class = 'success';
                        } elseif (strpos($order->operational_category, 'specific_') === 0) {
                            $cat_class = 'primary';
                        } else {
                            $cat_class = 'danger';
                        }
                    ?>
                    <span class="badge bg-<?= $cat_class ?>"><?= ucwords(str_replace('_', ' ', $order->operational_category)) ?></span>
                </h6>
                <?php if (!empty($order->equipment)): ?>
                <p class="small mb-1"><strong>Equipment:</strong> <?= esc($order->equipment->drone_model) ?>
                    <?php if (!empty($order->equipment->class_mark)): ?>(<?= esc($order->equipment->class_mark) ?>)<?php endif; ?>
                </p>
                <?php endif; ?>
                <?php if (!empty($order->category_blockers)): ?>
                <div class="alert alert-danger py-1 small mb-2">
                    <i class="bi bi-x-circle"></i> There are blockers preventing this flight. Resolve them or change parameters.
                </div>
                <?php endif; ?>
                <div class="d-flex gap-2">
                    <a href="<?= site_url('pilot/orders/' . $order->id . '/flight-params') ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-pencil"></i> Edit Parameters
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Risk Assessment CTA -->
        <?php if (!empty($order->risk_assessment) && $order->risk_assessment->decision == 'abort'): ?>
        <div class="card mb-3 border-danger">
            <div class="card-body">
                <h6><i class="bi bi-x-circle text-danger"></i> Flight Aborted</h6>
                <p class="small mb-2 text-danger">Risk assessment completed with abort decision. This flight cannot proceed.</p>
                <a href="<?= site_url('pilot/orders/' . $order->id . '/risk-assessment') ?>" class="btn btn-sm btn-outline-danger">
                    <i class="bi bi-eye"></i> View Assessment
                </a>
            </div>
        </div>
        <?php elseif (!empty($order->operational_category)): ?>
        <div class="card mb-3 border-<?= !empty($order->risk_assessment_completed) ? 'success' : 'warning' ?>">
            <div class="card-body">
                <?php if (!empty($order->risk_assessment_completed)): ?>
                <h6><i class="bi bi-check-circle text-success"></i> Pre-Flight Risk Assessment</h6>
                <p class="small mb-2 text-success">Assessment completed. You may start the flight.</p>
                <a href="<?= site_url('pilot/orders/' . $order->id . '/risk-assessment') ?>" class="btn btn-sm btn-outline-success">
                    <i class="bi bi-eye"></i> View Assessment
                </a>
                <?php elseif (!empty($order->category_blockers)): ?>
                <h6><i class="bi bi-lock text-muted"></i> Pre-Flight Risk Assessment</h6>
                <p class="small mb-2 text-muted">Resolve category blockers before starting the risk assessment.</p>
                <button class="btn btn-sm btn-secondary" disabled>
                    <i class="bi bi-lock"></i> Risk Assessment Locked
                </button>
                <?php else: ?>
                <h6><i class="bi bi-exclamation-triangle text-warning"></i> Pre-Flight Risk Assessment Required</h6>
                <p class="small mb-2">You must complete the on-site risk assessment before starting this flight.</p>
                <a href="<?= site_url('pilot/orders/' . $order->id . '/risk-assessment') ?>" class="btn btn-sm btn-warning">
                    <i class="bi bi-clipboard-check"></i> Complete Risk Assessment
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>

        <?php if (!in_array($order->status, ['accepted', 'assigned', 'declined', 'closed']) && !empty($order->risk_assessment)): ?>
        <div class="card mb-3">
            <div class="card-body">
                <h6><i class="bi bi-check-circle text-success"></i> Risk Assessment</h6>
                <a href="<?= site_url('pilot/orders/' . $order->id . '/risk-assessment') ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-eye"></i> View Assessment
                </a>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($allowed_next) && !in_array($order->status, ['assigned', 'declined', 'closed'])): ?>
        <div class="card mb-3">
            <div class="card-body">
                <h6><i class="bi bi-arrow-right-circle"></i> Update Status</h6>
                <?php foreach ($allowed_next as $next_status): ?>
                <?php if ($next_status == 'in_progress' && (empty($order->risk_assessment_completed) || (!empty($order->risk_assessment) && $order->risk_assessment->decision == 'abort'))): ?>
                <button class="btn btn-sm btn-outline-secondary me-1 mb-1" disabled
                        title="<?= (!empty($order->risk_assessment) && $order->risk_assessment->decision == 'abort') ? 'Flight aborted in risk assessment' : 'Complete risk assessment first' ?>">
                    <i class="bi bi-lock"></i> In Progress
                </button>
                <?php else: ?>
                <form method="POST" action="<?= site_url('pilot/orders/' . $order->id . '/update-status') ?>" class="d-inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="status" value="<?= esc($next_status) ?>">
                    <button class="btn btn-sm btn-outline-primary me-1 mb-1">
                        <?= ucwords(str_replace('_', ' ', $next_status)) ?>
                    </button>
                </form>
                <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Flight Plan Info -->
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-person"></i> Customer</div>
            <div class="card-body">
                <p class="mb-1"><strong><?= esc($flight_plan->customer_name) ?></strong></p>
                <?php if (!empty($flight_plan->customer_email)): ?>
                <p class="mb-1"><a href="mailto:<?= esc($flight_plan->customer_email) ?>"><?= esc($flight_plan->customer_email) ?></a></p>
                <?php endif; ?>
                <?php if (!empty($flight_plan->customer_phone)): ?>
                <p class="mb-1"><a href="tel:<?= esc($flight_plan->customer_phone) ?>"><?= esc($flight_plan->customer_phone) ?></a></p>
                <?php endif; ?>
                <?php if (!empty($flight_plan->customer_company)): ?>
                <p class="mb-0 text-muted"><?= esc($flight_plan->customer_company) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-clipboard-check"></i> Job Brief</div>
            <div class="card-body">
                <p class="mb-1"><strong>Reference:</strong> <code><?= esc($flight_plan->reference) ?></code></p>
                <p class="mb-1"><strong>Type:</strong> <?= ucwords(str_replace('_', ' ', $flight_plan->job_type)) ?></p>
                <?php if (!empty($flight_plan->job_description)): ?>
                <p class="mb-1"><strong>Description:</strong> <?= esc($flight_plan->job_description) ?></p>
                <?php endif; ?>
                <?php if (!empty($order->scheduled_date)): ?>
                <p class="mb-1"><strong>Scheduled:</strong> <?= date('d M Y', strtotime($order->scheduled_date)) ?>
                    <?php if (!empty($order->scheduled_time)): ?> at <?= esc($order->scheduled_time) ?><?php endif; ?>
                </p>
                <?php endif; ?>
                <?php if (!empty($flight_plan->location_address)): ?>
                <p class="mb-1"><strong>Location:</strong> <?= esc($flight_plan->location_address) ?></p>
                <?php endif; ?>
                <?php if (!empty($order->assignment_notes)): ?>
                <p class="mb-0"><strong>Assignment Notes:</strong> <?= esc($order->assignment_notes) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-sliders"></i> Flight Preferences</div>
            <div class="card-body">
                <p class="mb-1"><strong>Altitude:</strong>
                    <?php if ($flight_plan->altitude_preset == 'custom'): ?>
                    Custom (<?= esc($flight_plan->altitude_custom_m) ?>m)
                    <?php else: ?>
                    <?= !empty($flight_plan->altitude_preset) ? ucfirst($flight_plan->altitude_preset) : 'Not set' ?>
                    <?php endif; ?>
                </p>
                <p class="mb-1"><strong>Camera:</strong> <?= !empty($flight_plan->camera_angle) ? ucwords(str_replace('_', ' ', $flight_plan->camera_angle)) : 'Pilot Decides' ?></p>
                <p class="mb-1"><strong>Resolution:</strong> <?= esc($flight_plan->video_resolution ?: '4K') ?></p>
                <?php if (!empty($flight_plan->special_requirements)): ?>
                <p class="mb-0"><strong>Special:</strong> <?= esc($flight_plan->special_requirements) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Weather -->
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-cloud-sun"></i> Weather</span>
                <button class="btn btn-sm btn-outline-secondary" id="btn-load-weather"
                        onclick="fetch('<?= site_url('pilot/orders/' . $order->id . '/weather') ?>').then(function(r){return r.json()}).then(function(d){if(typeof WeatherPanel!=='undefined')WeatherPanel.render('weather-panel',d)})">
                    <i class="bi bi-arrow-repeat"></i> Refresh
                </button>
            </div>
            <div class="card-body" id="weather-panel">
                <p class="text-muted small mb-0">Click "Refresh" to load live weather.</p>
            </div>
        </div>

        <!-- Pilot Notes -->
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-journal-text"></i> My Notes</div>
            <div class="card-body">
                <form method="POST" action="<?= site_url('pilot/orders/' . $order->id . '/save-notes') ?>">
                    <?= csrf_field() ?>
                    <textarea name="pilot_notes" class="form-control form-control-sm" rows="3"
                              placeholder="Your notes..."><?= esc($order->pilot_notes ?? '') ?></textarea>
                    <button type="submit" class="btn btn-sm btn-outline-primary mt-2">
                        <i class="bi bi-save"></i> Save Notes
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Right: Map, Deliverables, Activity -->
    <div class="col-lg-7">
        <!-- Map -->
        <?php if (!empty($flight_plan->location_lat) && !empty($flight_plan->location_lng)): ?>
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-map"></i> Location</span>
                <div class="d-flex align-items-center gap-2">
                    <a href="<?= site_url('pilot/orders/' . $order->id . '/export-kmz') ?>" class="btn btn-sm btn-outline-success">
                        <i class="bi bi-download"></i> Export KMZ
                    </a>
                    <button class="btn btn-sm btn-outline-info" id="btn-ruler" title="Measure distance">
                        <i class="bi bi-rulers"></i>
                    </button>
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" id="toggle-satellite">
                        <label class="form-check-label small" for="toggle-satellite">Satellite</label>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <input type="hidden" id="plan-lat" value="<?= esc($flight_plan->location_lat) ?>">
                <input type="hidden" id="plan-lng" value="<?= esc($flight_plan->location_lng) ?>">
                <input type="hidden" id="plan-polygon" value="<?= esc($flight_plan->area_polygon ?? '') ?>">
                <input type="hidden" id="plan-pois" value='<?= $pois_json ?>'>
                <input type="hidden" id="plan-waypoints" value='<?= $waypoints_json ?>'>
                <input type="hidden" id="waypoints-save-url" value="<?= site_url('pilot/orders/' . $order->id . '/waypoints') ?>">
                <div id="pilot-map" style="height: 350px; border-radius: 0 0 8px 8px;"></div>
            </div>
            <?php if (in_array($order->status, ['accepted', 'in_progress'])): ?>
            <div class="card-footer">
                <div id="route-stats" class="mb-2"></div>
                <div class="d-flex gap-2 mb-2">
                    <button id="btn-save-waypoints" class="btn btn-sm btn-success">
                        <i class="bi bi-save"></i> Save Waypoints
                    </button>
                    <button id="btn-clear-waypoints" class="btn btn-sm btn-outline-danger">
                        <i class="bi bi-trash"></i> Clear All
                    </button>
                </div>
                <div id="waypoint-list"></div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Deliverables -->
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between">
                <span><i class="bi bi-file-earmark-arrow-up"></i> Deliverables</span>
                <button class="btn btn-sm btn-outline-success" data-bs-toggle="collapse" data-bs-target="#uploadDeliv">
                    <i class="bi bi-upload"></i> Upload
                </button>
            </div>
            <div class="collapse" id="uploadDeliv">
                <div class="card-body border-bottom">
                    <form method="POST" action="<?= site_url('pilot/orders/' . $order->id . '/upload-deliverable') ?>" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <input type="file" name="file" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-md-4">
                                <input type="text" name="description" class="form-control form-control-sm"
                                       placeholder="Description">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-sm btn-success w-100">Upload</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <?php if (!empty($deliverables)): ?>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>File</th><th>Size</th><th>Date</th><th></th></tr></thead>
                    <tbody>
                        <?php foreach ($deliverables as $d): ?>
                        <tr>
                            <td>
                                <?= esc($d->original_filename) ?>
                                <?php if (!empty($d->description)): ?>
                                <br><small class="text-muted"><?= esc($d->description) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?= round($d->file_size / 1024, 1) ?>KB</td>
                            <td><?= date('d M H:i', strtotime($d->created_at)) ?></td>
                            <td>
                                <form method="POST" action="<?= site_url('pilot/orders/' . $order->id . '/deliverables/' . $d->id . '/delete') ?>" class="d-inline">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="card-body text-muted small">No deliverables uploaded yet.</div>
            <?php endif; ?>
        </div>

        <!-- Activity Log -->
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-clock-history"></i> Activity Log</div>
            <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                <?php if (!empty($activities)): ?>
                <?php foreach ($activities as $act): ?>
                <div class="d-flex mb-2">
                    <div class="me-2">
                        <i class="bi bi-circle-fill text-primary" style="font-size: 0.5rem;"></i>
                    </div>
                    <div>
                        <strong class="small"><?= ucwords(str_replace('_', ' ', $act->action)) ?></strong>
                        <?php if (!empty($act->user)): ?>
                        <span class="small text-muted">by <?= esc($act->user->display_name) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($act->old_value) && !empty($act->new_value)): ?>
                        <span class="small text-muted">
                            <?= ucwords(str_replace('_', ' ', $act->old_value)) ?> &rarr; <?= ucwords(str_replace('_', ' ', $act->new_value)) ?>
                        </span>
                        <?php elseif (!empty($act->new_value)): ?>
                        <span class="small text-muted"><?= esc($act->new_value) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($act->details)): ?>
                        <br><small class="text-muted"><?= esc($act->details) ?></small>
                        <?php endif; ?>
                        <br><small class="text-muted"><?= date('d M Y H:i', strtotime($act->created_at)) ?></small>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php else: ?>
                <p class="text-muted small">No activity yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<?php if (!empty($flight_plan->location_lat) && !empty($flight_plan->location_lng)): ?>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="<?= base_url('static/js/weather-panel.js') ?>"></script>
<script src="<?= base_url('static/js/map-measure.js') ?>"></script>
<?php if (in_array($order->status, ['accepted', 'in_progress'])): ?>
<script src="<?= base_url('static/js/map-pilot-edit.js') ?>"></script>
<?php else: ?>
<script src="<?= base_url('static/js/map-pilot.js') ?>"></script>
<?php endif; ?>
<?php endif; ?>
<?= $this->endSection() ?>
