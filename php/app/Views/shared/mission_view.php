<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($flight_plan->reference) ?> - Shared Mission</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        #shared-map { height: 500px; border-radius: 8px; }
        body { background: #f8f9fa; }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="mb-0"><i class="bi bi-drone"></i> <?= esc($flight_plan->reference) ?></h4>
                <small class="text-muted">Shared Mission View</small>
            </div>
            <div>
                <span class="badge bg-light text-dark"><i class="bi bi-signpost-2"></i> <?= $waypoint_count ?> waypoints</span>
                <span class="badge bg-light text-dark"><i class="bi bi-arrows-angle-expand"></i>
                    <?php if ($total_distance_m < 1000): ?>
                        <?= $total_distance_m ?>m
                    <?php else: ?>
                        <?= $total_distance_km ?>km
                    <?php endif; ?>
                </span>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body p-0">
                <div id="shared-map"></div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header"><i class="bi bi-clipboard-check"></i> Job Details</div>
                    <div class="card-body small">
                        <p class="mb-1"><strong>Type:</strong> <?= ucwords(str_replace('_', ' ', $flight_plan->job_type)) ?></p>
                        <?php if (!empty($flight_plan->location_address)): ?>
                        <p class="mb-1"><strong>Location:</strong> <?= esc($flight_plan->location_address) ?></p>
                        <?php endif; ?>
                        <?php if (!empty($flight_plan->job_description)): ?>
                        <p class="mb-0"><?= esc($flight_plan->job_description) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header"><i class="bi bi-list-ol"></i> Waypoints</div>
                    <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                        <table class="table table-sm mb-0">
                            <thead><tr><th>#</th><th>Lat</th><th>Lng</th><th>Alt</th><th>Speed</th></tr></thead>
                            <tbody>
                                <?php foreach ($waypoints as $w): ?>
                                <tr>
                                    <td><?= $w->index ?></td>
                                    <td><?= number_format($w->lat, 5) ?></td>
                                    <td><?= number_format($w->lng, 5) ?></td>
                                    <td><?= $w->altitude_m ?>m</td>
                                    <td><?= $w->speed_ms ?>m/s</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center mt-3">
            <small class="text-muted">Powered by FlyingPlan</small>
        </div>
    </div>

    <input type="hidden" id="plan-lat" value="<?= $flight_plan->location_lat ?>">
    <input type="hidden" id="plan-lng" value="<?= $flight_plan->location_lng ?>">
    <input type="hidden" id="plan-polygon" value="<?= esc($flight_plan->area_polygon ?? '') ?>">
    <input type="hidden" id="plan-pois" value='<?= $pois_json ?>'>
    <input type="hidden" id="plan-waypoints" value='<?= $waypoints_json ?>'>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="<?= base_url('static/js/map-shared.js') ?>"></script>
</body>
</html>
