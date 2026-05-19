<?= $this->extend('layouts/base') ?>
<?= $this->section('title') ?><?= esc($pilot->display_name) ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <a href="<?= site_url('pilots') ?>" class="btn btn-sm btn-outline-secondary me-2">
            <i class="bi bi-arrow-left"></i> Back
        </a>
        <strong class="fs-5"><?= esc($pilot->display_name) ?></strong>
        <span class="badge avail-<?= esc($pilot->availability_status ?? 'available') ?> ms-2">
            <?= ucwords(str_replace('_', ' ', $pilot->availability_status ?? 'available')) ?>
        </span>
        <?php if (empty($pilot->is_active)): ?>
        <span class="badge bg-secondary ms-1">Inactive</span>
        <?php endif; ?>
    </div>
    <div class="d-flex gap-2">
        <form method="POST" action="<?= site_url('pilots/' . $pilot->id . '/availability') ?>" class="d-flex gap-1">
            <?= csrf_field() ?>
            <select name="status" class="form-select form-select-sm" style="width: auto;">
                <?php foreach (['available', 'on_mission', 'unavailable'] as $s): ?>
                <option value="<?= $s ?>" <?= ($pilot->availability_status ?? '') === $s ? 'selected' : '' ?>>
                    <?= ucwords(str_replace('_', ' ', $s)) ?>
                </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-sm btn-outline-primary">Set</button>
        </form>
    </div>
</div>

