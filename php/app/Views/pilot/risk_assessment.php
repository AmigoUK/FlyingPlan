<?= $this->extend('layouts/base') ?>
<?= $this->section('title') ?>Risk Assessment - Order #<?= esc($order->id) ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
    $cat = $order->operational_category ?? '';
    $is_open = str_starts_with($cat, 'open_');
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <a href="<?= site_url('pilot/orders/' . $order->id) ?>" class="btn btn-sm btn-outline-secondary me-2">
            <i class="bi bi-arrow-left"></i> Back to Order
        </a>
        <strong class="fs-5">Pre-Flight Risk Assessment</strong>
        <span class="badge bg-secondary ms-2">Order #<?= esc($order->id) ?></span>
    </div>
    <?php if (!empty($risk_assessment)): ?>
    <span class="badge bg-success"><i class="bi bi-check-circle"></i> Completed</span>
    <?php endif; ?>
</div>

<!-- Category Info Banner -->
<?php if ($cat): ?>
<div class="alert <?= $is_open ? 'alert-secondary' : 'alert-info' ?> mb-3">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <i class="bi bi-<?= $is_open ? 'info-circle' : 'shield-check' ?>"></i>
            <?php if ($is_open): ?>
            <strong>Open Category (<?= esc(strtoupper(str_replace('open_', 'A', $cat))) ?>):</strong>
            This pre-flight checklist is good practice. A formal risk assessment is not legally required for Open category flights under Article 11.
            <?php else: ?>
            <strong>Specific Category:</strong>
            A documented risk assessment is legally required under Article 11 for Specific category operations.
            <?php endif; ?>
        </div>
        <span class="badge bg-<?= $is_open ? 'success' : 'primary' ?>"><?= esc(ucwords(str_replace('_', ' ', $cat))) ?></span>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($risk_assessment)): ?>
