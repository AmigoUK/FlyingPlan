<?= $this->extend('layouts/base') ?>
<?= $this->section('title') ?>Request a Drone Flight<?= $this->endSection() ?>

<?= $this->section('head_extra') ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php $settings = (new \App\Models\AppSettingsModel())->getSettings(); ?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <h2 class="text-center mb-4"><i class="bi bi-send"></i> Request a Drone Flight</h2>

        <!-- Progress -->
        <div class="d-flex justify-content-between mb-4" id="wizard-progress">
            <div class="text-center flex-fill wizard-step active" data-step="1"><span class="badge bg-primary rounded-pill">1</span><br><small>Customer</small></div>
            <div class="text-center flex-fill wizard-step" data-step="2"><span class="badge bg-secondary rounded-pill">2</span><br><small>Brief</small></div>
            <div class="text-center flex-fill wizard-step" data-step="3"><span class="badge bg-secondary rounded-pill">3</span><br><small>Location</small></div>
            <div class="text-center flex-fill wizard-step" data-step="4"><span class="badge bg-secondary rounded-pill">4</span><br><small>Preferences</small></div>
            <div class="text-center flex-fill wizard-step" data-step="5"><span class="badge bg-secondary rounded-pill">5</span><br><small>Review</small></div>
        </div>

        <form method="POST" action="<?= site_url('submit') ?>" enctype="multipart/form-data" id="flight-form">
            <?= csrf_field() ?>

            <!-- Step 1: Customer Details -->
            <div class="wizard-panel" data-step="1">
                <div class="card"><div class="card-body">
                    <h5 class="card-title"><i class="bi bi-person"></i> Customer Details</h5>
                    <div class="mb-3"><label class="form-label">Full Name *</label><input type="text" name="customer_name" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Email *</label><input type="email" name="customer_email" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Phone</label><input type="tel" name="customer_phone" class="form-control"></div>
                    <div class="mb-3"><label class="form-label">Company</label><input type="text" name="customer_company" class="form-control"></div>
                    <?php if ($settings->show_customer_type_toggle): ?>
                    <div class="mb-3"><label class="form-label">Customer Type</label>
                        <select name="customer_type" class="form-select" id="customer-type-select">
                            <option value="private">Private</option><option value="business">Business</option>
                        </select>
                    </div>
                    <div id="business-fields" style="display:none;">
                        <div class="mb-3"><label class="form-label">ABN/Company Number</label><input type="text" name="business_abn" class="form-control"></div>
                        <div class="mb-3"><label class="form-label">Billing Contact</label><input type="text" name="billing_contact" class="form-control"></div>
                        <div class="mb-3"><label class="form-label">Billing Email</label><input type="email" name="billing_email" class="form-control"></div>
                        <div class="mb-3"><label class="form-label">Purchase Order #</label><input type="text" name="purchase_order" class="form-control"></div>
                    </div>
                    <?php else: ?>
                    <input type="hidden" name="customer_type" value="private">
                    <?php endif; ?>
                    <?php if ($settings->show_heard_about): ?>
                    <div class="mb-3"><label class="form-label">How did you hear about us?</label>
                        <select name="heard_about" class="form-select">
                            <option value="">Select...</option>
                            <?php foreach ((new \App\Models\HeardAboutOptionModel())->getActive() as $ha): ?>
                            <option value="<?= $ha->value ?>"><?= esc($ha->label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    <div class="text-end"><button type="button" class="btn btn-primary btn-next">Next <i class="bi bi-arrow-right"></i></button></div>
                </div></div>
            </div>

            <!-- Step 2: Job Brief -->
            <div class="wizard-panel" data-step="2" style="display:none;">
                <div class="card"><div class="card-body">
                    <h5 class="card-title"><i class="bi bi-clipboard"></i> Job Brief</h5>
                    <div class="mb-3"><label class="form-label">Job Type *</label>
                        <select name="job_type" class="form-select" required>
                            <option value="">Select...</option>
                            <?php foreach ((new \App\Models\JobTypeModel())->getActive() as $jt): ?>
                            <option value="<?= $jt->value ?>"><?= esc($jt->label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">Job Description *</label><textarea name="job_description" class="form-control" rows="4" required></textarea></div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Preferred Dates</label><input type="text" name="preferred_dates" class="form-control" placeholder="e.g. Next week, 15-20 March"></div>
                        <div class="col-md-3 mb-3"><label class="form-label">Time Window</label><input type="text" name="time_window" class="form-control" placeholder="e.g. Morning"></div>
                        <div class="col-md-3 mb-3"><label class="form-label">Urgency</label>
                            <select name="urgency" class="form-select"><option value="normal">Normal</option><option value="low">Low</option><option value="high">High</option><option value="urgent">Urgent</option></select>
                        </div>
                    </div>
                    <div class="mb-3"><label class="form-label">Special Requirements</label><textarea name="special_requirements" class="form-control" rows="2"></textarea></div>
                    <div class="mb-3"><label class="form-label">Attachments</label><input type="file" name="attachments[]" class="form-control" multiple accept=".png,.jpg,.jpeg,.gif,.pdf,.doc,.docx"></div>
                    <div class="d-flex justify-content-between"><button type="button" class="btn btn-outline-secondary btn-prev"><i class="bi bi-arrow-left"></i> Back</button><button type="button" class="btn btn-primary btn-next">Next <i class="bi bi-arrow-right"></i></button></div>
                </div></div>
            </div>

            <!-- Step 3: Location -->
            <div class="wizard-panel" data-step="3" style="display:none;">
                <div class="card"><div class="card-body">
                    <h5 class="card-title"><i class="bi bi-geo-alt"></i> Location</h5>
                    <div class="mb-3"><label class="form-label">Address</label><input type="text" name="location_address" class="form-control" placeholder="Street address or description"></div>
                    <p class="small text-muted">Click on the map to set the location pin. Draw a polygon for the survey area.</p>
                    <div id="customer-map" style="height: 400px; border-radius: 8px; border: 1px solid #ddd;"></div>
                    <input type="hidden" id="location_lat" name="location_lat">
                    <input type="hidden" id="location_lng" name="location_lng">
                    <input type="hidden" id="area_polygon" name="area_polygon">
                    <input type="hidden" id="pois_json" name="pois_json">
                    <div class="d-flex justify-content-between mt-3"><button type="button" class="btn btn-outline-secondary btn-prev"><i class="bi bi-arrow-left"></i> Back</button><button type="button" class="btn btn-primary btn-next">Next <i class="bi bi-arrow-right"></i></button></div>
                </div></div>
            </div>

            <!-- Step 4: Flight Preferences -->
            <div class="wizard-panel" data-step="4" style="display:none;">
                <div class="card"><div class="card-body">
                    <h5 class="card-title"><i class="bi bi-sliders"></i> Flight Preferences</h5>
                    <div class="row">
                        <div class="col-md-4 mb-3"><label class="form-label">Altitude</label>
                            <select name="altitude_preset" class="form-select" id="altitude-select"><option value="low">Low (15-30m)</option><option value="medium" selected>Medium (30-60m)</option><option value="high">High (60-120m)</option><option value="custom">Custom</option></select>
                        </div>
                        <div class="col-md-4 mb-3" id="custom-altitude-group" style="display:none;"><label class="form-label">Custom Altitude (m)</label><input type="number" name="altitude_custom_m" class="form-control" min="5" max="120"></div>
                        <div class="col-md-4 mb-3"><label class="form-label">Camera Angle</label>
                            <select name="camera_angle" class="form-select"><option value="">Pilot Decides</option><option value="nadir">Nadir (Straight Down)</option><option value="oblique">Oblique (45°)</option><option value="horizontal">Horizontal</option></select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3"><label class="form-label">Video Resolution</label>
                            <select name="video_resolution" class="form-select"><option value="4K">4K</option><option value="1080p">1080p</option><option value="2.7K">2.7K</option></select>
                        </div>
                        <div class="col-md-4 mb-3"><label class="form-label">Photo Mode</label>
                            <select name="photo_mode" class="form-select"><option value="single">Single</option><option value="burst">Burst</option><option value="interval">Interval</option><option value="hdr">HDR</option></select>
                        </div>
                    </div>
                    <?php if ($settings->show_purpose_fields): ?>
                    <div class="mb-3"><label class="form-label">Footage Purpose</label>
                        <select name="footage_purpose" class="form-select">
                            <option value="">Select...</option>
                            <?php foreach ((new \App\Models\PurposeOptionModel())->getActive() as $po): ?>
                            <option value="<?= $po->value ?>"><?= esc($po->label) ?></option>
                            <?php endforeach; ?>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3" id="purpose-other-group" style="display:none;"><input type="text" name="footage_purpose_other" class="form-control" placeholder="Describe purpose"></div>
                    <?php endif; ?>
                    <div class="mb-3"><label class="form-label">No-Fly Zone Notes</label><textarea name="no_fly_notes" class="form-control" rows="2" placeholder="Any areas to avoid?"></textarea></div>
                    <div class="mb-3"><label class="form-label">Privacy Concerns</label><textarea name="privacy_notes" class="form-control" rows="2" placeholder="Neighbours, restricted views, etc."></textarea></div>
                    <input type="hidden" name="shot_types_json" id="shot_types_json">
                    <input type="hidden" name="delivery_timeline" value="standard">
                    <div class="d-flex justify-content-between"><button type="button" class="btn btn-outline-secondary btn-prev"><i class="bi bi-arrow-left"></i> Back</button><button type="button" class="btn btn-primary btn-next">Next <i class="bi bi-arrow-right"></i></button></div>
                </div></div>
            </div>

            <!-- Step 5: Review & Submit -->
            <div class="wizard-panel" data-step="5" style="display:none;">
                <div class="card"><div class="card-body">
                    <h5 class="card-title"><i class="bi bi-check2-square"></i> Review & Submit</h5>
                    <p class="text-muted">Please review your submission details before sending.</p>
                    <div id="review-summary" class="mb-3">
                        <p><strong>Name:</strong> <span id="rev-name"></span></p>
                        <p><strong>Email:</strong> <span id="rev-email"></span></p>
                        <p><strong>Job Type:</strong> <span id="rev-job-type"></span></p>
                    </div>
                    <div class="form-check mb-3">
                        <input type="checkbox" class="form-check-input" name="consent_given" id="consent" value="1" required>
                        <label class="form-check-label" for="consent">I confirm the information provided is accurate and I consent to the collection of aerial imagery at the specified location.</label>
                    </div>
                    <div class="d-flex justify-content-between"><button type="button" class="btn btn-outline-secondary btn-prev"><i class="bi bi-arrow-left"></i> Back</button><button type="submit" class="btn btn-success"><i class="bi bi-send"></i> Submit Flight Brief</button></div>
                </div></div>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="<?= base_url('static/js/map-customer.js') ?>"></script>
<script src="<?= base_url('static/js/form-wizard.js') ?>"></script>
<script>
document.getElementById('customer-type-select')?.addEventListener('change', function() {
    document.getElementById('business-fields').style.display = this.value === 'business' ? 'block' : 'none';
});
document.getElementById('altitude-select')?.addEventListener('change', function() {
    document.getElementById('custom-altitude-group').style.display = this.value === 'custom' ? 'block' : 'none';
});
</script>
<?= $this->endSection() ?>