<div class="row g-4">
    <!-- Left: Profile -->
    <div class="col-lg-5">
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between">
                <span><i class="bi bi-person-badge"></i> Profile</span>
                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#editProfile">
                    <i class="bi bi-pencil"></i> Edit
                </button>
            </div>
            <div class="card-body">
                <p class="mb-1"><strong>Username:</strong> <?= esc($pilot->username) ?></p>
                <p class="mb-1"><strong>Email:</strong> <?= esc($pilot->email ?: '-') ?></p>
                <p class="mb-1"><strong>Phone:</strong> <?= esc($pilot->phone ?: '-') ?></p>

                <hr class="my-2">
                <h6 class="small text-muted mb-1">CAA Regulatory IDs</h6>
                <p class="mb-1">
                    <strong>Flyer ID:</strong> <?= esc($pilot->flying_id ?: '-') ?>
                    <?php if (!empty($pilot->flying_id)): ?>
                        <?php if (!empty($pilot->flying_id_valid)): ?>
                        <span class="badge bg-success">VALID</span>
                        <?php else: ?>
                        <span class="badge bg-danger">EXPIRED</span>
                        <?php endif; ?>
                    <?php endif; ?>
                    <?php if (!empty($pilot->flying_id_expiry)): ?>
                    <small class="text-muted">(exp. <?= date('d M Y', strtotime($pilot->flying_id_expiry)) ?>)</small>
                    <?php endif; ?>
                </p>
                <p class="mb-1">
                    <strong>Operator ID:</strong> <?= esc($pilot->operator_id ?: '-') ?>
                    <?php if (!empty($pilot->operator_id)): ?>
                        <?php if (!empty($pilot->operator_id_valid)): ?>
                        <span class="badge bg-success">VALID</span>
                        <?php else: ?>
                        <span class="badge bg-danger">EXPIRED</span>
                        <?php endif; ?>
                    <?php endif; ?>
                    <?php if (!empty($pilot->operator_id_expiry)): ?>
                    <small class="text-muted">(exp. <?= date('d M Y', strtotime($pilot->operator_id_expiry)) ?>)</small>
                    <?php endif; ?>
                </p>

                <hr class="my-2">
                <h6 class="small text-muted mb-1">UK Qualifications</h6>
                <p class="mb-1"><strong>A2 CofC:</strong>
                    <?php if (!empty($pilot->has_a2_cofc)): ?><span class="badge bg-success">VALID</span><?php elseif (!empty($pilot->a2_cofc_expiry)): ?><span class="badge bg-danger">EXPIRED</span><?php endif; ?>
                    <?= !empty($pilot->a2_cofc_expiry) ? date('d M Y', strtotime($pilot->a2_cofc_expiry)) : '-' ?>
                    <?php if (!empty($pilot->a2_cofc_number)): ?><small class="text-muted">(<?= esc($pilot->a2_cofc_number) ?>)</small><?php endif; ?>
                </p>
                <p class="mb-1"><strong>GVC Level:</strong> <?= esc($pilot->gvc_level ?: '-') ?>
                    <?php if (!empty($pilot->has_gvc)): ?><span class="badge bg-success">VALID</span><?php endif; ?>
                    <?php if (!empty($pilot->gvc_cert_number)): ?><small class="text-muted">(<?= esc($pilot->gvc_cert_number) ?>)</small><?php endif; ?>
                </p>
                <p class="mb-1"><strong>GVC MR:</strong>
                    <?= !empty($pilot->gvc_mr_expiry) ? date('d M Y', strtotime($pilot->gvc_mr_expiry)) : '-' ?></p>
                <p class="mb-1"><strong>GVC FW:</strong>
                    <?= !empty($pilot->gvc_fw_expiry) ? date('d M Y', strtotime($pilot->gvc_fw_expiry)) : '-' ?></p>
                <?php if (!empty($pilot->oa_type)): ?>
                <p class="mb-1"><strong>OA:</strong> <?= str_replace('_', '-', esc($pilot->oa_type)) ?>
                    <?php if (!empty($pilot->has_operational_authorisation)): ?><span class="badge bg-success">VALID</span><?php elseif (!empty($pilot->oa_expiry)): ?><span class="badge bg-danger">EXPIRED</span><?php endif; ?>
                    <?php if (!empty($pilot->oa_reference)): ?><small class="text-muted">(<?= esc($pilot->oa_reference) ?>)</small><?php endif; ?>
                    <?php if (!empty($pilot->oa_expiry)): ?><small class="text-muted">exp. <?= date('d M Y', strtotime($pilot->oa_expiry)) ?></small><?php endif; ?>
                </p>
                <?php endif; ?>

                <?php if (!empty($pilot->practical_competency_date) || !empty($pilot->mentor_examiner)): ?>
                <hr class="my-2">
                <h6 class="small text-muted mb-1">Competency</h6>
                <p class="mb-1"><strong>Practical Date:</strong>
                    <?= !empty($pilot->practical_competency_date) ? date('d M Y', strtotime($pilot->practical_competency_date)) : '-' ?></p>
                <p class="mb-1"><strong>Mentor/Examiner:</strong> <?= esc($pilot->mentor_examiner ?: '-') ?></p>
                <?php endif; ?>

                <hr class="my-2">
                <h6 class="small text-muted mb-1">Article 16</h6>
                <p class="mb-1">
                    <?php if (!empty($pilot->article16_agreed)): ?>
                    <span class="badge bg-success">Agreed</span>
                    <?php if (!empty($pilot->article16_agreed_date)): ?>
                    <small class="text-muted">on <?= date('d M Y', strtotime($pilot->article16_agreed_date)) ?></small>
                    <?php endif; ?>
                    <?php else: ?>
                    <span class="badge bg-secondary">Not Agreed</span>
                    <?php endif; ?>
                </p>

                <?php if (!empty($pilot->address_line1) || !empty($pilot->address_city) || !empty($pilot->address_postcode)): ?>
                <hr class="my-2">
                <h6 class="small text-muted mb-1">Home Address</h6>
                <p class="mb-0">
                    <?php if (!empty($pilot->address_line1)): ?><?= esc($pilot->address_line1) ?><br><?php endif; ?>
                    <?php if (!empty($pilot->address_line2)): ?><?= esc($pilot->address_line2) ?><br><?php endif; ?>
                    <?php if (!empty($pilot->address_city)): ?><?= esc($pilot->address_city) ?><?php endif; ?>
                    <?php if (!empty($pilot->address_county)): ?>, <?= esc($pilot->address_county) ?><?php endif; ?>
                    <?php if (!empty($pilot->address_postcode)): ?><br><?= esc($pilot->address_postcode) ?><?php endif; ?>
                    <?php if (!empty($pilot->address_country)): ?><br><?= esc($pilot->address_country) ?><?php endif; ?>
                </p>
                <?php endif; ?>

                <hr class="my-2">
                <h6 class="small text-muted mb-1">Insurance</h6>
                <p class="mb-1"><strong>Provider:</strong> <?= esc($pilot->insurance_provider ?: '-') ?>
                    <?php if (!empty($pilot->insurance_policy_no)): ?> (<?= esc($pilot->insurance_policy_no) ?>)<?php endif; ?>
                </p>
                <?php if (!empty($pilot->insurance_expiry)): ?>
                <p class="mb-1"><strong>Expiry:</strong> <?= date('d M Y', strtotime($pilot->insurance_expiry)) ?></p>
                <?php endif; ?>
                <?php if (!empty($pilot->pilot_bio)): ?>
                <hr class="my-2">
                <p class="mb-0"><strong>Bio:</strong> <?= esc($pilot->pilot_bio) ?></p>
                <?php endif; ?>
            </div>
            <div class="collapse" id="editProfile">
                <div class="card-body border-top">
                    <form method="POST" action="<?= site_url('pilots/' . $pilot->id . '/edit') ?>">
                        <?= csrf_field() ?>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label small">Display Name</label>
                                <input type="text" name="display_name" class="form-control form-control-sm"
                                       value="<?= esc($pilot->display_name) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Email</label>
                                <input type="email" name="email" class="form-control form-control-sm"
                                       value="<?= esc($pilot->email ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Phone</label>
                                <input type="text" name="phone" class="form-control form-control-sm"
                                       value="<?= esc($pilot->phone ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">New Password</label>
                                <input type="password" name="password" class="form-control form-control-sm"
                                       placeholder="(optional)">
                            </div>

                            <div class="col-12"><hr class="my-1"><h6 class="small text-muted">CAA Regulatory IDs</h6></div>
                            <div class="col-8">
                                <input type="text" name="flying_id" class="form-control form-control-sm"
                                       placeholder="Flyer ID (GBR-RP-...)" value="<?= esc($pilot->flying_id ?? '') ?>">
                            </div>
                            <div class="col-4">
                                <input type="date" name="flying_id_expiry" class="form-control form-control-sm"
                                       value="<?= esc($pilot->flying_id_expiry ?? '') ?>">
                            </div>
                            <div class="col-8">
                                <input type="text" name="operator_id" class="form-control form-control-sm"
                                       placeholder="Operator ID (GBR-OP-...)" value="<?= esc($pilot->operator_id ?? '') ?>">
                            </div>
                            <div class="col-4">
                                <input type="date" name="operator_id_expiry" class="form-control form-control-sm"
                                       value="<?= esc($pilot->operator_id_expiry ?? '') ?>">
                            </div>

                            <div class="col-12"><hr class="my-1"><h6 class="small text-muted">UK Qualifications</h6></div>
                            <div class="col-md-4">
                                <label class="form-label small">A2 CofC Number</label>
                                <input type="text" name="a2_cofc_number" class="form-control form-control-sm"
                                       value="<?= esc($pilot->a2_cofc_number ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">A2 CofC Expiry</label>
                                <input type="date" name="a2_cofc_expiry" class="form-control form-control-sm"
                                       value="<?= esc($pilot->a2_cofc_expiry ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">GVC Level</label>
                                <select name="gvc_level" class="form-select form-select-sm">
                                    <option value="">None</option>
                                    <option value="GVC" <?= ($pilot->gvc_level ?? '') === 'GVC' ? 'selected' : '' ?>>GVC</option>
                                    <option value="RPC_L1" <?= ($pilot->gvc_level ?? '') === 'RPC_L1' ? 'selected' : '' ?>>RPC L1</option>
                                    <option value="RPC_L2" <?= ($pilot->gvc_level ?? '') === 'RPC_L2' ? 'selected' : '' ?>>RPC L2</option>
                                    <option value="RPC_L3" <?= ($pilot->gvc_level ?? '') === 'RPC_L3' ? 'selected' : '' ?>>RPC L3</option>
                                    <option value="RPC_L4" <?= ($pilot->gvc_level ?? '') === 'RPC_L4' ? 'selected' : '' ?>>RPC L4</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">GVC Cert Number</label>
                                <input type="text" name="gvc_cert_number" class="form-control form-control-sm"
                                       value="<?= esc($pilot->gvc_cert_number ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">GVC MR Expiry</label>
                                <input type="date" name="gvc_mr_expiry" class="form-control form-control-sm"
                                       value="<?= esc($pilot->gvc_mr_expiry ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">GVC FW Expiry</label>
                                <input type="date" name="gvc_fw_expiry" class="form-control form-control-sm"
                                       value="<?= esc($pilot->gvc_fw_expiry ?? '') ?>">
                            </div>
                            <div class="col-12"><hr class="my-1"><h6 class="small text-muted">Operational Authorisation</h6></div>
                            <div class="col-md-4">
                                <select name="oa_type" class="form-select form-select-sm">
                                    <option value="">None</option>
                                    <option value="PDRA_01" <?= ($pilot->oa_type ?? '') === 'PDRA_01' ? 'selected' : '' ?>>PDRA-01</option>
                                    <option value="FULL_SORA" <?= ($pilot->oa_type ?? '') === 'FULL_SORA' ? 'selected' : '' ?>>Full SORA</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <input type="text" name="oa_reference" class="form-control form-control-sm"
                                       placeholder="OA Reference" value="<?= esc($pilot->oa_reference ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <input type="date" name="oa_expiry" class="form-control form-control-sm"
                                       value="<?= esc($pilot->oa_expiry ?? '') ?>">
                            </div>

                            <div class="col-12"><hr class="my-1"><h6 class="small text-muted">Competency</h6></div>
                            <div class="col-md-6">
                                <label class="form-label small">Practical Date</label>
                                <input type="date" name="practical_competency_date" class="form-control form-control-sm"
                                       value="<?= esc($pilot->practical_competency_date ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Mentor/Examiner</label>
                                <input type="text" name="mentor_examiner" class="form-control form-control-sm"
                                       value="<?= esc($pilot->mentor_examiner ?? '') ?>">
                            </div>

                            <div class="col-12"><hr class="my-1"><h6 class="small text-muted">Article 16</h6></div>
                            <div class="col-md-8">
                                <div class="form-check">
                                    <input type="checkbox" name="article16_agreed" class="form-check-input" id="article16edit"
                                           <?= !empty($pilot->article16_agreed) ? 'checked' : '' ?>>
                                    <label class="form-check-label small" for="article16edit">Agreed</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <input type="date" name="article16_agreed_date" class="form-control form-control-sm"
                                       value="<?= esc($pilot->article16_agreed_date ?? '') ?>">
                            </div>

                            <div class="col-12"><hr class="my-1"><h6 class="small text-muted">Home Address</h6></div>
                            <div class="col-12">
                                <input type="text" name="address_line1" class="form-control form-control-sm"
                                       placeholder="Address Line 1" value="<?= esc($pilot->address_line1 ?? '') ?>">
                            </div>
                            <div class="col-12">
                                <input type="text" name="address_line2" class="form-control form-control-sm"
                                       placeholder="Address Line 2" value="<?= esc($pilot->address_line2 ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <input type="text" name="address_city" class="form-control form-control-sm"
                                       placeholder="City / Town" value="<?= esc($pilot->address_city ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <input type="text" name="address_county" class="form-control form-control-sm"
                                       placeholder="County" value="<?= esc($pilot->address_county ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <input type="text" name="address_postcode" class="form-control form-control-sm"
                                       placeholder="Postcode" value="<?= esc($pilot->address_postcode ?? '') ?>">
                            </div>
                            <div class="col-md-8">
                                <input type="text" name="address_country" class="form-control form-control-sm"
                                       placeholder="Country" value="<?= esc($pilot->address_country ?? 'United Kingdom') ?>">
                            </div>

                            <div class="col-12"><hr class="my-1"><h6 class="small text-muted">Insurance</h6></div>
                            <div class="col-md-4">
                                <input type="text" name="insurance_provider" class="form-control form-control-sm"
                                       placeholder="Provider" value="<?= esc($pilot->insurance_provider ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <input type="text" name="insurance_policy_no" class="form-control form-control-sm"
                                       placeholder="Policy No" value="<?= esc($pilot->insurance_policy_no ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <input type="date" name="insurance_expiry" class="form-control form-control-sm"
                                       value="<?= esc($pilot->insurance_expiry ?? '') ?>">
                            </div>
                            <div class="col-12">
                                <textarea name="pilot_bio" class="form-control form-control-sm" rows="2"
                                          placeholder="Bio / notes"><?= esc($pilot->pilot_bio ?? '') ?></textarea>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-sm btn-primary">
                                    <i class="bi bi-save"></i> Save
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Order History -->
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-clock-history"></i> Order History</div>
            <div class="card-body text-muted small">No orders yet.</div>
        </div>
    </div>

    <!-- Right: Certs, Memberships, Equipment, Documents -->
    <div class="col-lg-7">
        <!-- Certifications -->
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between">
                <span><i class="bi bi-award"></i> Certifications</span>
                <button class="btn btn-sm btn-outline-success" data-bs-toggle="collapse" data-bs-target="#addCert">
                    <i class="bi bi-plus"></i> Add
                </button>
            </div>
            <div class="collapse" id="addCert">
                <div class="card-body border-bottom">
                    <form method="POST" action="<?= site_url('pilots/' . $pilot->id . '/certifications/add') ?>">
                        <?= csrf_field() ?>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <input type="text" name="cert_name" class="form-control form-control-sm"
                                       placeholder="Certification Name *" required>
                            </div>
                            <div class="col-md-6">
                                <input type="text" name="issuing_body" class="form-control form-control-sm"
                                       placeholder="Issuing Body">
                            </div>
                            <div class="col-md-4">
                                <input type="text" name="cert_number" class="form-control form-control-sm"
                                       placeholder="Cert Number">
                            </div>
                            <div class="col-md-4">
                                <input type="date" name="issue_date" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-4">
                                <input type="date" name="expiry_date" class="form-control form-control-sm">
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-sm btn-success">
                                    <i class="bi bi-plus"></i> Add Certification
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Name</th><th>Issuer</th><th>Number</th><th>Expiry</th><th></th></tr></thead>
                    <tbody>
                        <?php if (!empty($certs)): ?>
                        <?php foreach ($certs as $cert): ?>
                        <tr>
                            <td><?= esc($cert->cert_name) ?></td>
                            <td><?= esc($cert->issuing_body ?: '-') ?></td>
                            <td><?= esc($cert->cert_number ?: '-') ?></td>
                            <td><?= !empty($cert->expiry_date) ? date('d M Y', strtotime($cert->expiry_date)) : '-' ?></td>
                            <td>
                                <form method="POST" action="<?= site_url('pilots/' . $pilot->id . '/certifications/' . $cert->id . '/delete') ?>" class="d-inline">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <tr><td colspan="5" class="text-muted text-center small">No certifications.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Memberships -->
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between">
                <span><i class="bi bi-people"></i> Memberships</span>
                <button class="btn btn-sm btn-outline-success" data-bs-toggle="collapse" data-bs-target="#addMem">
                    <i class="bi bi-plus"></i> Add
                </button>
            </div>
            <div class="collapse" id="addMem">
                <div class="card-body border-bottom">
                    <form method="POST" action="<?= site_url('pilots/' . $pilot->id . '/memberships/add') ?>">
                        <?= csrf_field() ?>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <input type="text" name="org_name" class="form-control form-control-sm"
                                       placeholder="Organisation Name *" required>
                            </div>
                            <div class="col-md-6">
                                <input type="text" name="membership_number" class="form-control form-control-sm"
                                       placeholder="Membership Number">
                            </div>
                            <div class="col-md-6">
                                <input type="text" name="membership_type" class="form-control form-control-sm"
                                       placeholder="Type (e.g. Full, Associate)">
                            </div>
                            <div class="col-md-6">
                                <input type="date" name="expiry_date" class="form-control form-control-sm">
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-sm btn-success">
                                    <i class="bi bi-plus"></i> Add Membership
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Organisation</th><th>Number</th><th>Type</th><th>Expiry</th><th></th></tr></thead>
                    <tbody>
                        <?php if (!empty($memberships)): ?>
                        <?php foreach ($memberships as $mem): ?>
                        <tr>
                            <td><?= esc($mem->org_name) ?></td>
                            <td><?= esc($mem->membership_number ?: '-') ?></td>
                            <td><?= esc($mem->membership_type ?: '-') ?></td>
                            <td><?= !empty($mem->expiry_date) ? date('d M Y', strtotime($mem->expiry_date)) : '-' ?></td>
                            <td>
                                <form method="POST" action="<?= site_url('pilots/' . $pilot->id . '/memberships/' . $mem->id . '/delete') ?>" class="d-inline">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <tr><td colspan="5" class="text-muted text-center small">No memberships.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Equipment -->
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between">
                <span><i class="bi bi-drone"></i> Equipment</span>
                <button class="btn btn-sm btn-outline-success" data-bs-toggle="collapse" data-bs-target="#addEquip">
                    <i class="bi bi-plus"></i> Add
                </button>
            </div>
            <div class="collapse" id="addEquip">
                <div class="card-body border-bottom">
                    <form method="POST" action="<?= site_url('pilots/' . $pilot->id . '/equipment/add') ?>">
                        <?= csrf_field() ?>
                        <div class="row g-2">
                            <div class="col-md-4">
                                <input type="text" name="drone_model" class="form-control form-control-sm"
                                       placeholder="Drone Model *" required>
                            </div>
                            <div class="col-md-4">
                                <input type="text" name="serial_number" class="form-control form-control-sm"
                                       placeholder="Serial Number">
                            </div>
                            <div class="col-md-4">
                                <input type="text" name="registration_id" class="form-control form-control-sm"
                                       placeholder="Registration ID">
                            </div>
                            <div class="col-md-3">
                                <select name="class_mark" class="form-select form-select-sm">
                                    <option value="">Class Mark</option>
                                    <option value="C0">C0</option><option value="C1">C1</option>
                                    <option value="C2">C2</option><option value="C3">C3</option>
                                    <option value="C4">C4</option><option value="legacy">Legacy</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <input type="number" name="mtom_grams" class="form-control form-control-sm"
                                       placeholder="MTOM (g)" min="0">
                            </div>
                            <div class="col-md-3">
                                <select name="green_light_type" class="form-select form-select-sm">
                                    <option value="none">No Light</option>
                                    <option value="built_in">Built-in</option>
                                    <option value="external">External</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <div class="form-check mt-1">
                                    <input type="checkbox" name="has_camera" class="form-check-input" value="1" checked>
                                    <label class="form-check-label small">Camera</label>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" name="remote_id_capable" class="form-check-input" value="1">
                                    <label class="form-check-label small">Remote ID</label>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <input type="text" name="notes" class="form-control form-control-sm"
                                       placeholder="Notes">
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-sm btn-success w-100">
                                    <i class="bi bi-plus"></i> Add Equipment
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Drone</th><th>Class</th><th>MTOM</th><th>Serial</th><th>Reg ID</th><th></th></tr></thead>
                    <tbody>
                        <?php if (!empty($equipment)): ?>
                        <?php foreach ($equipment as $eq): ?>
                        <tr>
                            <td><?= esc($eq->drone_model) ?></td>
                            <td>
                                <?php if (!empty($eq->class_mark)): ?>
                                <span class="badge bg-info"><?= esc($eq->class_mark) ?></span>
                                <?php else: ?>-<?php endif; ?>
                            </td>
                            <td><?= isset($eq->mtom_grams) ? number_format($eq->mtom_grams) . 'g' : '-' ?></td>
                            <td><?= esc($eq->serial_number ?: '-') ?></td>
                            <td><?= esc($eq->registration_id ?: '-') ?></td>
                            <td>
                                <form method="POST" action="<?= site_url('pilots/' . $pilot->id . '/equipment/' . $eq->id . '/delete') ?>" class="d-inline">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <tr><td colspan="6" class="text-muted text-center small">No equipment.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Documents -->
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between">
                <span><i class="bi bi-file-earmark"></i> Documents</span>
                <button class="btn btn-sm btn-outline-success" data-bs-toggle="collapse" data-bs-target="#addDoc">
                    <i class="bi bi-plus"></i> Upload
                </button>
            </div>
            <div class="collapse" id="addDoc">
                <div class="card-body border-bottom">
                    <form method="POST" action="<?= site_url('pilots/' . $pilot->id . '/documents/upload') ?>" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <div class="row g-2">
                            <div class="col-md-4">
                                <select name="doc_type" class="form-select form-select-sm">
                                    <option value="certificate">Certificate</option>
                                    <option value="insurance">Insurance</option>
                                    <option value="license">License</option>
                                    <option value="other" selected>Other</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <input type="text" name="label" class="form-control form-control-sm"
                                       placeholder="Label">
                            </div>
                            <div class="col-md-4">
                                <input type="date" name="expiry_date" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-8">
                                <input type="file" name="file" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-sm btn-success w-100">
                                    <i class="bi bi-upload"></i> Upload
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Type</th><th>Label</th><th>File</th><th>Expiry</th><th></th></tr></thead>
                    <tbody>
                        <?php if (!empty($documents)): ?>
                        <?php foreach ($documents as $doc): ?>
                        <tr>
                            <td><span class="badge bg-secondary"><?= esc($doc->doc_type) ?></span></td>
                            <td><?= esc($doc->label) ?></td>
                            <td>
                                <a href="<?= site_url('pilots/' . $pilot->id . '/documents/' . $doc->id . '/download') ?>">
                                    <?= esc($doc->original_filename) ?>
                                </a>
                                <small class="text-muted">(<?= round(($doc->file_size ?? 0) / 1024, 1) ?>KB)</small>
                            </td>
                            <td><?= !empty($doc->expiry_date) ? date('d M Y', strtotime($doc->expiry_date)) : '-' ?></td>
                            <td>
                                <form method="POST" action="<?= site_url('pilots/' . $pilot->id . '/documents/' . $doc->id . '/delete') ?>" class="d-inline">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <tr><td colspan="5" class="text-muted text-center small">No documents.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
