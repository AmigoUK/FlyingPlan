<?= $this->extend('layouts/base') ?>
<?= $this->section('title') ?><?= esc($flight_plan->reference) ?><?= $this->endSection() ?>

<?= $this->section('head_extra') ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.css" />
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php $settings = (new \App\Models\AppSettingsModel())->getSettings(); ?>
<?php $_settingsModel = new \App\Models\AppSettingsModel(); ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <a href="<?= site_url('admin') ?>" class="btn btn-sm btn-outline-secondary me-2">
            <i class="bi bi-arrow-left"></i> Back
        </a>
        <code class="fs-5"><?= esc($flight_plan->reference) ?></code>
        <span class="badge badge-<?= $flight_plan->status ?> ms-2"><?= ucwords(str_replace('_', ' ', $flight_plan->status)) ?></span>
    </div>
    <div class="d-flex gap-2">
        <select id="status-select" class="form-select form-select-sm" style="width: auto;">
            <?php foreach (['new', 'in_review', 'route_planned', 'completed', 'cancelled'] as $s): ?>
            <option value="<?= $s ?>" <?= $flight_plan->status == $s ? 'selected' : '' ?>>
                <?= ucwords(str_replace('_', ' ', $s)) ?>
            </option>
            <?php endforeach; ?>
        </select>
        <select id="drone-model-select" class="form-select form-select-sm" style="width: auto;">
            <?php foreach ($drone_choices as $choice): ?>
            <option value="<?= $choice[0] ?>" <?= ($flight_plan->drone_model ?? '') == $choice[0] ? 'selected' : '' ?>><?= esc($choice[1]) ?></option>
            <?php endforeach; ?>
        </select>
        <label class="btn btn-sm btn-outline-primary mb-0" for="import-kmz-file">
            <i class="bi bi-upload"></i> Import KMZ
        </label>
        <input type="file" id="import-kmz-file" accept=".kmz" class="d-none">
        <div class="btn-group">
            <a href="<?= site_url('admin/' . $flight_plan->id . '/export-kmz') ?>"
               class="btn btn-sm btn-success">
                <i class="bi bi-download"></i> Export KMZ
            </a>
            <button type="button" class="btn btn-sm btn-success dropdown-toggle dropdown-toggle-split"
                    data-bs-toggle="dropdown"></button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="<?= site_url('admin/' . $flight_plan->id . '/export-kml') ?>"><i class="bi bi-globe me-1"></i> KML (Google Earth)</a></li>
                <li><a class="dropdown-item" href="<?= site_url('admin/' . $flight_plan->id . '/export-geojson') ?>"><i class="bi bi-braces me-1"></i> GeoJSON (GIS)</a></li>
                <li><a class="dropdown-item" href="<?= site_url('admin/' . $flight_plan->id . '/export-csv') ?>"><i class="bi bi-file-earmark-spreadsheet me-1"></i> CSV (Spreadsheet)</a></li>
                <li><a class="dropdown-item" href="<?= site_url('admin/' . $flight_plan->id . '/export-gpx') ?>"><i class="bi bi-pin-map me-1"></i> GPX (GPS)</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="<?= site_url('admin/' . $flight_plan->id . '/export-litchi') ?>"><i class="bi bi-phone me-1"></i> Litchi CSV</a></li>
                <li><a class="dropdown-item" href="<?= site_url('admin/' . $flight_plan->id . '/export-photo-positions') ?>"><i class="bi bi-camera me-1"></i> Photo Positions CSV</a></li>
                <li><a class="dropdown-item" href="<?= site_url('admin/' . $flight_plan->id . '/export-enhanced-geojson') ?>"><i class="bi bi-geo me-1"></i> Enhanced GeoJSON (3D)</a></li>
            </ul>
        </div>
        <button class="btn btn-sm btn-outline-info" id="btn-share" title="Create shareable link">
            <i class="bi bi-share"></i> Share
        </button>
    </div>
</div>