<!-- ── Read-Only View ──────────────────────────────────────────── -->
<div class="row g-4">
    <div class="col-lg-8">
        <div class="accordion" id="raReadonly">
            <!-- 1. Site Assessment -->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#roSite">
                        <i class="bi bi-geo-alt me-2"></i> 1. Site Assessment
                        <span class="badge bg-success ms-auto me-2">4/4</span>
                    </button>
                </h2>
                <div id="roSite" class="accordion-collapse collapse show" data-bs-parent="#raReadonly">
                    <div class="accordion-body">
                        <ul class="list-unstyled mb-0">
                            <li><i class="bi bi-check-circle-fill text-success"></i> Ground hazards assessed and area safe</li>
                            <li><i class="bi bi-check-circle-fill text-success"></i> Obstacles identified and mapped</li>
                            <li><i class="bi bi-check-circle-fill text-success"></i> 50m separation from uninvolved persons confirmed</li>
                            <li><i class="bi bi-check-circle-fill text-success"></i> 150m from residential/commercial/industrial areas confirmed</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- 2. Airspace Check -->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#roAirspace">
                        <i class="bi bi-broadcast me-2"></i> 2. Airspace Check
                        <span class="badge bg-success ms-auto me-2">4/4</span>
                    </button>
                </h2>
                <div id="roAirspace" class="accordion-collapse collapse" data-bs-parent="#raReadonly">
                    <div class="accordion-body">
                        <ul class="list-unstyled mb-2">
                            <li><i class="bi bi-check-circle-fill text-success"></i> Flight Restriction Zones checked</li>
                            <li><i class="bi bi-check-circle-fill text-success"></i> Restricted airspace zones checked</li>
                            <li><i class="bi bi-check-circle-fill text-success"></i> NOTAMs reviewed</li>
                            <li><i class="bi bi-check-circle-fill text-success"></i> Max altitude limit confirmed (120m)</li>
                        </ul>
                        <?php if (!empty($risk_assessment->airspace_planned_altitude)): ?>
                        <p class="mb-0 small"><strong>Planned altitude:</strong> <?= esc($risk_assessment->airspace_planned_altitude) ?>m</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- 3. Weather Assessment -->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#roWeather">
                        <i class="bi bi-cloud-sun me-2"></i> 3. Weather Assessment
                        <span class="badge bg-success ms-auto me-2">1/1</span>
                    </button>
                </h2>
                <div id="roWeather" class="accordion-collapse collapse" data-bs-parent="#raReadonly">
                    <div class="accordion-body">
                        <ul class="list-unstyled mb-2">
                            <li><i class="bi bi-check-circle-fill text-success"></i> Weather conditions acceptable for flight</li>
                        </ul>
                        <div class="row small">
                            <?php if (isset($risk_assessment->weather_wind_speed) && $risk_assessment->weather_wind_speed !== null): ?>
                            <div class="col-6 col-md-4 mb-1"><strong>Wind:</strong> <?= esc($risk_assessment->weather_wind_speed) ?> km/h <?= esc($risk_assessment->weather_wind_direction ?? '') ?></div>
                            <?php endif; ?>
                            <?php if (isset($risk_assessment->weather_visibility) && $risk_assessment->weather_visibility !== null): ?>
                            <div class="col-6 col-md-4 mb-1"><strong>Visibility:</strong> <?= esc($risk_assessment->weather_visibility) ?> km</div>
                            <?php endif; ?>
                            <?php if (!empty($risk_assessment->weather_precipitation)): ?>
                            <div class="col-6 col-md-4 mb-1"><strong>Precipitation:</strong> <?= esc($risk_assessment->weather_precipitation) ?></div>
                            <?php endif; ?>
                            <?php if (isset($risk_assessment->weather_temperature) && $risk_assessment->weather_temperature !== null): ?>
                            <div class="col-6 col-md-4 mb-1"><strong>Temperature:</strong> <?= esc($risk_assessment->weather_temperature) ?>&deg;C</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4. Equipment Check -->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#roEquip">
                        <i class="bi bi-tools me-2"></i> 4. Equipment Check
                        <span class="badge bg-success ms-auto me-2">6/6</span>
                    </button>
                </h2>
                <div id="roEquip" class="accordion-collapse collapse" data-bs-parent="#raReadonly">
                    <div class="accordion-body">
                        <ul class="list-unstyled mb-0">
                            <li><i class="bi bi-check-circle-fill text-success"></i> Drone condition and airworthiness OK</li>
                            <li><i class="bi bi-check-circle-fill text-success"></i> Battery level adequate<?php if (!empty($risk_assessment->equip_battery_level)): ?> (<?= esc($risk_assessment->equip_battery_level) ?>%)<?php endif; ?></li>
                            <li><i class="bi bi-check-circle-fill text-success"></i> Propellers inspected and OK</li>
                            <li><i class="bi bi-check-circle-fill text-success"></i> GPS/GNSS lock achieved</li>
                            <li><i class="bi bi-check-circle-fill text-success"></i> Remote control functional</li>
                            <li><i class="bi bi-check-circle-fill text-success"></i> Remote ID active</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- 5. IMSAFE -->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#roImsafe">
                        <i class="bi bi-heart-pulse me-2"></i> 5. IMSAFE Pilot Fitness
                        <span class="badge bg-success ms-auto me-2">6/6</span>
                    </button>
                </h2>
                <div id="roImsafe" class="accordion-collapse collapse" data-bs-parent="#raReadonly">
                    <div class="accordion-body">
                        <ul class="list-unstyled mb-0">
                            <li><i class="bi bi-check-circle-fill text-success"></i> <strong>I</strong>llness &mdash; Free from illness</li>
                            <li><i class="bi bi-check-circle-fill text-success"></i> <strong>M</strong>edication &mdash; No impairing medication</li>
                            <li><i class="bi bi-check-circle-fill text-success"></i> <strong>S</strong>tress &mdash; Manageable stress levels</li>
                            <li><i class="bi bi-check-circle-fill text-success"></i> <strong>A</strong>lcohol &mdash; No alcohol in last 8+ hours</li>
                            <li><i class="bi bi-check-circle-fill text-success"></i> <strong>F</strong>atigue &mdash; Adequately rested</li>
                            <li><i class="bi bi-check-circle-fill text-success"></i> <strong>E</strong>ating &mdash; Properly nourished/hydrated</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- 6. Permissions -->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#roPerms">
                        <i class="bi bi-shield-check me-2"></i> 6. Permissions &amp; Compliance
                        <span class="badge bg-success ms-auto me-2">4/4</span>
                    </button>
                </h2>
                <div id="roPerms" class="accordion-collapse collapse" data-bs-parent="#raReadonly">
                    <div class="accordion-body">
                        <ul class="list-unstyled mb-0">
                            <li><i class="bi bi-check-circle-fill text-success"></i> Flyer ID valid</li>
                            <li><i class="bi bi-check-circle-fill text-success"></i> Operator ID displayed on aircraft</li>
                            <li><i class="bi bi-check-circle-fill text-success"></i> Insurance valid and in date</li>
                            <li><i class="bi bi-check-circle-fill text-success"></i> Required authorizations checked/obtained</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- 7. Emergency -->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#roEmergency">
                        <i class="bi bi-exclamation-triangle me-2"></i> 7. Emergency Procedures
                        <span class="badge bg-success ms-auto me-2">3/3</span>
                    </button>
                </h2>
                <div id="roEmergency" class="accordion-collapse collapse" data-bs-parent="#raReadonly">
                    <div class="accordion-body">
                        <ul class="list-unstyled mb-0">
                            <li><i class="bi bi-check-circle-fill text-success"></i> Emergency landing site identified</li>
                            <li><i class="bi bi-check-circle-fill text-success"></i> Emergency contacts confirmed</li>
                            <li><i class="bi bi-check-circle-fill text-success"></i> Contingency plan reviewed</li>
                        </ul>
                    </div>
                </div>
            </div>

            <?php if (($risk_assessment->category_version ?? 0) == 2): ?>
            <!-- Night Flying (if applicable) -->
            <?php if (!empty($risk_assessment->night_green_light_fitted) || !empty($risk_assessment->night_green_light_on)): ?>
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#roNight">
                        <i class="bi bi-moon me-2"></i> Night Flying Checks
                        <span class="badge bg-success ms-auto me-2">Checked</span>
                    </button>
                </h2>
                <div id="roNight" class="accordion-collapse collapse" data-bs-parent="#raReadonly">
                    <div class="accordion-body">
                        <ul class="list-unstyled mb-0">
                            <li><i class="bi bi-check-circle-fill text-<?= !empty($risk_assessment->night_green_light_fitted) ? 'success' : 'secondary' ?>"></i> Green flashing light fitted</li>
                            <li><i class="bi bi-check-circle-fill text-<?= !empty($risk_assessment->night_green_light_on) ? 'success' : 'secondary' ?>"></i> Green light switched on</li>
                            <li><i class="bi bi-check-circle-fill text-<?= !empty($risk_assessment->night_vlos_maintainable) ? 'success' : 'secondary' ?>"></i> VLOS maintainable in darkness</li>
                            <li><i class="bi bi-check-circle-fill text-<?= !empty($risk_assessment->night_orientation_visible) ? 'success' : 'secondary' ?>"></i> Drone orientation visible</li>
                        </ul>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- A2 Assessment (if applicable) -->
            <?php if (($risk_assessment->operational_category ?? '') === 'open_a2'): ?>
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#roA2">
                        <i class="bi bi-people me-2"></i> A2 People Assessment
                        <span class="badge bg-success ms-auto me-2">Checked</span>
                    </button>
                </h2>
                <div id="roA2" class="accordion-collapse collapse" data-bs-parent="#raReadonly">
                    <div class="accordion-body">
                        <ul class="list-unstyled mb-0">
                            <li><i class="bi bi-check-circle-fill text-<?= !empty($risk_assessment->a2_distance_confirmed) ? 'success' : 'secondary' ?>"></i> Required distance from people confirmed</li>
                            <li><i class="bi bi-check-circle-fill text-<?= !empty($risk_assessment->a2_low_speed_active) ? 'success' : 'secondary' ?>"></i> Low-speed mode active (if applicable)</li>
                            <li><i class="bi bi-check-circle-fill text-<?= !empty($risk_assessment->a2_segregation_assessed) ? 'success' : 'secondary' ?>"></i> Area segregation assessed</li>
                        </ul>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- A3 Assessment (if applicable) -->
            <?php if (($risk_assessment->operational_category ?? '') === 'open_a3'): ?>
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#roA3">
                        <i class="bi bi-arrows-fullscreen me-2"></i> A3 Distance Assessment
                        <span class="badge bg-success ms-auto me-2">Checked</span>
                    </button>
                </h2>
                <div id="roA3" class="accordion-collapse collapse" data-bs-parent="#raReadonly">
                    <div class="accordion-body">
                        <ul class="list-unstyled mb-0">
                            <li><i class="bi bi-check-circle-fill text-<?= !empty($risk_assessment->a3_150m_from_areas) ? 'success' : 'secondary' ?>"></i> 150m from residential/commercial/industrial areas</li>
                            <li><i class="bi bi-check-circle-fill text-<?= !empty($risk_assessment->a3_50m_from_people) ? 'success' : 'secondary' ?>"></i> 50m from uninvolved people</li>
                            <li><i class="bi bi-check-circle-fill text-<?= !empty($risk_assessment->a3_50m_from_buildings) ? 'success' : 'secondary' ?>"></i> 50m from buildings</li>
                        </ul>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Specific Ops (if applicable) -->
            <?php if (!empty($risk_assessment->operational_category) && str_starts_with($risk_assessment->operational_category, 'specific_')): ?>
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#roSpecific">
                        <i class="bi bi-file-earmark-text me-2"></i> Specific Operations
                        <span class="badge bg-success ms-auto me-2">Checked</span>
                    </button>
                </h2>
                <div id="roSpecific" class="accordion-collapse collapse" data-bs-parent="#raReadonly">
                    <div class="accordion-body">
                        <ul class="list-unstyled mb-0">
                            <li><i class="bi bi-check-circle-fill text-<?= !empty($risk_assessment->specific_ops_manual_reviewed) ? 'success' : 'secondary' ?>"></i> Operations manual reviewed</li>
                            <li><i class="bi bi-check-circle-fill text-<?= !empty($risk_assessment->specific_insurance_confirmed) ? 'success' : 'secondary' ?>"></i> Insurance confirmed</li>
                            <li><i class="bi bi-check-circle-fill text-<?= !empty($risk_assessment->specific_oa_valid) ? 'success' : 'secondary' ?>"></i> Operational Authorisation valid</li>
                        </ul>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            <?php endif; /* category_version == 2 */ ?>
        </div>
    </div>

    <!-- Decision Summary -->
    <?php
        $decisionColor = ($risk_assessment->decision ?? '') === 'abort' ? 'danger' : 'success';
        $riskLevel = $risk_assessment->risk_level ?? 'low';
        if ($riskLevel === 'low') {
            $riskColor = 'success';
        } elseif ($riskLevel === 'medium') {
            $riskColor = 'warning';
        } else {
            $riskColor = 'danger';
        }
        $raCat = $risk_assessment->operational_category ?? '';
    ?>
    <div class="col-lg-4">
        <div class="card border-<?= $decisionColor ?>">
            <div class="card-header bg-<?= $decisionColor ?> text-white">
                <i class="bi bi-clipboard-check"></i> Decision
            </div>
            <div class="card-body">
                <p class="mb-2"><strong>Risk Level:</strong>
                    <span class="badge bg-<?= $riskColor ?>">
                        <?= esc(ucfirst($riskLevel)) ?>
                    </span>
                </p>
                <p class="mb-2"><strong>Decision:</strong>
                    <span class="badge bg-<?= $decisionColor ?>">
                        <?= esc(ucwords(str_replace('_', ' ', $risk_assessment->decision ?? ''))) ?>
                    </span>
                </p>
                <?php if (!empty($risk_assessment->mitigation_notes)): ?>
                <p class="mb-2"><strong>Mitigations:</strong><br><?= esc($risk_assessment->mitigation_notes) ?></p>
                <?php endif; ?>
                <?php if (!empty($raCat)): ?>
                <p class="mb-2"><strong>Category:</strong>
                    <span class="badge bg-<?= str_starts_with($raCat, 'open_') ? 'success' : 'primary' ?>">
                        <?= esc(ucwords(str_replace('_', ' ', $raCat))) ?>
                    </span>
                    <?php if (($risk_assessment->category_version ?? 0) == 1): ?><small class="text-muted">(legacy)</small><?php endif; ?>
                </p>
                <?php endif; ?>
                <p class="mb-2"><strong>Pilot:</strong> <?= esc($risk_assessment->pilot->display_name ?? $risk_assessment->pilot_display_name ?? '') ?></p>
                <p class="mb-2"><strong>Completed:</strong> <?= esc(date('d M Y H:i', strtotime($risk_assessment->created_at)) . ' UTC') ?></p>
                <?php if (!empty($risk_assessment->gps_latitude) && !empty($risk_assessment->gps_longitude)): ?>
                <p class="mb-0"><strong>GPS:</strong> <?= esc(number_format((float)$risk_assessment->gps_latitude, 6)) ?>, <?= esc(number_format((float)$risk_assessment->gps_longitude, 6)) ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php else: ?>
