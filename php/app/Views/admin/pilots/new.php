<?= $this->extend('layouts/base') ?>
<?= $this->section('title') ?>New Pilot<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="mb-4">
    <a href="<?= site_url('pilots') ?>" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back to Pilots
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-person-plus"></i> New Pilot
            </div>
            <div class="card-body">
                <form method="POST" action="<?= site_url('pilots/new') ?>">
                    <?= csrf_field() ?>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Username <span class="text-danger">*</span></label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Password <span class="text-danger">*</span></label>
                            <input type="password" name="password" class="form-control" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Display Name <span class="text-danger">*</span></label>
                            <input type="text" name="display_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control">
                        </div>

                        <hr>
                        <h6>CAA Regulatory IDs</h6>

                        <div class="col-md-8">
                            <label class="form-label">Flyer ID</label>
                            <input type="text" name="flying_id" class="form-control"
                                   placeholder="GBR-RP-XXXXXXXXXX">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Flyer ID Expiry</label>
                            <input type="date" name="flying_id_expiry" class="form-control">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Operator ID</label>
                            <input type="text" name="operator_id" class="form-control"
                                   placeholder="GBR-OP-XXXXXXXXXX">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Operator ID Expiry</label>
                            <input type="date" name="operator_id_expiry" class="form-control">
                        </div>

                        <hr>
                        <h6>UK Qualifications</h6>

                        <div class="col-md-4">
                            <label class="form-label">A2 CofC Expiry</label>
                            <input type="date" name="a2_cofc_expiry" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">GVC MR Expiry</label>
                            <input type="date" name="gvc_mr_expiry" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">GVC FW Expiry</label>
                            <input type="date" name="gvc_fw_expiry" class="form-control">
                        </div>

                        <hr>
                        <h6>Certificate of Competency</h6>

                        <div class="col-md-6">
                            <label class="form-label">Practical Competency Date</label>
                            <input type="date" name="practical_competency_date" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Mentor / Examiner</label>
                            <input type="text" name="mentor_examiner" class="form-control">
                        </div>

                        <hr>
                        <h6>Article 16</h6>

                        <div class="col-md-8">
                            <div class="form-check mt-2">
                                <input type="checkbox" name="article16_agreed" class="form-check-input" id="article16form">
                                <label class="form-check-label" for="article16form">
                                    I have read and understood the Article 16 operational authorisation requirements
                                </label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Date Agreed</label>
                            <input type="date" name="article16_agreed_date" class="form-control">
                        </div>

                        <hr>
                        <h6>Home Address</h6>

                        <div class="col-md-12">
                            <input type="text" name="address_line1" class="form-control"
                                   placeholder="Address Line 1">
                        </div>
                        <div class="col-md-12">
                            <input type="text" name="address_line2" class="form-control"
                                   placeholder="Address Line 2">
                        </div>
                        <div class="col-md-6">
                            <input type="text" name="address_city" class="form-control"
                                   placeholder="City / Town">
                        </div>
                        <div class="col-md-6">
                            <input type="text" name="address_county" class="form-control"
                                   placeholder="County">
                        </div>
                        <div class="col-md-4">
                            <input type="text" name="address_postcode" class="form-control"
                                   placeholder="Postcode">
                        </div>
                        <div class="col-md-8">
                            <input type="text" name="address_country" class="form-control"
                                   placeholder="Country" value="United Kingdom">
                        </div>

                        <hr>
                        <h6>Insurance</h6>

                        <div class="col-md-4">
                            <label class="form-label">Insurance Provider</label>
                            <input type="text" name="insurance_provider" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Policy Number</label>
                            <input type="text" name="insurance_policy_no" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Insurance Expiry</label>
                            <input type="date" name="insurance_expiry" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Bio / Notes</label>
                            <textarea name="pilot_bio" class="form-control" rows="3"></textarea>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Create Pilot
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