<div class="row g-4">
    <!-- Left: Info -->
    <div class="col-lg-5">
        <!-- Customer (collapsible info card) -->
        <div class="card mb-3">
            <div class="card-header card-header-collapse" role="button" data-bs-toggle="collapse" data-bs-target="#collapse-customer" aria-expanded="false">
                <span>
                    <i class="bi bi-person"></i> Customer
                    <span class="collapse-summary text-muted ms-2">&mdash; <?= esc($flight_plan->customer_name) ?>
                        <?php if (($flight_plan->customer_type ?? '') == 'business'): ?>
                        <span class="badge bg-info ms-1" style="font-size:.65rem">Business</span>
                        <?php else: ?>
                        <span class="badge bg-secondary ms-1" style="font-size:.65rem">Private</span>
                        <?php endif; ?>
                    </span>
                </span>
                <i class="bi bi-chevron-right collapse-chevron"></i>
            </div>
            <div class="collapse info-card-collapse" id="collapse-customer">
                <div class="card-body">
                    <?php if (($flight_plan->customer_type ?? '') == 'business'): ?>
                    <span class="badge bg-info mb-2">Business</span>
                    <?php else: ?>
                    <span class="badge bg-secondary mb-2">Private</span>
                    <?php endif; ?>
                    <p class="mb-1"><strong><?= esc($flight_plan->customer_name) ?></strong></p>
                    <p class="mb-1"><a href="mailto:<?= esc($flight_plan->customer_email) ?>"><?= esc($flight_plan->customer_email) ?></a></p>
                    <?php if (!empty($flight_plan->customer_phone)): ?>
                    <p class="mb-1"><a href="tel:<?= esc($flight_plan->customer_phone) ?>"><?= esc($flight_plan->customer_phone) ?></a></p>
                    <?php endif; ?>
                    <?php if (!empty($flight_plan->customer_company)): ?>
                    <p class="mb-1 text-muted"><?= esc($flight_plan->customer_company) ?></p>
                    <?php endif; ?>
                    <?php if (($flight_plan->customer_type ?? '') == 'business'): ?>
                        <?php if (!empty($flight_plan->business_abn)): ?>
                        <p class="mb-1"><strong>ABN:</strong> <?= esc($flight_plan->business_abn) ?></p>
                        <?php endif; ?>
                        <?php if (!empty($flight_plan->billing_contact)): ?>
                        <p class="mb-1"><strong>Billing Contact:</strong> <?= esc($flight_plan->billing_contact) ?></p>
                        <?php endif; ?>
                        <?php if (!empty($flight_plan->billing_email)): ?>
                        <p class="mb-1"><strong>Billing Email:</strong> <?= esc($flight_plan->billing_email) ?></p>
                        <?php endif; ?>
                        <?php if (!empty($flight_plan->purchase_order)): ?>
                        <p class="mb-0"><strong>PO #:</strong> <?= esc($flight_plan->purchase_order) ?></p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Job Brief (collapsible info card) -->
        <div class="card mb-3">
            <div class="card-header card-header-collapse" role="button" data-bs-toggle="collapse" data-bs-target="#collapse-jobbr" aria-expanded="false">
                <span>
                    <i class="bi bi-clipboard-check"></i> Job Brief
                    <span class="collapse-summary text-muted ms-2">&mdash; <?= ucwords(str_replace('_', ' ', $flight_plan->job_type ?? '')) ?> &middot; <?= ucfirst($flight_plan->urgency ?? '') ?></span>
                </span>
                <i class="bi bi-chevron-right collapse-chevron"></i>
            </div>
            <div class="collapse info-card-collapse" id="collapse-jobbr">
                <div class="card-body">
                    <p class="mb-1">
                        <strong>Type:</strong> <?= ucwords(str_replace('_', ' ', $flight_plan->job_type ?? '')) ?>
                        <span class="ms-2 urgency-<?= $flight_plan->urgency ?? '' ?>"><?= ucfirst($flight_plan->urgency ?? '') ?></span>
                    </p>
                    <p class="mb-1"><strong>Description:</strong></p>
                    <p class="mb-2"><?= esc($flight_plan->job_description ?? '') ?: '-' ?></p>
                    <p class="mb-1"><strong>Preferred Dates:</strong> <?= esc($flight_plan->preferred_dates ?? '') ?: 'Flexible' ?></p>
                    <p class="mb-1"><strong>Time:</strong> <?= esc($flight_plan->time_window ?? '') ?: 'Flexible' ?></p>
                    <?php if (!empty($flight_plan->special_requirements)): ?>
                    <p class="mb-0"><strong>Special:</strong> <?= esc($flight_plan->special_requirements) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Flight Preferences (collapsible info card) -->
        <div class="card mb-3">
            <div class="card-header card-header-collapse" role="button" data-bs-toggle="collapse" data-bs-target="#collapse-flpref" aria-expanded="false">
                <span>
                    <i class="bi bi-sliders"></i> Flight Preferences
                    <span class="collapse-summary text-muted ms-2">&mdash;
                        <?php if (($flight_plan->altitude_preset ?? '') == 'custom'): ?><?= esc($flight_plan->altitude_custom_m ?? '') ?>m<?php else: ?><?= ucfirst($flight_plan->altitude_preset ?? '') ?><?php endif; ?>
                        &middot; <?= !empty($flight_plan->camera_angle) ? ucwords(str_replace('_', ' ', $flight_plan->camera_angle)) : 'Auto' ?>
                        &middot; <?= esc($flight_plan->video_resolution ?? '') ?: '4K' ?>
                    </span>
                </span>
                <i class="bi bi-chevron-right collapse-chevron"></i>
            </div>
            <div class="collapse info-card-collapse" id="collapse-flpref">
                <div class="card-body">
                    <p class="mb-1"><strong>Altitude:</strong>
                        <?php if (($flight_plan->altitude_preset ?? '') == 'custom'): ?>
                        Custom (<?= esc($flight_plan->altitude_custom_m ?? '') ?>m)
                        <?php else: ?>
                        <?= ucfirst($flight_plan->altitude_preset ?? '') ?>
                        <?php endif; ?>
                    </p>
                    <p class="mb-1"><strong>Camera:</strong> <?= !empty($flight_plan->camera_angle) ? ucwords(str_replace('_', ' ', $flight_plan->camera_angle)) : 'Pilot Decides' ?></p>
                    <p class="mb-1"><strong>Resolution:</strong> <?= esc($flight_plan->video_resolution ?? '') ?: '4K' ?></p>
                    <p class="mb-1"><strong>Photo:</strong> <?= !empty($flight_plan->photo_mode) ? ucfirst($flight_plan->photo_mode) : 'Single' ?></p>
                    <?php if (!empty($flight_plan->no_fly_notes)): ?>
                    <p class="mb-1"><strong>No-Fly Notes:</strong> <?= esc($flight_plan->no_fly_notes) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($flight_plan->privacy_notes)): ?>
                    <p class="mb-1"><strong>Privacy:</strong> <?= esc($flight_plan->privacy_notes) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($flight_plan->footage_purpose)): ?>
                    <hr class="my-2">
                    <p class="mb-1"><strong>Purpose:</strong> <?= ucwords(str_replace('_', ' ', $flight_plan->footage_purpose)) ?>
                        <?php if ($flight_plan->footage_purpose == 'other' && !empty($flight_plan->footage_purpose_other)): ?>
                         &mdash; <?= esc($flight_plan->footage_purpose_other) ?>
                        <?php endif; ?>
                    </p>
                    <?php endif; ?>
                    <?php if (!empty($flight_plan->output_format)): ?>
                    <p class="mb-1"><strong>Output Format:</strong> <?= ucwords(str_replace('_', ' ', $flight_plan->output_format)) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($flight_plan->video_duration)): ?>
                    <p class="mb-1"><strong>Video Duration:</strong> <?= esc($flight_plan->video_duration) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($flight_plan->shot_types)): ?>
                    <p class="mb-1"><strong>Shot Types:</strong> <?= str_replace(['[', ']', '"', '_'], ['', '', '', ' '], esc($flight_plan->shot_types)) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($flight_plan->delivery_timeline)): ?>
                    <p class="mb-0"><strong>Delivery Timeline:</strong> <?= ucwords(str_replace('_', ' ', $flight_plan->delivery_timeline)) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Workflow Selector + Stepper -->
        <div class="card mb-3">
            <div class="card-body py-2" id="workflow-bar"></div>
        </div>

        <!-- Route Stats -->
        <?php if ($_settingsModel->isPanelEnabled('route_stats')): ?>
        <div class="card mb-3" data-card-key="route_stats">
            <div class="card-body py-2" id="route-stats"></div>
        </div>
        <?php endif; ?>

        <!-- Path Tools -->
        <?php if ($_settingsModel->isPanelEnabled('path_tools')): ?>
        <div class="card mb-3" data-card-key="path_tools">
            <div class="card-header card-header-collapse" role="button" data-bs-toggle="collapse" data-bs-target="#collapse-pathtools" aria-expanded="true">
                <span><i class="bi bi-tools"></i> Path Tools</span>
                <i class="bi bi-chevron-down collapse-chevron"></i>
            </div>
            <div class="collapse show" id="collapse-pathtools">
                <div class="card-body py-2" id="path-tools-bar"></div>
            </div>
        </div>
        <?php endif; ?>

        <!-- GSD Calculator -->
        <?php if ($_settingsModel->isPanelEnabled('gsd')): ?>
        <div class="card mb-3" data-card-key="gsd">
            <div class="card-header card-header-collapse" role="button" data-bs-toggle="collapse" data-bs-target="#collapse-gsd" aria-expanded="true">
                <span>
                    <i class="bi bi-aspect-ratio"></i> GSD Calculator
                    <i class="bi bi-question-circle-fill text-muted ms-1 fp-tooltip-icon" data-bs-toggle="tooltip" title="Calculates photo resolution (ground sampling distance) based on your drone and altitude. Use this first to choose the right altitude for your mission."></i>
                </span>
                <i class="bi bi-chevron-down collapse-chevron"></i>
            </div>
            <div class="collapse show" id="collapse-gsd">
            <div class="card-body">
                <?php if (!empty($settings->guide_mode)): ?>
                <div class="guide-block mb-2">
                    <a class="text-decoration-none guide-toggle-btn" data-bs-toggle="collapse" href="#guide-gsd" role="button" aria-expanded="false"><i class="bi bi-mortarboard me-1"></i>How to use this</a>
                    <div class="collapse mt-1" id="guide-gsd">
                        <h6>What is GSD?</h6>
                        <p>Ground Sampling Distance — the real-world size of one pixel in your photo.</p>
                        <h6>Steps</h6>
                        <ol>
                            <li>Set your flight altitude (start with 30m for general use)</li>
                            <li>Set overlap percentage (70% for photos, 80% for 3D mapping)</li>
                            <li>Click "Calculate GSD" to see results</li>
                        </ol>
                        <h6>Reading Results</h6>
                        <ul>
                            <li><strong>GSD (cm/px):</strong> Lower = more detail. Under 2cm is high quality.</li>
                            <li><strong>Quality Tier:</strong> Ultra High (&lt;1cm), High (1-2cm), Standard (2-5cm), Low (&gt;5cm)</li>
                            <li><strong>Footprint:</strong> Ground area covered by a single photo</li>
                            <li><strong>Line Spacing:</strong> Recommended distance between flight lines for your overlap</li>
                            <li><strong>Est. Photos/Time/Battery:</strong> Mission planning estimates</li>
                        </ul>
                    </div>
                </div>
                <?php endif; ?>
                <div id="gsd-panel"></div>
            </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Mission Patterns -->
        <?php if ($_settingsModel->isPanelEnabled('patterns')): ?>
        <div class="card mb-3" data-card-key="patterns">
            <div class="card-header card-header-collapse" role="button" data-bs-toggle="collapse" data-bs-target="#collapse-patterns" aria-expanded="true">
                <span>
                    <i class="bi bi-bullseye"></i> Mission Patterns
                    <i class="bi bi-question-circle-fill text-muted ms-1 fp-tooltip-icon" data-bs-toggle="tooltip" title="Pre-built circular and linear flight paths for inspections and cinematic shots."></i>
                </span>
                <i class="bi bi-chevron-down collapse-chevron"></i>
            </div>
            <div class="collapse show" id="collapse-patterns">
            <div class="card-body">
                <?php if (!empty($settings->guide_mode)): ?>
                <div class="guide-block mb-2">
                    <a class="text-decoration-none guide-toggle-btn" data-bs-toggle="collapse" href="#guide-patterns" role="button" aria-expanded="false"><i class="bi bi-mortarboard me-1"></i>How to use this</a>
                    <div class="collapse mt-1" id="guide-patterns">
                        <h6>Pattern Guide</h6>
                        <ul>
                            <li><strong>Orbit:</strong> Fly in a circle around a point — camera always faces center. Use for tower inspections, monument documentation, real estate.</li>
                            <li><strong>Spiral:</strong> Ascending circle — starts low, climbs each revolution. Use for tall structure inspection (chimneys, masts, wind turbines).</li>
                            <li><strong>Cable Cam:</strong> Straight-line smooth flight between two points. Use for cinematic shots, corridor inspections, power lines.</li>
                            <li><strong>Multi-Altitude Orbit:</strong> Multiple orbits stacked at different heights. Use for complete 360&deg; coverage at every level.</li>
                        </ul>
                        <h6>Steps</h6>
                        <ol>
                            <li>Select a pattern type from the dropdown</li>
                            <li>Set radius, altitude, speed and other parameters</li>
                            <li>Click on the map to set the center/start point</li>
                            <li>Click "Generate Pattern"</li>
                        </ol>
                    </div>
                </div>
                <?php endif; ?>
                <div id="mission-patterns-panel"></div>
            </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Grid Mission -->
        <?php if ($_settingsModel->isPanelEnabled('grid')): ?>
        <div class="card mb-3" data-card-key="grid">
            <div class="card-header card-header-collapse" role="button" data-bs-toggle="collapse" data-bs-target="#collapse-grid" aria-expanded="true">
                <span>
                    <i class="bi bi-grid-3x3"></i> Grid Mission
                    <i class="bi bi-question-circle-fill text-muted ms-1 fp-tooltip-icon" data-bs-toggle="tooltip" title="Generates an automated lawn-mower flight pattern over your survey area. Best for flat terrain mapping and aerial photography."></i>
                </span>
                <i class="bi bi-chevron-down collapse-chevron"></i>
            </div>
            <div class="collapse show" id="collapse-grid">
            <div class="card-body">
                <?php if (!empty($settings->guide_mode)): ?>
                <div class="guide-block mb-2">
                    <a class="text-decoration-none guide-toggle-btn" data-bs-toggle="collapse" href="#guide-grid" role="button" aria-expanded="false"><i class="bi bi-mortarboard me-1"></i>How to use this</a>
                    <div class="collapse mt-1" id="guide-grid">
                        <h6>What is a Grid Mission?</h6>
                        <p>An automated lawn-mower flight pattern that systematically covers a survey area with parallel flight lines.</p>
                        <h6>When to use</h6>
                        <ul>
                            <li>2D mapping / orthomosaic generation</li>
                            <li>Agricultural surveys</li>
                            <li>Large area aerial photography</li>
                            <li>Any time you need consistent coverage of a defined area</li>
                        </ul>
                        <h6>Steps</h6>
                        <ol>
                            <li>First, draw an area polygon on the map (click corners, double-click to finish)</li>
                            <li>Use GSD Calculator to find ideal altitude and line spacing</li>
                            <li>Set Line Spacing — use the GSD calculator's "Line Spacing" result</li>
                            <li>Set Direction — align with the longest edge of your area for efficiency</li>
                            <li>Choose Pattern: Parallel (fast) or Crosshatch (better for 3D)</li>
                            <li>Click "Generate Grid" — waypoints appear on the map</li>
                            <li>Run Coverage Analysis to verify sufficient overlap</li>
                        </ol>
                        <h6>Tips</h6>
                        <ul>
                            <li>Always run GSD Calculator first to determine optimal spacing</li>
                            <li>Crosshatch doubles flight time but greatly improves 3D results</li>
                            <li>Check weather before flying — wind direction affects efficiency</li>
                        </ul>
                    </div>
                </div>
                <?php endif; ?>
                <div id="grid-planner-panel"></div>
            </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Oblique Grid / 3D Mapping -->
        <?php if ($_settingsModel->isPanelEnabled('oblique')): ?>
        <div class="card mb-3" data-card-key="oblique">
            <div class="card-header card-header-collapse" role="button" data-bs-toggle="collapse" data-bs-target="#collapse-oblique" aria-expanded="true">
                <span>
                    <i class="bi bi-grid-3x3-gap"></i> 3D Mapping Grid
                    <i class="bi bi-question-circle-fill text-muted ms-1 fp-tooltip-icon" data-bs-toggle="tooltip" title="Creates 3D photogrammetry missions. Use Double Grid for professional 3D models of areas, Multi-Angle for highest quality."></i>
                </span>
                <i class="bi bi-chevron-down collapse-chevron"></i>
            </div>
            <div class="collapse show" id="collapse-oblique">
            <div class="card-body">
                <?php if (!empty($settings->guide_mode)): ?>
                <div class="guide-block mb-2">
                    <a class="text-decoration-none guide-toggle-btn" data-bs-toggle="collapse" href="#guide-3d" role="button" aria-expanded="false"><i class="bi bi-mortarboard me-1"></i>How to use this</a>
                    <div class="collapse mt-1" id="guide-3d">
                        <h6>What is 3D Mapping?</h6>
                        <p>Creates multi-pass flight patterns for photogrammetry — building 3D models from overlapping photos.</p>
                        <h6>Capture Modes Explained</h6>
                        <ul>
                            <li><strong>Nadir:</strong> Camera points straight down. Good for flat 2D maps only.</li>
                            <li><strong>Oblique:</strong> Camera at an angle. Captures building sides and depth.</li>
                            <li><strong>Double Grid:</strong> Industry standard — one nadir pass + one angled pass at 90&deg;. Best balance of quality and flight time.</li>
                            <li><strong>Multi-Angle:</strong> 5 passes (1 nadir + 4 oblique at N/E/S/W). Highest 3D quality but longest flight time.</li>
                        </ul>
                        <h6>Steps</h6>
                        <ol>
                            <li>Draw your area polygon on the map</li>
                            <li>Select capture mode (start with "Double Grid" for most jobs)</li>
                            <li>Set altitude (50m typical for 3D mapping)</li>
                            <li>Set line spacing (15-25m depending on detail needed)</li>
                            <li>Click "Generate 3D Grid"</li>
                            <li>Run Quality Report to check reconstruction quality</li>
                            <li>If quality is "Poor", try Multi-Angle or decrease spacing</li>
                        </ol>
                    </div>
                </div>
                <?php endif; ?>
                <div id="oblique-planner-panel"></div>
            </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Facade Scanner -->
        <?php if ($_settingsModel->isPanelEnabled('facade')): ?>
        <div class="card mb-3" data-card-key="facade">
            <div class="card-header card-header-collapse" role="button" data-bs-toggle="collapse" data-bs-target="#collapse-facade" aria-expanded="true">
                <span>
                    <i class="bi bi-building"></i> Facade Scanner
                    <i class="bi bi-question-circle-fill text-muted ms-1 fp-tooltip-icon" data-bs-toggle="tooltip" title="Scans building walls and structures vertically. Use for building inspections, facade surveys, and structural assessments."></i>
                </span>
                <i class="bi bi-chevron-down collapse-chevron"></i>
            </div>
            <div class="collapse show" id="collapse-facade">
            <div class="card-body">
                <?php if (!empty($settings->guide_mode)): ?>
                <div class="guide-block mb-2">
                    <a class="text-decoration-none guide-toggle-btn" data-bs-toggle="collapse" href="#guide-facade" role="button" aria-expanded="false"><i class="bi bi-mortarboard me-1"></i>How to use this</a>
                    <div class="collapse mt-1" id="guide-facade">
                        <h6>What is Facade Scanning?</h6>
                        <p>Automated flight pattern for inspecting building walls — the drone flies vertical columns at a set distance from the facade.</p>
                        <h6>When to use</h6>
                        <ul>
                            <li>Building inspections (cracks, damage, weathering)</li>
                            <li>Facade documentation and measurement</li>
                            <li>Structural assessment surveys</li>
                            <li>Heritage building recording</li>
                        </ul>
                        <h6>Steps</h6>
                        <ol>
                            <li>Choose Scan Mode: Single Face (click 2 points) or Multi-Face (from polygon)</li>
                            <li>Set Standoff Distance (how far from the wall — 10m is safe default)</li>
                            <li>Set Column Spacing (5m typical — closer for more detail)</li>
                            <li>Set Min/Max Altitude to match the building height</li>
                            <li>Set Altitude Step (5m gives good vertical overlap)</li>
                            <li>Click "Generate Facade Scan"</li>
                            <li>Review waypoints — drone will fly vertical zigzag columns</li>
                        </ol>
                        <h6>Safety</h6>
                        <ul>
                            <li>Always check for obstacles (aerials, wires, overhangs)</li>
                            <li>Standoff must account for GPS drift (minimum 5m from structure)</li>
                            <li>Check wind — gusts near buildings can be unpredictable</li>
                        </ul>
                    </div>
                </div>
                <?php endif; ?>
                <div id="facade-planner-panel"></div>
            </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Coverage Analysis -->
        <?php if ($_settingsModel->isPanelEnabled('coverage')): ?>
        <div class="card mb-3" data-card-key="coverage">
            <div class="card-header card-header-collapse d-flex justify-content-between align-items-center" role="button" data-bs-toggle="collapse" data-bs-target="#collapse-coverage" aria-expanded="true">
                <span>
                    <i class="bi bi-grid-fill"></i> Coverage Analysis
                    <i class="bi bi-question-circle-fill text-muted ms-1 fp-tooltip-icon" data-bs-toggle="tooltip" title="Shows a heatmap of photo overlap across your mission area — helps verify no gaps before you fly."></i>
                </span>
                <div class="d-flex align-items-center gap-2">
                    <button class="btn btn-sm btn-outline-secondary" id="btn-run-coverage" onclick="event.stopPropagation()">
                        <i class="bi bi-play"></i> Analyse
                    </button>
                    <i class="bi bi-chevron-down collapse-chevron"></i>
                </div>
            </div>
            <div class="collapse show" id="collapse-coverage">
            <div class="card-body" id="coverage-panel">
                <?php if (!empty($settings->guide_mode)): ?>
                <div class="guide-block mb-2">
                    <a class="text-decoration-none guide-toggle-btn" data-bs-toggle="collapse" href="#guide-coverage" role="button" aria-expanded="false"><i class="bi bi-mortarboard me-1"></i>How to use this</a>
                    <div class="collapse mt-1" id="guide-coverage">
                        <h6>How to read</h6>
                        <ul>
                            <li><strong>Red/Orange:</strong> Low overlap (0-2 images). May have gaps in final output.</li>
                            <li><strong>Yellow:</strong> Moderate overlap (2-3 images). Acceptable for 2D maps.</li>
                            <li><strong>Green:</strong> Good overlap (4+ images). Required for quality 3D models.</li>
                        </ul>
                        <h6>Steps</h6>
                        <ol>
                            <li>Generate your mission waypoints first (Grid, 3D Grid, etc.)</li>
                            <li>Click "Analyse" to compute the heatmap</li>
                            <li>Check statistics: Avg Overlap, Coverage Area, Sufficient %</li>
                            <li>If coverage is poor, decrease line spacing or increase overlap %</li>
                        </ol>
                    </div>
                </div>
                <?php endif; ?>
                <p class="text-muted small mb-0">Click "Analyse" to compute photo overlap coverage.</p>
            </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Quality Report -->
        <?php if ($_settingsModel->isPanelEnabled('quality')): ?>
        <div class="card mb-3" data-card-key="quality">
            <div class="card-header card-header-collapse d-flex justify-content-between align-items-center" role="button" data-bs-toggle="collapse" data-bs-target="#collapse-quality" aria-expanded="true">
                <span>
                    <i class="bi bi-award"></i> Quality Report
                    <i class="bi bi-question-circle-fill text-muted ms-1 fp-tooltip-icon" data-bs-toggle="tooltip" title="Predicts how good your 3D reconstruction will be before you fly — saving time and battery."></i>
                </span>
                <div class="d-flex align-items-center gap-2">
                    <button class="btn btn-sm btn-outline-secondary" id="btn-run-quality" onclick="event.stopPropagation()">
                        <i class="bi bi-play"></i> Analyse
                    </button>
                    <i class="bi bi-chevron-down collapse-chevron"></i>
                </div>
            </div>
            <div class="collapse show" id="collapse-quality">
            <div class="card-body" id="quality-panel">
                <?php if (!empty($settings->guide_mode)): ?>
                <div class="guide-block mb-2">
                    <a class="text-decoration-none guide-toggle-btn" data-bs-toggle="collapse" href="#guide-quality" role="button" aria-expanded="false"><i class="bi bi-mortarboard me-1"></i>How to use this</a>
                    <div class="collapse mt-1" id="guide-quality">
                        <h6>Steps</h6>
                        <ol>
                            <li>Generate mission waypoints first</li>
                            <li>Click "Analyse" to run the report</li>
                            <li>Review the traffic-light metrics</li>
                        </ol>
                        <h6>Metrics Explained</h6>
                        <ul>
                            <li><strong>Image Overlap:</strong> How many photos cover each area (green: 5+)</li>
                            <li><strong>Coverage:</strong> Percentage of area captured (green: 90%+)</li>
                            <li><strong>GSD:</strong> Photo resolution (green: under 2cm/pixel)</li>
                            <li><strong>Convergence:</strong> Angle between overlapping views (green: 45&deg;+ — critical for 3D depth)</li>
                            <li><strong>Point Density:</strong> Expected 3D points per square metre</li>
                            <li><strong>Capture Mode:</strong> Double Grid or Multi-Angle are best for 3D</li>
                        </ul>
                        <p>If you see red metrics, follow the recommendations at the bottom of the report.</p>
                    </div>
                </div>
                <?php endif; ?>
                <p class="text-muted small mb-0">Click "Analyse" to estimate 3D reconstruction quality.</p>
            </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Waypoint panel (always expanded) -->
        <div class="card mb-3" data-card-key="waypoints">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-signpost-2"></i> Waypoints</span>
                <div>
                    <button class="btn btn-sm btn-outline-danger" id="btn-clear-waypoints">
                        <i class="bi bi-trash"></i> Clear
                    </button>
                    <button class="btn btn-sm btn-primary" id="btn-save-waypoints">
                        <i class="bi bi-save"></i> Save
                    </button>
                </div>
            </div>
            <div class="card-body waypoint-panel" id="waypoint-list">
                <p class="text-muted small">Click on the map to add waypoints.</p>
            </div>
        </div>

        <!-- Order Info (collapsible, starts collapsed) -->
        <div class="card mb-3">
            <div class="card-header card-header-collapse" role="button" data-bs-toggle="collapse" data-bs-target="#collapse-order" aria-expanded="false">
                <span><i class="bi bi-clipboard2-check"></i> Order</span>
                <i class="bi bi-chevron-right collapse-chevron"></i>
            </div>
            <div class="collapse" id="collapse-order">
                <div class="card-body">
                    <?php if (!empty($order)): ?>
                    <p class="mb-1">
                        <strong>Status:</strong>
                        <span class="badge badge-<?= $order->status ?>">
                            <?= ucwords(str_replace('_', ' ', $order->status)) ?>
                        </span>
                    </p>
                    <?php if (!empty($order->pilot_id)):
                        $pilot_user = \Config\Database::connect()->table('users')->where('id', $order->pilot_id)->get()->getRow();
                    ?>
                    <?php if ($pilot_user): ?>
                    <p class="mb-1"><strong>Pilot:</strong> <?= esc($pilot_user->display_name) ?></p>
                    <?php endif; ?>
                    <?php endif; ?>
                    <?php if (!empty($order->scheduled_date)): ?>
                    <p class="mb-1"><strong>Scheduled:</strong> <?= date('d M Y', strtotime($order->scheduled_date)) ?>
                        <?php if (!empty($order->scheduled_time)): ?> at <?= esc($order->scheduled_time) ?><?php endif; ?>
                    </p>
                    <?php endif; ?>
                    <a href="<?= site_url('orders/' . $order->id) ?>" class="btn btn-sm btn-outline-primary mt-1">
                        <i class="bi bi-eye"></i> View Order
                    </a>
                    <?php else: ?>
                    <p class="text-muted mb-2">No order created yet.</p>
                    <button class="btn btn-sm btn-outline-success"
                            data-bs-toggle="modal" data-bs-target="#assignModal"
                            onclick="document.getElementById('assignForm').action='<?= site_url('orders/create/' . $flight_plan->id) ?>'">
                        <i class="bi bi-person-plus"></i> Create Order & Assign Pilot
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Weather (collapsible, starts collapsed) -->
        <?php if ($_settingsModel->isModuleEnabled('analytics')): ?>
        <div class="card mb-3">
            <div class="card-header card-header-collapse d-flex justify-content-between align-items-center" role="button" data-bs-toggle="collapse" data-bs-target="#collapse-weather" aria-expanded="false">
                <span><i class="bi bi-cloud-sun"></i> Weather</span>
                <div class="d-flex align-items-center gap-2">
                    <button class="btn btn-sm btn-outline-secondary" id="btn-load-weather" onclick="event.stopPropagation()">
                        <i class="bi bi-arrow-repeat"></i> Refresh
                    </button>
                    <i class="bi bi-chevron-right collapse-chevron"></i>
                </div>
            </div>
            <div class="collapse" id="collapse-weather">
                <div class="card-body" id="weather-panel">
                    <p class="text-muted small mb-0">Click "Refresh" to load live weather.</p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Admin notes (collapsible, starts collapsed) -->
        <div class="card mb-3">
            <div class="card-header card-header-collapse" role="button" data-bs-toggle="collapse" data-bs-target="#collapse-notes" aria-expanded="false">
                <span><i class="bi bi-journal-text"></i> Pilot Notes</span>
                <i class="bi bi-chevron-right collapse-chevron"></i>
            </div>
            <div class="collapse" id="collapse-notes">
                <div class="card-body">
                    <textarea class="form-control" id="admin-notes" rows="3"
                              placeholder="Internal notes..."><?= esc($flight_plan->admin_notes ?? '') ?></textarea>
                    <button class="btn btn-sm btn-outline-primary mt-2" id="btn-save-notes">
                        <i class="bi bi-save"></i> Save Notes
                    </button>
                </div>
            </div>
        </div>
    </div>

        <?php if (!empty($_settingsModel->getSettings()->solo_mode) && !empty($order)): ?>
        <!-- Solo Operator Workflow -->
        <div class="card mb-3 border-primary">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-person-check"></i> Solo Workflow
                <span class="badge bg-light text-primary ms-2"><?= ucwords(str_replace('_', ' ', $order->status)) ?></span>
            </div>
            <div class="card-body">
                <?php
                $soloTransitions = [
                    'accepted' => ['in_progress'],
                    'in_progress' => ['flight_complete'],
                    'flight_complete' => ['delivered'],
                    'delivered' => ['closed'],
                ];
                $allowed = $soloTransitions[$order->status] ?? [];
                ?>

                <?php if (!empty($allowed)): ?>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Update Status</label>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($allowed as $next): ?>
                        <form method="POST" action="<?= site_url('orders/' . $order->id . '/status') ?>" class="d-inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="status" value="<?= $next ?>">
                            <button type="submit" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-arrow-right-circle"></i> <?= ucwords(str_replace('_', ' ', $next)) ?>
                            </button>
                        </form>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($order->status === 'closed'): ?>
                <div class="alert alert-success py-2 mb-0 small"><i class="bi bi-check-circle"></i> Job completed and closed.</div>
                <?php endif; ?>

                <?php if ($_settingsModel->isModuleEnabled('compliance')): ?>
                <div class="d-flex flex-wrap gap-2 mt-2">
                    <a href="<?= site_url('pilot/orders/' . $order->id . '/flight-params') ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-sliders"></i> Flight Params
                    </a>
                    <a href="<?= site_url('pilot/orders/' . $order->id . '/risk-assessment') ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-clipboard-check"></i> Risk Assessment
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

    <!-- Right: Map -->
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-map"></i> Flight Route Editor</span>
                <div class="d-flex align-items-center gap-2">
                    <button class="btn btn-sm btn-outline-info" id="btn-ruler" data-bs-toggle="tooltip" title="Measure distance between points on the map">
                        <i class="bi bi-rulers"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-warning" id="btn-coverage-toggle" data-bs-toggle="tooltip" title="Show/hide photo overlap heatmap on the map">
                        <i class="bi bi-grid-fill"></i>
                    </button>
                    <?php if ($_settingsModel->isModuleEnabled('analytics')): ?>
                    <button class="btn btn-sm btn-outline-danger" id="btn-airspace" data-bs-toggle="tooltip" title="Show/hide controlled airspace zones and restrictions">
                        <i class="bi bi-broadcast"></i>
                    </button>
                    <?php endif; ?>
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" id="toggle-satellite">
                        <label class="form-check-label small" for="toggle-satellite">Satellite</label>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div id="admin-map"></div>
            </div>
        </div>

        <!-- 3D Preview -->
        <div class="card mt-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-box"></i> 3D Mission Preview</span>
                <button class="btn btn-sm btn-outline-secondary" id="btn-load-3d" data-bs-toggle="tooltip" title="Load a 3D visualisation of your mission waypoints and flight path">
                    <i class="bi bi-play"></i> Load 3D
                </button>
            </div>
            <div class="card-body p-0" id="three-preview-container" style="display:none;">
                <canvas id="three-preview-canvas" style="width:100%;"></canvas>
            </div>
        </div>

        <!-- Elevation Profile -->
        <div class="card mt-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-graph-up"></i> Elevation Profile</span>
                <div class="d-flex gap-1">
                    <button class="btn btn-sm btn-outline-secondary" id="btn-load-elevation" data-bs-toggle="tooltip" title="Fetch ground elevation data for all waypoints to see terrain height changes">
                        <i class="bi bi-arrow-repeat"></i> Load Terrain
                    </button>
                    <button class="btn btn-sm btn-outline-warning" id="btn-terrain-follow" data-bs-toggle="tooltip" title="Automatically adjust waypoint altitudes to maintain consistent height above ground level">
                        <i class="bi bi-bezier2"></i> Terrain Follow
                    </button>
                </div>
            </div>
            <div class="card-body p-2">
                <canvas id="elevation-canvas" style="width:100%; display:none;"></canvas>
                <p class="text-muted small mb-0" id="elevation-placeholder">Click "Load Terrain" to fetch elevation data for waypoints.</p>
            </div>
        </div>
    </div>