<!-- ── Editable Form ───────────────────────────────────────────── -->
<form method="POST" id="riskAssessmentForm" action="<?= site_url('pilot/orders/' . $order->id . '/risk-assessment') ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="gps_latitude" id="gps_latitude" value="">
    <input type="hidden" name="gps_longitude" id="gps_longitude" value="">

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="accordion" id="raAccordion">
                <!-- 1. Site Assessment -->
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#secSite">
                            <i class="bi bi-geo-alt me-2"></i> 1. Site Assessment
                            <span class="badge bg-secondary ms-auto me-2" id="badgeSite">0/4</span>
                        </button>
                    </h2>
                    <div id="secSite" class="accordion-collapse collapse show" data-bs-parent="#raAccordion">
                        <div class="accordion-body">
                            <div class="form-check mb-2">
                                <input class="form-check-input ra-check" type="checkbox" name="site_ground_hazards" id="site_ground_hazards" value="1" data-section="Site">
                                <label class="form-check-label" for="site_ground_hazards">Ground hazards assessed and area safe</label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input ra-check" type="checkbox" name="site_obstacles_mapped" id="site_obstacles_mapped" value="1" data-section="Site">
                                <label class="form-check-label" for="site_obstacles_mapped">Obstacles identified and mapped</label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input ra-check" type="checkbox" name="site_50m_separation" id="site_50m_separation" value="1" data-section="Site">
                                <label class="form-check-label" for="site_50m_separation">50m separation from uninvolved persons confirmed</label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input ra-check" type="checkbox" name="site_150m_residential" id="site_150m_residential" value="1" data-section="Site">
                                <label class="form-check-label" for="site_150m_residential">150m from residential/commercial/industrial areas confirmed</label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. Airspace Check -->
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#secAirspace">
                            <i class="bi bi-broadcast me-2"></i> 2. Airspace Check
                            <span class="badge bg-secondary ms-auto me-2" id="badgeAirspace">0/4</span>
                        </button>
                    </h2>
                    <div id="secAirspace" class="accordion-collapse collapse" data-bs-parent="#raAccordion">
                        <div class="accordion-body">
                            <div class="form-check mb-2">
                                <input class="form-check-input ra-check" type="checkbox" name="airspace_frz_checked" id="airspace_frz_checked" value="1" data-section="Airspace">
                                <label class="form-check-label" for="airspace_frz_checked">Flight Restriction Zones (FRZ) checked</label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input ra-check" type="checkbox" name="airspace_restricted_checked" id="airspace_restricted_checked" value="1" data-section="Airspace">
                                <label class="form-check-label" for="airspace_restricted_checked">Restricted airspace zones checked</label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input ra-check" type="checkbox" name="airspace_notams_reviewed" id="airspace_notams_reviewed" value="1" data-section="Airspace">
                                <label class="form-check-label" for="airspace_notams_reviewed">NOTAMs reviewed (dates/times in UTC)</label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input ra-check" type="checkbox" name="airspace_max_altitude_confirmed" id="airspace_max_altitude_confirmed" value="1" data-section="Airspace">
                                <label class="form-check-label" for="airspace_max_altitude_confirmed">Max altitude limit confirmed (120m Open Category)</label>
                            </div>
                            <div class="mt-3">
                                <label for="airspace_planned_altitude" class="form-label small">Planned altitude (metres)</label>
                                <input type="number" class="form-control form-control-sm" name="airspace_planned_altitude" id="airspace_planned_altitude" min="0" max="120" step="1" placeholder="e.g. 80">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. Weather Assessment -->
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#secWeather">
                            <i class="bi bi-cloud-sun me-2"></i> 3. Weather Assessment
                            <span class="badge bg-secondary ms-auto me-2" id="badgeWeather">0/1</span>
                        </button>
                    </h2>
                    <div id="secWeather" class="accordion-collapse collapse" data-bs-parent="#raAccordion">
                        <div class="accordion-body">
                            <div class="form-check mb-3">
                                <input class="form-check-input ra-check" type="checkbox" name="weather_acceptable" id="weather_acceptable" value="1" data-section="Weather">
                                <label class="form-check-label" for="weather_acceptable">Weather conditions acceptable for flight</label>
                            </div>
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <label class="form-label small">Wind speed (km/h)</label>
                                    <input type="number" class="form-control form-control-sm" name="weather_wind_speed" step="0.1" min="0" placeholder="e.g. 15">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small">Wind direction</label>
                                    <input type="text" class="form-control form-control-sm" name="weather_wind_direction" placeholder="e.g. NW">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small">Visibility (km)</label>
                                    <input type="number" class="form-control form-control-sm" name="weather_visibility" step="0.1" min="0" placeholder="e.g. 10">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small">Precipitation</label>
                                    <input type="text" class="form-control form-control-sm" name="weather_precipitation" placeholder="e.g. None">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small">Temperature (&deg;C)</label>
                                    <input type="number" class="form-control form-control-sm" name="weather_temperature" step="0.1" placeholder="e.g. 18">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4. Equipment Check -->
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#secEquip">
                            <i class="bi bi-tools me-2"></i> 4. Equipment Check
                            <span class="badge bg-secondary ms-auto me-2" id="badgeEquip">0/6</span>
                        </button>
                    </h2>
                    <div id="secEquip" class="accordion-collapse collapse" data-bs-parent="#raAccordion">
                        <div class="accordion-body">
                            <div class="form-check mb-2">
                                <input class="form-check-input ra-check" type="checkbox" name="equip_condition_ok" id="equip_condition_ok" value="1" data-section="Equip">
                                <label class="form-check-label" for="equip_condition_ok">Drone condition and airworthiness OK</label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input ra-check" type="checkbox" name="equip_battery_adequate" id="equip_battery_adequate" value="1" data-section="Equip">
                                <label class="form-check-label" for="equip_battery_adequate">Battery level adequate</label>
                            </div>
                            <div class="mb-3 ms-4">
                                <label class="form-label small">Battery level (%)</label>
                                <input type="number" class="form-control form-control-sm" name="equip_battery_level" min="0" max="100" placeholder="e.g. 95" style="max-width: 120px;">
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input ra-check" type="checkbox" name="equip_propellers_ok" id="equip_propellers_ok" value="1" data-section="Equip">
                                <label class="form-check-label" for="equip_propellers_ok">Propellers inspected and OK</label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input ra-check" type="checkbox" name="equip_gps_lock" id="equip_gps_lock" value="1" data-section="Equip">
                                <label class="form-check-label" for="equip_gps_lock">GPS/GNSS lock achieved</label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input ra-check" type="checkbox" name="equip_remote_ok" id="equip_remote_ok" value="1" data-section="Equip">
                                <label class="form-check-label" for="equip_remote_ok">Remote control functional</label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input ra-check" type="checkbox" name="equip_remote_id_active" id="equip_remote_id_active" value="1" data-section="Equip">
                                <label class="form-check-label" for="equip_remote_id_active">Remote ID active (required from Jan 2026)</label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 5. IMSAFE Pilot Fitness -->
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#secImsafe">
                            <i class="bi bi-heart-pulse me-2"></i> 5. IMSAFE Pilot Fitness
                            <span class="badge bg-secondary ms-auto me-2" id="badgeImsafe">0/6</span>
                        </button>
                    </h2>
                    <div id="secImsafe" class="accordion-collapse collapse" data-bs-parent="#raAccordion">
                        <div class="accordion-body">
                            <div class="form-check mb-2">
                                <input class="form-check-input ra-check" type="checkbox" name="imsafe_illness" id="imsafe_illness" value="1" data-section="Imsafe">
                                <label class="form-check-label" for="imsafe_illness"><strong>I</strong>llness &mdash; I am free from illness</label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input ra-check" type="checkbox" name="imsafe_medication" id="imsafe_medication" value="1" data-section="Imsafe">
                                <label class="form-check-label" for="imsafe_medication"><strong>M</strong>edication &mdash; No impairing medication taken</label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input ra-check" type="checkbox" name="imsafe_stress" id="imsafe_stress" value="1" data-section="Imsafe">
                                <label class="form-check-label" for="imsafe_stress"><strong>S</strong>tress &mdash; My stress levels are manageable</label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input ra-check" type="checkbox" name="imsafe_alcohol" id="imsafe_alcohol" value="1" data-section="Imsafe">
                                <label class="form-check-label" for="imsafe_alcohol"><strong>A</strong>lcohol &mdash; No alcohol consumed in last 8+ hours</label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input ra-check" type="checkbox" name="imsafe_fatigue" id="imsafe_fatigue" value="1" data-section="Imsafe">
                                <label class="form-check-label" for="imsafe_fatigue"><strong>F</strong>atigue &mdash; I am adequately rested</label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input ra-check" type="checkbox" name="imsafe_eating" id="imsafe_eating" value="1" data-section="Imsafe">
                                <label class="form-check-label" for="imsafe_eating"><strong>E</strong>ating &mdash; Properly nourished and hydrated</label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 6. Permissions & Compliance -->
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#secPerms">
                            <i class="bi bi-shield-check me-2"></i> 6. Permissions &amp; Compliance
                            <span class="badge bg-secondary ms-auto me-2" id="badgePerms">0/4</span>
                        </button>
                    </h2>
                    <div id="secPerms" class="accordion-collapse collapse" data-bs-parent="#raAccordion">
                        <div class="accordion-body">
                            <div class="form-check mb-2">
                                <input class="form-check-input ra-check" type="checkbox" name="perms_flyer_id_valid" id="perms_flyer_id_valid" value="1" data-section="Perms">
                                <label class="form-check-label" for="perms_flyer_id_valid">Flyer ID valid (mandatory for 100g+ from Jan 2026)</label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input ra-check" type="checkbox" name="perms_operator_id_displayed" id="perms_operator_id_displayed" value="1" data-section="Perms">
                                <label class="form-check-label" for="perms_operator_id_displayed">Operator ID displayed on aircraft</label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input ra-check" type="checkbox" name="perms_insurance_valid" id="perms_insurance_valid" value="1" data-section="Perms">
                                <label class="form-check-label" for="perms_insurance_valid">Insurance valid and in date</label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input ra-check" type="checkbox" name="perms_authorizations_checked" id="perms_authorizations_checked" value="1" data-section="Perms">
                                <label class="form-check-label" for="perms_authorizations_checked">Required authorizations checked/obtained</label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 7. Emergency Procedures -->
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#secEmergency">
                            <i class="bi bi-exclamation-triangle me-2"></i> 7. Emergency Procedures
                            <span class="badge bg-secondary ms-auto me-2" id="badgeEmergency">0/3</span>
                        </button>
                    </h2>
                    <div id="secEmergency" class="accordion-collapse collapse" data-bs-parent="#raAccordion">
                        <div class="accordion-body">
                            <div class="form-check mb-2">
                                <input class="form-check-input ra-check" type="checkbox" name="emergency_landing_site" id="emergency_landing_site" value="1" data-section="Emergency">
                                <label class="form-check-label" for="emergency_landing_site">Emergency landing site identified</label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input ra-check" type="checkbox" name="emergency_contacts_confirmed" id="emergency_contacts_confirmed" value="1" data-section="Emergency">
                                <label class="form-check-label" for="emergency_contacts_confirmed">Emergency contacts confirmed</label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input ra-check" type="checkbox" name="emergency_contingency_plan" id="emergency_contingency_plan" value="1" data-section="Emergency">
                                <label class="form-check-label" for="emergency_contingency_plan">Contingency plan reviewed</label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── Conditional Sections ─────────────────────────── -->

                <?php if (in_array($order->time_of_day ?? '', ['night', 'twilight'])): ?>
                <!-- Night Flying -->
                <div class="accordion-item border-warning">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#secNight">
                            <i class="bi bi-moon me-2"></i> Night Flying Checks
                            <span class="badge bg-warning text-dark ms-2">Mandatory</span>
                            <span class="badge bg-secondary ms-auto me-2" id="badgeNight">0/4</span>
                        </button>
                    </h2>
                    <div id="secNight" class="accordion-collapse collapse" data-bs-parent="#raAccordion">
                        <div class="accordion-body">
                            <div class="form-check mb-2">
                                <input class="form-check-input ra-check" type="checkbox" name="night_green_light_fitted" id="night_green_light_fitted" value="1" data-section="Night">
                                <label class="form-check-label" for="night_green_light_fitted">Green flashing light fitted to drone</label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input ra-check" type="checkbox" name="night_green_light_on" id="night_green_light_on" value="1" data-section="Night">
                                <label class="form-check-label" for="night_green_light_on">Green light switched on and visible from ground</label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input ra-check" type="checkbox" name="night_vlos_maintainable" id="night_vlos_maintainable" value="1" data-section="Night">
                                <label class="form-check-label" for="night_vlos_maintainable">VLOS maintainable in darkness conditions</label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input ra-check" type="checkbox" name="night_orientation_visible" id="night_orientation_visible" value="1" data-section="Night">
                                <label class="form-check-label" for="night_orientation_visible">Drone orientation clearly visible</label>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (($order->operational_category ?? '') === 'open_a2'): ?>
                <!-- A2 People Assessment -->
                <div class="accordion-item border-info">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#secA2">
                            <i class="bi bi-people me-2"></i> A2 People Assessment
                            <span class="badge bg-info ms-2">A2 Required</span>
                            <span class="badge bg-secondary ms-auto me-2" id="badgeA2">0/3</span>
                        </button>
                    </h2>
                    <div id="secA2" class="accordion-collapse collapse" data-bs-parent="#raAccordion">
                        <div class="accordion-body">
                            <?php if (!empty($category_result)): ?>
                            <div class="alert alert-info py-1 small mb-3">
                                <strong>Required distance from people:</strong> <?= esc($category_result->min_distance_people_m ?? '') ?>m
                            </div>
                            <?php endif; ?>
                            <div class="form-check mb-2">
                                <input class="form-check-input ra-check" type="checkbox" name="a2_distance_confirmed" id="a2_distance_confirmed" value="1" data-section="A2">
                                <label class="form-check-label" for="a2_distance_confirmed">Required horizontal distance from uninvolved people confirmed</label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input ra-check" type="checkbox" name="a2_low_speed_active" id="a2_low_speed_active" value="1" data-section="A2">
                                <label class="form-check-label" for="a2_low_speed_active">Low-speed mode active (if using 5m reduction)</label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input ra-check" type="checkbox" name="a2_segregation_assessed" id="a2_segregation_assessed" value="1" data-section="A2">
                                <label class="form-check-label" for="a2_segregation_assessed">Area segregation assessed for close-proximity operations</label>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (($order->operational_category ?? '') === 'open_a3'): ?>
                <!-- A3 Distance Assessment -->
                <div class="accordion-item border-info">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#secA3">
                            <i class="bi bi-arrows-fullscreen me-2"></i> A3 Distance Assessment
                            <span class="badge bg-info ms-2">A3 Required</span>
                            <span class="badge bg-secondary ms-auto me-2" id="badgeA3">0/3</span>
                        </button>
                    </h2>
                    <div id="secA3" class="accordion-collapse collapse" data-bs-parent="#raAccordion">
                        <div class="accordion-body">
                            <div class="form-check mb-2">
                                <input class="form-check-input ra-check" type="checkbox" name="a3_150m_from_areas" id="a3_150m_from_areas" value="1" data-section="A3">
                                <label class="form-check-label" for="a3_150m_from_areas">150m from residential, commercial, industrial, and recreational areas confirmed</label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input ra-check" type="checkbox" name="a3_50m_from_people" id="a3_50m_from_people" value="1" data-section="A3">
                                <label class="form-check-label" for="a3_50m_from_people">50m from uninvolved people confirmed</label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input ra-check" type="checkbox" name="a3_50m_from_buildings" id="a3_50m_from_buildings" value="1" data-section="A3">
                                <label class="form-check-label" for="a3_50m_from_buildings">50m from buildings confirmed</label>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($order->operational_category) && str_starts_with($order->operational_category, 'specific_')): ?>
                <!-- Specific Ops -->
                <div class="accordion-item border-primary">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#secSpecific">
                            <i class="bi bi-file-earmark-text me-2"></i> Specific Operations
                            <span class="badge bg-primary ms-2">Mandatory</span>
                            <span class="badge bg-secondary ms-auto me-2" id="badgeSpecific">0/3</span>
                        </button>
                    </h2>
                    <div id="secSpecific" class="accordion-collapse collapse" data-bs-parent="#raAccordion">
                        <div class="accordion-body">
                            <div class="form-check mb-2">
                                <input class="form-check-input ra-check" type="checkbox" name="specific_ops_manual_reviewed" id="specific_ops_manual_reviewed" value="1" data-section="Specific">
                                <label class="form-check-label" for="specific_ops_manual_reviewed">Operations manual reviewed and procedures followed</label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input ra-check" type="checkbox" name="specific_insurance_confirmed" id="specific_insurance_confirmed" value="1" data-section="Specific">
                                <label class="form-check-label" for="specific_insurance_confirmed">Third-party liability insurance confirmed</label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input ra-check" type="checkbox" name="specific_oa_valid" id="specific_oa_valid" value="1" data-section="Specific">
                                <label class="form-check-label" for="specific_oa_valid">Operational Authorisation valid and conditions met</label>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Decision Panel -->
        <div class="col-lg-4">
            <div class="card sticky-top" style="top: 1rem;">
                <div class="card-header"><i class="bi bi-clipboard-check"></i> Overall Decision</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Checks completed</label>
                        <div class="progress mb-1" style="height: 20px;">
                            <div class="progress-bar bg-success" id="progressBar" role="progressbar" style="width: 0%">0/28</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Risk Level</label>
                        <div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="risk_level" id="riskLow" value="low">
                                <label class="form-check-label" for="riskLow"><span class="badge bg-success">Low</span></label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="risk_level" id="riskMed" value="medium">
                                <label class="form-check-label" for="riskMed"><span class="badge bg-warning">Medium</span></label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="risk_level" id="riskHigh" value="high">
                                <label class="form-check-label" for="riskHigh"><span class="badge bg-danger">High</span></label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Decision</label>
                        <select name="decision" id="decisionSelect" class="form-select form-select-sm">
                            <option value="">-- Select --</option>
                            <option value="proceed">Proceed</option>
                            <option value="proceed_with_mitigations">Proceed with Mitigations</option>
                            <option value="abort">Abort</option>
                        </select>
                    </div>

                    <div class="mb-3" id="mitigationGroup" style="display: none;">
                        <label class="form-label small fw-bold">Mitigation Notes <span class="text-danger">*</span></label>
                        <textarea name="mitigation_notes" class="form-control form-control-sm" rows="3"
                                  placeholder="Describe mitigations being applied..."></textarea>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="pilot_declaration" id="pilot_declaration" value="1">
                            <label class="form-check-label small" for="pilot_declaration">
                                I declare that this pre-flight risk assessment has been completed on-site and all information is accurate to the best of my knowledge.
                            </label>
                        </div>
                    </div>

                    <div class="mb-3 small" id="gpsStatus">
                        <i class="bi bi-geo-alt"></i> <span id="gpsText">Acquiring GPS location...</span>
                    </div>

                    <button type="submit" class="btn btn-primary w-100" id="submitBtn" disabled>
                        <i class="bi bi-check-circle"></i> Submit Assessment
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>
<?php endif; ?>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<?php if (empty($risk_assessment)): ?>
<script>
(function() {
    var sectionCounts = {
        Site: {total: 4, checked: 0},
        Airspace: {total: 4, checked: 0},
        Weather: {total: 1, checked: 0},
        Equip: {total: 6, checked: 0},
        Imsafe: {total: 6, checked: 0},
        Perms: {total: 4, checked: 0},
        Emergency: {total: 3, checked: 0}
    };
    // Dynamically count conditional sections
    var conditionalSections = ['Night', 'A2', 'A3', 'Specific'];
    conditionalSections.forEach(function(s) {
        var checks = document.querySelectorAll('.ra-check[data-section="' + s + '"]');
        if (checks.length > 0) {
            sectionCounts[s] = {total: checks.length, checked: 0};
        }
    });
    var totalChecks = 0;
    for (var s in sectionCounts) totalChecks += sectionCounts[s].total;

    function updateBadges() {
        // Reset counts
        for (var s in sectionCounts) sectionCounts[s].checked = 0;

        var checks = document.querySelectorAll('.ra-check');
        var totalChecked = 0;
        checks.forEach(function(cb) {
            if (cb.checked) {
                sectionCounts[cb.dataset.section].checked++;
                totalChecked++;
            }
        });

        for (var s in sectionCounts) {
            var badge = document.getElementById('badge' + s);
            if (badge) {
                var sc = sectionCounts[s];
                badge.textContent = sc.checked + '/' + sc.total;
                badge.className = 'badge ms-auto me-2 ' + (sc.checked === sc.total ? 'bg-success' : 'bg-secondary');
            }
        }

        var pct = Math.round((totalChecked / totalChecks) * 100);
        var bar = document.getElementById('progressBar');
        bar.style.width = pct + '%';
        bar.textContent = totalChecked + '/' + totalChecks;
        bar.className = 'progress-bar ' + (pct === 100 ? 'bg-success' : 'bg-primary');

        updateSubmitButton();
    }

    function updateSubmitButton() {
        var allChecked = true;
        document.querySelectorAll('.ra-check').forEach(function(cb) {
            if (!cb.checked) allChecked = false;
        });
        var hasDecision = document.getElementById('decisionSelect').value !== '';
        var hasRisk = document.querySelector('input[name="risk_level"]:checked') !== null;
        var hasDeclaration = document.getElementById('pilot_declaration').checked;

        document.getElementById('submitBtn').disabled = !(allChecked && hasDecision && hasRisk && hasDeclaration);
    }

    document.querySelectorAll('.ra-check').forEach(function(cb) {
        cb.addEventListener('change', updateBadges);
    });

    document.getElementById('decisionSelect').addEventListener('change', function() {
        var mg = document.getElementById('mitigationGroup');
        mg.style.display = this.value === 'proceed_with_mitigations' ? 'block' : 'none';
        updateSubmitButton();
    });

    document.querySelectorAll('input[name="risk_level"]').forEach(function(r) {
        r.addEventListener('change', updateSubmitButton);
    });
    document.getElementById('pilot_declaration').addEventListener('change', updateSubmitButton);

    // GPS
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(pos) {
            document.getElementById('gps_latitude').value = pos.coords.latitude;
            document.getElementById('gps_longitude').value = pos.coords.longitude;
            var gpsSpan = document.getElementById('gpsText');
            gpsSpan.textContent = 'Location: ' + pos.coords.latitude.toFixed(6) + ', ' + pos.coords.longitude.toFixed(6);
        }, function() {
            document.getElementById('gpsText').textContent = 'GPS unavailable (assessment will still be recorded)';
        });
    } else {
        document.getElementById('gpsText').textContent = 'GPS not supported by browser';
    }
})();
</script>
<?php endif; ?>
<?= $this->endSection() ?>
