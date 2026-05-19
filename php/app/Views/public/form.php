<?= $this->extend('layouts/base') ?>
<?= $this->section('title') ?>Drone Flight Brief - FlyingPlan<?= $this->endSection() ?>

<?= $this->section('head_extra') ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.css" />
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php $settings = (new \App\Models\AppSettingsModel())->getSettings(); ?>
<?php $_settingsModel = new \App\Models\AppSettingsModel(); ?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <h2 class="mb-2"><i class="bi bi-airplane"></i> Drone Flight Brief</h2>
        <p class="text-muted mb-4">Fill out the details for your drone flight or inspection request.</p>

        <!-- Progress -->
        <div class="step-progress mb-4">
            <div class="step active" data-step="1">Your Details</div>
            <div class="step" data-step="2">Job Brief</div>
            <div class="step" data-step="3">Location</div>
            <div class="step" data-step="4">Preferences</div>
            <div class="step" data-step="5">Review</div>
        </div>

        <form id="flightPlanForm" method="POST" action="<?= site_url('submit') ?>"
              enctype="multipart/form-data" novalidate>
            <?= csrf_field() ?>

            <!-- Step 1: Customer Details -->
            <div class="form-step active" data-step="1">
                <h4 class="mb-3"><i class="bi bi-person"></i> Your Details</h4>

                <?php if ($_settingsModel->getFieldMode('customer_type') !== 'hidden'): ?>
                <div class="mb-3">
                    <label class="form-label">Customer Type<?php if ($_settingsModel->getFieldMode('customer_type') === 'required'): ?> <span class="text-danger">*</span><?php endif; ?></label>
                    <div class="btn-group w-100" role="group">
                        <input type="radio" class="btn-check" name="customer_type" id="ct-private"
                               value="private" checked autocomplete="off">
                        <label class="btn btn-outline-primary" for="ct-private">
                            <i class="bi bi-person"></i> Private
                        </label>
                        <input type="radio" class="btn-check" name="customer_type" id="ct-business"
                               value="business" autocomplete="off">
                        <label class="btn btn-outline-primary" for="ct-business">
                            <i class="bi bi-building"></i> Business
                        </label>
                    </div>
                </div>
                <?php else: ?>
                <input type="hidden" name="customer_type" value="private">
                <?php endif; ?>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="customer_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="customer_name" name="customer_name" required>
                        <div class="invalid-feedback">Please enter your name.</div>
                    </div>
                    <div class="col-md-6">
                        <label for="customer_email" class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="customer_email" name="customer_email" required>
                        <div class="invalid-feedback">Please enter a valid email.</div>
                    </div>
                    <?php if ($_settingsModel->getFieldMode('customer_phone') !== 'hidden'): ?>
                    <div class="col-md-6">
                        <label for="customer_phone" class="form-label">Phone<?php if ($_settingsModel->getFieldMode('customer_phone') === 'required'): ?> <span class="text-danger">*</span><?php endif; ?></label>
                        <input type="tel" class="form-control" id="customer_phone" name="customer_phone"<?php if ($_settingsModel->getFieldMode('customer_phone') === 'required'): ?> required<?php endif; ?>>
                    </div>
                    <?php endif; ?>
                    <?php if ($_settingsModel->getFieldMode('customer_company') !== 'hidden'): ?>
                    <div class="col-md-6">
                        <label for="customer_company" class="form-label">
                            Company <span id="company-required-star" class="text-danger" style="display: none;">*</span>
                        </label>
                        <input type="text" class="form-control" id="customer_company" name="customer_company"<?php if ($_settingsModel->getFieldMode('customer_company') === 'required'): ?> required<?php endif; ?>>
                        <div class="invalid-feedback">Company name is required for business customers.</div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Business-only fields -->
                <div id="business-fields" style="display: none;">
                    <div class="row g-3 mt-1">
                        <?php if ($_settingsModel->getFieldMode('business_abn') !== 'hidden'): ?>
                        <div class="col-md-6">
                            <label for="business_abn" class="form-label">Company Reg / VAT Number<?php if ($_settingsModel->getFieldMode('business_abn') === 'required'): ?> <span class="text-danger">*</span><?php endif; ?></label>
                            <input type="text" class="form-control" id="business_abn" name="business_abn"<?php if ($_settingsModel->getFieldMode('business_abn') === 'required'): ?> required<?php endif; ?>>
                        </div>
                        <?php endif; ?>
                        <?php if ($_settingsModel->getFieldMode('billing_contact') !== 'hidden'): ?>
                        <div class="col-md-6">
                            <label for="billing_contact" class="form-label">Billing Contact<?php if ($_settingsModel->getFieldMode('billing_contact') === 'required'): ?> <span class="text-danger">*</span><?php endif; ?></label>
                            <input type="text" class="form-control" id="billing_contact" name="billing_contact"<?php if ($_settingsModel->getFieldMode('billing_contact') === 'required'): ?> required<?php endif; ?>>
                        </div>
                        <?php endif; ?>
                        <?php if ($_settingsModel->getFieldMode('billing_email') !== 'hidden'): ?>
                        <div class="col-md-6">
                            <label for="billing_email" class="form-label">Billing Email<?php if ($_settingsModel->getFieldMode('billing_email') === 'required'): ?> <span class="text-danger">*</span><?php endif; ?></label>
                            <input type="email" class="form-control" id="billing_email" name="billing_email"<?php if ($_settingsModel->getFieldMode('billing_email') === 'required'): ?> required<?php endif; ?>>
                        </div>
                        <?php endif; ?>
                        <?php if ($_settingsModel->getFieldMode('purchase_order') !== 'hidden'): ?>
                        <div class="col-md-6">
                            <label for="purchase_order" class="form-label">Purchase Order #<?php if ($_settingsModel->getFieldMode('purchase_order') === 'required'): ?> <span class="text-danger">*</span><?php endif; ?></label>
                            <input type="text" class="form-control" id="purchase_order" name="purchase_order"<?php if ($_settingsModel->getFieldMode('purchase_order') === 'required'): ?> required<?php endif; ?>>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($_settingsModel->getFieldMode('heard_about') !== 'hidden'): ?>
                <div class="row g-3 mt-1">
                    <div class="col-12">
                        <label for="heard_about" class="form-label">How did you hear about us?<?php if ($_settingsModel->getFieldMode('heard_about') === 'required'): ?> <span class="text-danger">*</span><?php endif; ?></label>
                        <select class="form-select" id="heard_about" name="heard_about"<?php if ($_settingsModel->getFieldMode('heard_about') === 'required'): ?> required<?php endif; ?>>
                            <option value="">-- Select --</option>
                            <?php foreach ((new \App\Models\HeardAboutOptionModel())->getActive() as $ha): ?>
                            <option value="<?= $ha->value ?>"><?= esc($ha->label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <?php endif; ?>

                <div class="d-flex justify-content-end mt-4">
                    <button type="button" class="btn btn-primary btn-next">
                        Next: Job Brief <i class="bi bi-arrow-right"></i>
                    </button>
                </div>
            </div>

            <!-- Step 2: Job Brief -->
            <div class="form-step" data-step="2">
                <h4 class="mb-3"><i class="bi bi-clipboard-check"></i> Job Brief</h4>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="job_type" class="form-label">Job Type <span class="text-danger">*</span></label>
                        <select class="form-select" id="job_type" name="job_type" required>
                            <option value="">-- Select --</option>
                            <?php
                            $jobTypes = (new \App\Models\JobTypeModel())->getActive();
                            $categories = ['technical' => 'Technical', 'creative' => 'Creative & Events', 'other' => 'Other'];
                            foreach ($categories as $catKey => $catLabel):
                                $catTypes = array_filter($jobTypes, fn($jt) => ($jt->category ?? 'other') === $catKey);
                                if (!empty($catTypes)):
                            ?>
                            <optgroup label="<?= $catLabel ?>">
                                <?php foreach ($catTypes as $jt): ?>
                                <option value="<?= $jt->value ?>"><?= esc($jt->label) ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                            <?php endif; endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Please select a job type.</div>
                    </div>
                    <?php if ($_settingsModel->getFieldMode('urgency') !== 'hidden'): ?>
                    <div class="col-md-6">
                        <label for="urgency" class="form-label">Urgency<?php if ($_settingsModel->getFieldMode('urgency') === 'required'): ?> <span class="text-danger">*</span><?php endif; ?></label>
                        <select class="form-select" id="urgency" name="urgency"<?php if ($_settingsModel->getFieldMode('urgency') === 'required'): ?> required<?php endif; ?>>
                            <option value="low">Low</option>
                            <option value="normal" selected>Normal</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                    <?php endif; ?>
                    <div class="col-12">
                        <label for="job_description" class="form-label">Job Description <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="job_description" name="job_description"
                                  rows="4" required placeholder="Describe what you need..."></textarea>
                        <div class="invalid-feedback">Please describe the job.</div>
                    </div>
                    <?php if ($_settingsModel->getFieldMode('preferred_dates') !== 'hidden'): ?>
                    <div class="col-md-6">
                        <label for="preferred_dates" class="form-label">Preferred Dates<?php if ($_settingsModel->getFieldMode('preferred_dates') === 'required'): ?> <span class="text-danger">*</span><?php endif; ?></label>
                        <input type="text" class="form-control" id="preferred_dates" name="preferred_dates"
                               placeholder="e.g. Next week, March 20-25"<?php if ($_settingsModel->getFieldMode('preferred_dates') === 'required'): ?> required<?php endif; ?>>
                    </div>
                    <?php endif; ?>
                    <?php if ($_settingsModel->getFieldMode('time_window') !== 'hidden'): ?>
                    <div class="col-md-6">
                        <label for="time_window" class="form-label">Time Window<?php if ($_settingsModel->getFieldMode('time_window') === 'required'): ?> <span class="text-danger">*</span><?php endif; ?></label>
                        <select class="form-select" id="time_window" name="time_window"<?php if ($_settingsModel->getFieldMode('time_window') === 'required'): ?> required<?php endif; ?>>
                            <option value="morning">Morning</option>
                            <option value="afternoon">Afternoon</option>
                            <option value="flexible" selected>Flexible</option>
                        </select>
                    </div>
                    <?php endif; ?>
                    <?php if ($_settingsModel->getFieldMode('special_requirements') !== 'hidden'): ?>
                    <div class="col-12">
                        <label for="special_requirements" class="form-label">Special Requirements<?php if ($_settingsModel->getFieldMode('special_requirements') === 'required'): ?> <span class="text-danger">*</span><?php endif; ?></label>
                        <textarea class="form-control" id="special_requirements" name="special_requirements"
                                  rows="2" placeholder="Any special instructions or requirements..."<?php if ($_settingsModel->getFieldMode('special_requirements') === 'required'): ?> required<?php endif; ?>></textarea>
                    </div>
                    <?php endif; ?>
                    <?php if ($_settingsModel->getFieldMode('attachments') !== 'hidden'): ?>
                    <div class="col-12">
                        <label for="attachments" class="form-label">Reference Files<?php if ($_settingsModel->getFieldMode('attachments') === 'required'): ?> <span class="text-danger">*</span><?php endif; ?></label>
                        <input type="file" class="form-control" id="attachments" name="attachments[]"
                               multiple accept=".png,.jpg,.jpeg,.gif,.pdf,.doc,.docx"<?php if ($_settingsModel->getFieldMode('attachments') === 'required'): ?> required<?php endif; ?>>
                        <div class="form-text">Upload images, PDFs, or documents (max 32 MB total).</div>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <button type="button" class="btn btn-outline-secondary btn-prev">
                        <i class="bi bi-arrow-left"></i> Back
                    </button>
                    <button type="button" class="btn btn-primary btn-next">
                        Next: Location <i class="bi bi-arrow-right"></i>
                    </button>
                </div>
            </div>

            <!-- Step 3: Location -->
            <div class="form-step" data-step="3">
                <h4 class="mb-3"><i class="bi bi-geo-alt"></i> Location</h4>

                <div class="row g-3">
                    <div class="col-12">
                        <label for="address-search" class="form-label">Search Address</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="address-search"
                                   placeholder="Enter a postcode or address (e.g. B15 2TT or 12 High Street, Birmingham)">
                            <button type="button" class="btn btn-outline-primary" id="btn-search-address">
                                <i class="bi bi-search"></i> Search
                            </button>
                        </div>
                    </div>
                    <div class="col-12">
                        <div id="customer-map"></div>
                        <div class="form-text mt-1">
                            <i class="bi bi-pin-map"></i> <strong>Click</strong> to place pin<?php if ($_settingsModel->getFieldMode('area_polygon') !== 'hidden'): ?> |
                            <i class="bi bi-bounding-box"></i> Use draw tools for area<?php endif; ?> |
                            <i class="bi bi-star"></i> Right-click to mark a point of interest
                        </div>
                    </div>
                    <div class="col-12">
                        <label for="location_address" class="form-label">Address</label>
                        <input type="text" class="form-control" id="location_address" name="location_address"
                               placeholder="Will auto-fill from map or type manually">
                    </div>
                </div>

                <!-- Hidden map data fields -->
                <input type="hidden" id="location_lat" name="location_lat">
                <input type="hidden" id="location_lng" name="location_lng">
                <input type="hidden" id="area_polygon" name="area_polygon">
                <input type="hidden" id="pois_json" name="pois_json">

                <div class="d-flex justify-content-between mt-4">
                    <button type="button" class="btn btn-outline-secondary btn-prev">
                        <i class="bi bi-arrow-left"></i> Back
                    </button>
                    <button type="button" class="btn btn-primary btn-next">
                        Next: Preferences <i class="bi bi-arrow-right"></i>
                    </button>
                </div>
            </div>

            <!-- Step 4: Flight Preferences -->
            <div class="form-step" data-step="4">
                <h4 class="mb-3"><i class="bi bi-sliders"></i> Flight Preferences</h4>

                <div class="row g-3">
                    <?php if ($_settingsModel->getFieldMode('altitude_preset') !== 'hidden'): ?>
                    <div class="col-md-6">
                        <label class="form-label">Altitude<?php if ($_settingsModel->getFieldMode('altitude_preset') === 'required'): ?> <span class="text-danger">*</span><?php endif; ?></label>
                        <select class="form-select" id="altitude_preset" name="altitude_preset"<?php if ($_settingsModel->getFieldMode('altitude_preset') === 'required'): ?> required<?php endif; ?>>
                            <option value="low">Low (15-30m) - Detail shots</option>
                            <option value="medium" selected>Medium (30-60m) - Standard</option>
                            <option value="high">High (60-120m) - Wide coverage</option>
                            <option value="custom">Custom</option>
                        </select>
                    </div>
                    <div class="col-md-6" id="custom-altitude-group" style="display: none;">
                        <label for="altitude_custom_m" class="form-label">Custom Altitude (meters)</label>
                        <input type="number" class="form-control" id="altitude_custom_m"
                               name="altitude_custom_m" min="5" max="120" value="30">
                    </div>
                    <?php endif; ?>
                    <?php if ($_settingsModel->getFieldMode('camera_angle') !== 'hidden'): ?>
                    <div class="col-md-6">
                        <label class="form-label">Camera Angle<?php if ($_settingsModel->getFieldMode('camera_angle') === 'required'): ?> <span class="text-danger">*</span><?php endif; ?></label>
                        <select class="form-select" name="camera_angle"<?php if ($_settingsModel->getFieldMode('camera_angle') === 'required'): ?> required<?php endif; ?>>
                            <option value="straight_down">Overhead (looking straight down)</option>
                            <option value="45deg">Angled (45&deg; from above)</option>
                            <option value="horizontal">Eye-level (looking ahead)</option>
                            <option value="pilot_decides" selected>Pilot Decides</option>
                        </select>
                    </div>
                    <?php endif; ?>
                    <?php if ($_settingsModel->getFieldMode('video_resolution') !== 'hidden'): ?>
                    <div class="col-md-6">
                        <label class="form-label">Video Resolution<?php if ($_settingsModel->getFieldMode('video_resolution') === 'required'): ?> <span class="text-danger">*</span><?php endif; ?></label>
                        <select class="form-select" name="video_resolution"<?php if ($_settingsModel->getFieldMode('video_resolution') === 'required'): ?> required<?php endif; ?>>
                            <option value="4k" selected>4K</option>
                            <option value="1080p">1080p</option>
                        </select>
                    </div>
                    <?php endif; ?>
                    <?php if ($_settingsModel->getFieldMode('photo_mode') !== 'hidden'): ?>
                    <div class="col-md-6">
                        <label class="form-label">Photo Mode<?php if ($_settingsModel->getFieldMode('photo_mode') === 'required'): ?> <span class="text-danger">*</span><?php endif; ?></label>
                        <select class="form-select" name="photo_mode"<?php if ($_settingsModel->getFieldMode('photo_mode') === 'required'): ?> required<?php endif; ?>>
                            <option value="single" selected>Single Shot</option>
                            <option value="interval">Interval (Timelapse)</option>
                            <option value="panorama">Panorama</option>
                        </select>
                    </div>
                    <?php endif; ?>
                    <?php if ($_settingsModel->getFieldMode('no_fly_notes') !== 'hidden'): ?>
                    <div class="col-12">
                        <label for="no_fly_notes" class="form-label">Nearby Restrictions<?php if ($_settingsModel->getFieldMode('no_fly_notes') === 'required'): ?> <span class="text-danger">*</span><?php else: ?> (optional)<?php endif; ?></label>
                        <textarea class="form-control" id="no_fly_notes" name="no_fly_notes" rows="2"
                                  placeholder="Any airports, restricted areas, or obstacles we should know about?"<?php if ($_settingsModel->getFieldMode('no_fly_notes') === 'required'): ?> required<?php endif; ?>></textarea>
                    </div>
                    <?php endif; ?>
                    <?php if ($_settingsModel->getFieldMode('privacy_notes') !== 'hidden'): ?>
                    <div class="col-12">
                        <label for="privacy_notes" class="form-label">Privacy Considerations<?php if ($_settingsModel->getFieldMode('privacy_notes') === 'required'): ?> <span class="text-danger">*</span><?php endif; ?></label>
                        <textarea class="form-control" id="privacy_notes" name="privacy_notes" rows="2"
                                  placeholder="Neighbouring properties, people, or sensitive areas?"<?php if ($_settingsModel->getFieldMode('privacy_notes') === 'required'): ?> required<?php endif; ?>></textarea>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Footage Purpose & Output section -->
                <?php
                    $_anyPurposeFieldVisible = (
                        $_settingsModel->getFieldMode('footage_purpose') !== 'hidden' ||
                        $_settingsModel->getFieldMode('output_format') !== 'hidden' ||
                        $_settingsModel->getFieldMode('video_duration') !== 'hidden' ||
                        $_settingsModel->getFieldMode('shot_types') !== 'hidden' ||
                        $_settingsModel->getFieldMode('delivery_timeline') !== 'hidden'
                    );
                ?>
                <?php if ($_anyPurposeFieldVisible): ?>
                <hr class="my-4">
                <h5 class="mb-3"><i class="bi bi-bullseye"></i> Footage Purpose & Output</h5>
                <div class="row g-3">
                    <?php if ($_settingsModel->getFieldMode('footage_purpose') !== 'hidden'): ?>
                    <div class="col-md-6">
                        <label for="footage_purpose" class="form-label">What is the footage for?<?php if ($_settingsModel->getFieldMode('footage_purpose') === 'required'): ?> <span class="text-danger">*</span><?php endif; ?></label>
                        <select class="form-select" id="footage_purpose" name="footage_purpose"<?php if ($_settingsModel->getFieldMode('footage_purpose') === 'required'): ?> required<?php endif; ?>>
                            <option value="">-- Select --</option>
                            <?php foreach ((new \App\Models\PurposeOptionModel())->getActive() as $po): ?>
                            <option value="<?= $po->value ?>"><?= esc($po->label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6" id="purpose-other-group" style="display: none;">
                        <label for="footage_purpose_other" class="form-label">Please specify</label>
                        <input type="text" class="form-control" id="footage_purpose_other" name="footage_purpose_other"
                               placeholder="Describe the purpose...">
                    </div>
                    <?php endif; ?>
                    <?php if ($_settingsModel->getFieldMode('output_format') !== 'hidden'): ?>
                    <div class="col-md-6">
                        <label for="output_format" class="form-label">Output Format<?php if ($_settingsModel->getFieldMode('output_format') === 'required'): ?> <span class="text-danger">*</span><?php endif; ?></label>
                        <select class="form-select" id="output_format" name="output_format"<?php if ($_settingsModel->getFieldMode('output_format') === 'required'): ?> required<?php endif; ?>>
                            <option value="">-- Select --</option>
                            <option value="raw">Raw Footage</option>
                            <option value="edited_video">Edited Video</option>
                            <option value="photos_only">Photos Only</option>
                            <option value="photos_video">Photos + Video</option>
                            <option value="livestream">Live Stream</option>
                        </select>
                    </div>
                    <?php endif; ?>
                    <?php if ($_settingsModel->getFieldMode('video_duration') !== 'hidden'): ?>
                    <div class="col-md-6">
                        <label for="video_duration" class="form-label">Video Duration Expectation<?php if ($_settingsModel->getFieldMode('video_duration') === 'required'): ?> <span class="text-danger">*</span><?php endif; ?></label>
                        <input type="text" class="form-control" id="video_duration" name="video_duration"
                               placeholder='e.g. "2-3 minutes", "full event"'<?php if ($_settingsModel->getFieldMode('video_duration') === 'required'): ?> required<?php endif; ?>>
                    </div>
                    <?php endif; ?>
                    <?php if ($_settingsModel->getFieldMode('shot_types') !== 'hidden'): ?>
                    <div class="col-md-6">
                        <label class="form-label">Specific Shots Needed<?php if ($_settingsModel->getFieldMode('shot_types') === 'required'): ?> <span class="text-danger">*</span><?php endif; ?></label>
                        <div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input shot-type-check" type="checkbox" value="overview" id="shot-overview">
                                <label class="form-check-label" for="shot-overview">Overview / Wide</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input shot-type-check" type="checkbox" value="close_up" id="shot-closeup">
                                <label class="form-check-label" for="shot-closeup">Close-up Detail</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input shot-type-check" type="checkbox" value="orbit" id="shot-orbit">
                                <label class="form-check-label" for="shot-orbit">Orbiting</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input shot-type-check" type="checkbox" value="tracking" id="shot-tracking">
                                <label class="form-check-label" for="shot-tracking">Tracking / Follow</label>
                            </div>
                        </div>
                        <input type="hidden" id="shot_types_json" name="shot_types_json" value="[]">
                    </div>
                    <?php endif; ?>
                    <?php if ($_settingsModel->getFieldMode('delivery_timeline') !== 'hidden'): ?>
                    <div class="col-md-6">
                        <label for="delivery_timeline" class="form-label">Delivery Timeline<?php if ($_settingsModel->getFieldMode('delivery_timeline') === 'required'): ?> <span class="text-danger">*</span><?php endif; ?></label>
                        <select class="form-select" id="delivery_timeline" name="delivery_timeline"<?php if ($_settingsModel->getFieldMode('delivery_timeline') === 'required'): ?> required<?php endif; ?>>
                            <option value="">-- Select --</option>
                            <option value="asap">ASAP</option>
                            <option value="1_week">1 Week</option>
                            <option value="2_weeks">2 Weeks</option>
                            <option value="1_month">1 Month</option>
                            <option value="flexible">Flexible</option>
                        </select>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <div class="d-flex justify-content-between mt-4">
                    <button type="button" class="btn btn-outline-secondary btn-prev">
                        <i class="bi bi-arrow-left"></i> Back
                    </button>
                    <button type="button" class="btn btn-primary btn-next">
                        Next: Review <i class="bi bi-arrow-right"></i>
                    </button>
                </div>
            </div>

            <!-- Step 5: Review & Submit -->
            <div class="form-step" data-step="5">
                <h4 class="mb-3"><i class="bi bi-check2-square"></i> Review & Submit</h4>

                <div class="review-section">
                    <h6>Your Details</h6>
                    <p class="mb-1"><strong>Name:</strong> <span id="rev-name"></span></p>
                    <p class="mb-1"><strong>Email:</strong> <span id="rev-email"></span></p>
                    <p class="mb-1"><strong>Phone:</strong> <span id="rev-phone"></span></p>
                    <p class="mb-1"><strong>Type:</strong> <span id="rev-customer-type"></span></p>
                    <p class="mb-1"><strong>Company:</strong> <span id="rev-company"></span></p>
                    <div id="rev-business-fields" style="display: none;">
                        <p class="mb-1"><strong>Reg/VAT:</strong> <span id="rev-abn"></span></p>
                        <p class="mb-1"><strong>Billing Contact:</strong> <span id="rev-billing-contact"></span></p>
                        <p class="mb-1"><strong>Billing Email:</strong> <span id="rev-billing-email"></span></p>
                        <p class="mb-1"><strong>PO #:</strong> <span id="rev-po"></span></p>
                    </div>
                </div>

                <div class="review-section">
                    <h6>Job Brief</h6>
                    <p class="mb-1"><strong>Type:</strong> <span id="rev-job-type"></span></p>
                    <p class="mb-1"><strong>Urgency:</strong> <span id="rev-urgency"></span></p>
                    <p class="mb-1"><strong>Description:</strong> <span id="rev-description"></span></p>
                    <p class="mb-1"><strong>Preferred Dates:</strong> <span id="rev-dates"></span></p>
                    <p class="mb-0"><strong>Time Window:</strong> <span id="rev-time"></span></p>
                </div>

                <div class="review-section">
                    <h6>Location</h6>
                    <p class="mb-1"><strong>Address:</strong> <span id="rev-address"></span></p>
                    <p class="mb-1"><strong>Coordinates:</strong> <span id="rev-coords"></span></p>
                    <div id="review-map" style="height: 200px; border-radius: 8px; border: 1px solid #dee2e6;" class="mb-2"></div>
                </div>

                <div class="review-section">
                    <h6>Flight Preferences</h6>
                    <p class="mb-1"><strong>Altitude:</strong> <span id="rev-altitude"></span></p>
                    <p class="mb-1"><strong>Camera Angle:</strong> <span id="rev-camera"></span></p>
                    <p class="mb-1"><strong>Resolution:</strong> <span id="rev-resolution"></span></p>
                    <p class="mb-1" id="rev-purpose-row" style="display: none;"><strong>Purpose:</strong> <span id="rev-purpose"></span></p>
                    <p class="mb-1" id="rev-output-row" style="display: none;"><strong>Output Format:</strong> <span id="rev-output-format"></span></p>
                    <p class="mb-1" id="rev-duration-row" style="display: none;"><strong>Video Duration:</strong> <span id="rev-video-duration"></span></p>
                    <p class="mb-1" id="rev-shots-row" style="display: none;"><strong>Shot Types:</strong> <span id="rev-shot-types"></span></p>
                    <p class="mb-0" id="rev-timeline-row" style="display: none;"><strong>Delivery Timeline:</strong> <span id="rev-delivery-timeline"></span></p>
                </div>

                <div class="form-check mt-3 mb-3">
                    <input class="form-check-input" type="checkbox" id="consent_given" name="consent_given" value="1" required>
                    <label class="form-check-label" for="consent_given">
                        I confirm the information above is correct and I consent to drone flight operations
                        at the specified location. <span class="text-danger">*</span>
                    </label>
                    <div class="invalid-feedback">You must give consent to submit.</div>
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <button type="button" class="btn btn-outline-secondary btn-prev">
                        <i class="bi bi-arrow-left"></i> Back
                    </button>
                    <button type="submit" class="btn btn-success btn-lg" id="btn-submit">
                        <i class="bi bi-send"></i> Submit Flight Brief
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.js"></script>
<script src="<?= base_url('static/js/map-customer.js') ?>"></script>
<script src="<?= base_url('static/js/form-wizard.js') ?>"></script>
<?= $this->endSection() ?>