</div>

<?= $this->include('partials/assign_modal') ?>

<!-- Hidden data -->
<input type="hidden" id="plan-id" value="<?= esc($flight_plan->id) ?>">
<input type="hidden" id="plan-lat" value="<?= esc($flight_plan->location_lat ?? '') ?>">
<input type="hidden" id="plan-lng" value="<?= esc($flight_plan->location_lng ?? '') ?>">
<input type="hidden" id="plan-polygon" value="<?= esc($flight_plan->area_polygon ?? '') ?>">
<input type="hidden" id="plan-pois" value='<?= $pois_json ?>'>
<input type="hidden" id="plan-waypoints" value='<?= $waypoints_json ?>'>
<input type="hidden" id="plan-job-type" value="<?= esc($flight_plan->job_type ?? '') ?>">
<input type="hidden" id="drone-profiles" value='<?= json_encode($drone_profiles) ?>'>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<input type="hidden" id="app-base-url" value="<?= rtrim(base_url(), '/') ?>">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.js"></script>
<script src="https://unpkg.com/three@0.160.0/build/three.min.js"></script>
<script src="https://unpkg.com/three@0.160.0/examples/js/controls/OrbitControls.js"></script>
<script src="<?= base_url('static/js/airspace-layer.js') ?>"></script>
<script src="<?= base_url('static/js/weather-panel.js') ?>"></script>
<script src="<?= base_url('static/js/map-measure.js') ?>"></script>
<script src="<?= base_url('static/js/elevation-profile.js') ?>"></script>
<script src="<?= base_url('static/js/path-tools.js') ?>"></script>
<script src="<?= base_url('static/js/gsd-calculator.js') ?>"></script>
<script src="<?= base_url('static/js/mission-patterns.js') ?>"></script>
<script src="<?= base_url('static/js/grid-planner.js') ?>"></script>
<script src="<?= base_url('static/js/oblique-planner.js') ?>"></script>
<script src="<?= base_url('static/js/facade-planner.js') ?>"></script>
<script src="<?= base_url('static/js/coverage-heatmap.js') ?>"></script>
<script src="<?= base_url('static/js/three-preview.js') ?>"></script>
<script src="<?= base_url('static/js/quality-report.js') ?>"></script>
<script src="<?= base_url('static/js/workflow-manager.js') ?>"></script>
<script src="<?= base_url('static/js/map-toolbox.js') ?>"></script>
<script src="<?= base_url('static/js/camera-viz.js') ?>"></script>
<script src="<?= base_url('static/js/map-admin.js') ?>"></script>
<script>
// Initialize Bootstrap tooltips (including those added dynamically by JS panels)
function initTooltips() {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function(el) {
        if (!bootstrap.Tooltip.getInstance(el)) {
            new bootstrap.Tooltip(el);
        }
    });
}
// Run on page load, and again after panels build (slight delay for JS panel rendering)
document.addEventListener("DOMContentLoaded", function() {
    setTimeout(initTooltips, 500);
});
</script>
<?= $this->endSection() ?>
