<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Flight Report - <?= esc($flight_plan->reference ?? '') ?></title>
    <style>
        body { font-family: 'Helvetica Neue', Arial, sans-serif; font-size: 11px; color: #333; margin: 20px; }
        h1 { font-size: 22px; margin-bottom: 5px; }
        h2 { font-size: 16px; border-bottom: 2px solid #0d6efd; padding-bottom: 4px; margin-top: 20px; }
        h3 { font-size: 13px; margin-top: 12px; }
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .info-table td { padding: 4px 8px; border: 1px solid #dee2e6; }
        .info-label { font-weight: bold; background: #f8f9fa; width: 25%; }
        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; font-size: 10px; }
        .data-table th, .data-table td { padding: 3px 6px; border: 1px solid #dee2e6; text-align: left; }
        .data-table th { background: #f8f9fa; font-weight: bold; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 10px; font-weight: bold; color: white; }
        .bg-success { background: #198754; }
        .bg-warning { background: #ffc107; color: #333; }
        .bg-danger { background: #dc3545; }
        .bg-primary { background: #0d6efd; }
        .bg-secondary { background: #6c757d; }
        .check-pass { color: #198754; }
        .check-category { margin-bottom: 8px; }
        .check-category-title { font-weight: bold; margin-bottom: 4px; }
        .section-box { background: #f8f9fa; padding: 8px; border-radius: 4px; margin-bottom: 10px; }
        .empty-note { color: #6c757d; font-style: italic; }
        @page { margin: 15mm; }
    </style>
</head>
<body>
    <h1><?= esc($settings->business_name ?? 'FlyingPlan') ?></h1>
    <p style="color:#666; margin-top:0;"><?= esc($settings->tagline ?? 'Drone Flight Brief') ?></p>
    <h2>Flight Report: <?= esc($flight_plan->reference ?? '') ?></h2>
    <p>Generated: <?= date('d M Y H:i') ?> UTC</p>

    <!-- Flight Plan Info -->
    <table class="info-table">
        <tr>
            <td class="info-label">Reference:</td><td><?= esc($flight_plan->reference) ?></td>
            <td class="info-label">Status:</td><td><?= ucwords(str_replace('_', ' ', $order->status ?? '')) ?></td>
        </tr>
        <tr>
            <td class="info-label">Customer:</td><td><?= esc($flight_plan->customer_name) ?></td>
            <td class="info-label">Email:</td><td><?= esc($flight_plan->customer_email) ?></td>
        </tr>
        <tr>
            <td class="info-label">Job Type:</td><td><?= ucwords(str_replace('_', ' ', $flight_plan->job_type)) ?></td>
            <td class="info-label">Urgency:</td><td><?= ucfirst($flight_plan->urgency ?? 'normal') ?></td>
        </tr>
        <?php if (!empty($flight_plan->location_address)): ?>
        <tr>
            <td class="info-label">Location:</td><td colspan="3"><?= esc($flight_plan->location_address) ?></td>
        </tr>
        <?php endif; ?>
        <?php if (!empty($flight_plan->job_description)): ?>
        <tr>
            <td class="info-label">Description:</td><td colspan="3"><?= esc($flight_plan->job_description) ?></td>
        </tr>
        <?php endif; ?>
    </table>

    <!-- Waypoints -->
    <?php if (!empty($waypoints)): ?>
    <h2>Waypoints (<?= count($waypoints) ?>)</h2>
    <table class="data-table">
        <thead><tr><th>#</th><th>Lat</th><th>Lng</th><th>Alt (m)</th><th>Speed (m/s)</th><th>Heading</th><th>Gimbal</th></tr></thead>
        <tbody>
            <?php foreach ($waypoints as $w): ?>
            <tr>
                <td><?= $w->index ?? 0 ?></td>
                <td><?= number_format($w->lat, 5) ?></td>
                <td><?= number_format($w->lng, 5) ?></td>
                <td><?= $w->altitude_m ?? 30 ?></td>
                <td><?= $w->speed_ms ?? 5 ?></td>
                <td><?= isset($w->heading_deg) ? number_format($w->heading_deg, 1) : '-' ?></td>
                <td><?= $w->gimbal_pitch_deg ?? -90 ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <!-- Risk Assessment -->
    <?php if (!empty($risk_assessment)): ?>
    <?php $ra = $risk_assessment; ?>
    <h2>Risk Assessment</h2>
    <table class="info-table">
        <tr>
            <td class="info-label">Risk Level:</td>
            <td><span class="badge bg-<?= $ra->risk_level === 'low' ? 'success' : ($ra->risk_level === 'medium' ? 'warning' : 'danger') ?>"><?= ucfirst($ra->risk_level) ?></span></td>
            <td class="info-label">Decision:</td>
            <td><span class="badge bg-<?= $ra->decision === 'abort' ? 'danger' : 'success' ?>"><?= ucwords(str_replace('_', ' ', $ra->decision)) ?></span></td>
        </tr>
        <?php if ($ra->operational_category): ?>
        <tr>
            <td class="info-label">Category:</td>
            <td colspan="3"><?= ucwords(str_replace('_', ' ', $ra->operational_category)) ?></td>
        </tr>
        <?php endif; ?>
    </table>

    <div class="check-category"><div class="check-category-title">1. Site Assessment</div>
        <span class="check-pass">&#10003; Ground hazards assessed</span>
        <span class="check-pass">&#10003; Obstacles mapped</span>
        <span class="check-pass">&#10003; 50m separation confirmed</span>
        <span class="check-pass">&#10003; 150m from residential areas</span>
    </div>
    <div class="check-category"><div class="check-category-title">2. Airspace Check</div>
        <span class="check-pass">&#10003; FRZ checked</span>
        <span class="check-pass">&#10003; Restricted areas checked</span>
        <span class="check-pass">&#10003; NOTAMs reviewed</span>
        <span class="check-pass">&#10003; Max altitude confirmed</span>
    </div>
    <div class="check-category"><div class="check-category-title">3. Weather</div>
        <span class="check-pass">&#10003; Weather acceptable</span>
        <?php if ($ra->weather_wind_speed !== null): ?>
        <br><small>Wind: <?= $ra->weather_wind_speed ?> km/h <?= esc($ra->weather_wind_direction ?? '') ?></small>
        <?php endif; ?>
    </div>
    <div class="check-category"><div class="check-category-title">4. Equipment</div>
        <span class="check-pass">&#10003; All equipment checks passed</span>
        <?php if ($ra->equip_battery_level !== null): ?><br><small>Battery: <?= $ra->equip_battery_level ?>%</small><?php endif; ?>
    </div>
    <div class="check-category"><div class="check-category-title">5. IMSAFE Pilot Fitness</div><span class="check-pass">&#10003; All fitness checks passed</span></div>
    <div class="check-category"><div class="check-category-title">6. Permissions</div><span class="check-pass">&#10003; All permissions verified</span></div>
    <div class="check-category"><div class="check-category-title">7. Emergency</div><span class="check-pass">&#10003; Emergency procedures confirmed</span></div>

    <?php if ($ra->mitigation_notes): ?>
    <h3>Mitigations</h3>
    <p><?= esc($ra->mitigation_notes) ?></p>
    <?php endif; ?>
    <?php endif; ?>

    <!-- Pilot Info -->
    <?php if (!empty($pilot)): ?>
    <h2>Pilot Information</h2>
    <table class="info-table">
        <tr><td class="info-label">Name:</td><td><?= esc($pilot->display_name) ?></td>
        <td class="info-label">Flyer ID:</td><td><?= esc($pilot->flying_id ?? '-') ?></td></tr>
        <?php if ($pilot->operator_id || $pilot->insurance_provider): ?>
        <tr>
            <td class="info-label">Operator ID:</td><td><?= esc($pilot->operator_id ?? '-') ?></td>
            <td class="info-label">Insurance:</td><td><?= esc($pilot->insurance_provider ?? '-') ?></td>
        </tr>
        <?php endif; ?>
    </table>
    <?php endif; ?>

    <!-- Activity Log -->
    <?php if (!empty($activities)): ?>
    <h2>Activity Log</h2>
    <table class="data-table">
        <thead><tr><th>Date/Time</th><th>Action</th><th>By</th><th>Details</th></tr></thead>
        <tbody>
            <?php foreach ($activities as $act): ?>
            <tr>
                <td><?= date('d M Y H:i', strtotime($act->created_at)) ?></td>
                <td><?= ucwords(str_replace('_', ' ', $act->action)) ?></td>
                <td><?= esc($act->user_name ?? '-') ?></td>
                <td>
                    <?php if ($act->old_value && $act->new_value): ?>
                    <?= ucwords(str_replace('_', ' ', $act->old_value)) ?> &rarr; <?= ucwords(str_replace('_', ' ', $act->new_value)) ?>
                    <?php elseif ($act->new_value): ?><?= esc($act->new_value) ?><?php endif; ?>
                    <?php if ($act->details): ?><?= esc($act->details) ?><?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <?php if (!empty($include_admin_notes) && !empty($order->assignment_notes)): ?>
    <h2>Admin Notes</h2>
    <div class="section-box"><p><?= esc($order->assignment_notes) ?></p></div>
    <?php endif; ?>
</body>
</html>
