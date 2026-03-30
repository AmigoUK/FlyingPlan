<?= $this->extend('layouts/base') ?>
<?= $this->section('title') ?>My Profile<?= $this->endSection() ?>

<?= $this->section('content') ?>
<h2 class="mb-4"><i class="bi bi-person-badge"></i> My Profile</h2>

<div class="row g-4">
    <!-- Left: Profile Form -->
    <div class="col-lg-5">
        <form method="POST" action="<?= site_url('pilot/profile') ?>">
            <?= csrf_field() ?>

            <!-- Personal Details -->
            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-person"></i> Personal Details</div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label small">Display Name</label>
                            <input type="text" name="display_name" class="form-control form-control-sm"
                                   value="<?= esc($user->display_name) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Email</label>
                            <input type="email" name="email" class="form-control form-control-sm"
                                   value="<?= esc($user->email ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Phone</label>
                            <input type="text" name="phone" class="form-control form-control-sm"
                                   value="<?= esc($user->phone ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">New Password <small class="text-muted">(optional)</small></label>
                            <input type="password" name="password" class="form-control form-control-sm">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Home Address -->
            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-house"></i> Home Address</div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-12">
                            <input type="text" name="address_line1" class="form-control form-control-sm"
                                   placeholder="Address Line 1" value="<?= esc($user->address_line1 ?? '') ?>">
                        </div>
                        <div class="col-12">
                            <input type="text" name="address_line2" class="form-control form-control-sm"
                                   placeholder="Address Line 2" value="<?= esc($user->address_line2 ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <input type="text" name="address_city" class="form-control form-control-sm"
                                   placeholder="City / Town" value="<?= esc($user->address_city ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <input type="text" name="address_county" class="form-control form-control-sm"
                                   placeholder="County" value="<?= esc($user->address_county ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <input type="text" name="address_postcode" class="form-control form-control-sm"
                                   placeholder="Postcode" value="<?= esc($user->address_postcode ?? '') ?>">
                        </div>
                        <div class="col-md-8">
                            <input type="text" name="address_country" class="form-control form-control-sm"
                                   placeholder="Country" value="<?= esc($user->address_country ?? 'United Kingdom') ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- CAA Regulatory IDs -->
            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-shield-check"></i> CAA Regulatory IDs</div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-8">
                            <label class="form-label small">Flyer ID</label>
                            <input type="text" name="flying_id" class="form-control form-control-sm"
                                   placeholder="GBR-RP-XXXXXXXXXX" value="<?= esc($user->flying_id ?? '') ?>">
                        </div>
                        <div class="col-4">
                            <label class="form-label small">Expiry</label>
                            <input type="date" name="flying_id_expiry" class="form-control form-control-sm"
                                   value="<?= $user->flying_id_expiry ?? '' ?>">
                        </div>
                        <div class="col-8">
                            <label class="form-label small">CAA Operator ID</label>
                            <input type="text" name="operator_id" class="form-control form-control-sm"
                                   placeholder="GBR-OP-XXXXXXXXXX" value="<?= esc($user->operator_id ?? '') ?>">
                        </div>
                        <div class="col-4">
                            <label class="form-label small">Expiry</label>
                            <input type="date" name="operator_id_expiry" class="form-control form-control-sm"
                                   value="<?= $user->operator_id_expiry ?? '' ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- UK Qualifications -->
            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-mortarboard"></i> UK Qualifications</div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-12"><h6 class="small text-muted mb-1">A2 Certificate of Competency</h6></div>
                        <div class="col-md-4">
                            <label class="form-label small">Cert Number</label>
                            <input type="text" name="a2_cofc_number" class="form-control form-control-sm"
                                   placeholder="e.g. A2-XX-2024-001" value="<?= esc($user->a2_cofc_number ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Expiry Date</label>
                            <input type="date" name="a2_cofc_expiry" class="form-control form-control-sm"
                                   value="<?= $user->a2_cofc_expiry ?? '' ?>">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <?php if (!empty($user->has_a2_cofc)): ?>
                            <span class="badge bg-success">VALID</span>
                            <?php elseif (!empty($user->a2_cofc_expiry)): ?>
                            <span class="badge bg-danger">EXPIRED</span>
                            <?php endif; ?>
                        </div>

                        <div class="col-12 mt-2"><h6 class="small text-muted mb-1">GVC / RPC</h6></div>
                        <div class="col-md-4">
                            <label class="form-label small">Level</label>
                            <select name="gvc_level" class="form-select form-select-sm">
                                <option value="">None</option>
                                <option value="GVC" <?= ($user->gvc_level ?? '') === 'GVC' ? 'selected' : '' ?>>GVC</option>
                                <option value="RPC_L1" <?= ($user->gvc_level ?? '') === 'RPC_L1' ? 'selected' : '' ?>>RPC Level 1</option>
                                <option value="RPC_L2" <?= ($user->gvc_level ?? '') === 'RPC_L2' ? 'selected' : '' ?>>RPC Level 2</option>
                                <option value="RPC_L3" <?= ($user->gvc_level ?? '') === 'RPC_L3' ? 'selected' : '' ?>>RPC Level 3</option>
                                <option value="RPC_L4" <?= ($user->gvc_level ?? '') === 'RPC_L4' ? 'selected' : '' ?>>RPC Level 4</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Cert Number</label>
                            <input type="text" name="gvc_cert_number" class="form-control form-control-sm"
                                   value="<?= esc($user->gvc_cert_number ?? '') ?>">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <?php if (!empty($user->has_gvc)): ?>
                            <span class="badge bg-success">VALID</span>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">GVC MR Expiry</label>
                            <input type="date" name="gvc_mr_expiry" class="form-control form-control-sm"
                                   value="<?= $user->gvc_mr_expiry ?? '' ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">GVC FW Expiry</label>
                            <input type="date" name="gvc_fw_expiry" class="form-control form-control-sm"
                                   value="<?= $user->gvc_fw_expiry ?? '' ?>">
                        </div>

                        <div class="col-12 mt-2"><h6 class="small text-muted mb-1">Operational Authorisation</h6></div>
                        <div class="col-md-4">
                            <label class="form-label small">OA Type</label>
                            <select name="oa_type" class="form-select form-select-sm">
                                <option value="">None</option>
                                <option value="PDRA_01" <?= ($user->oa_type ?? '') === 'PDRA_01' ? 'selected' : '' ?>>PDRA-01</option>
                                <option value="FULL_SORA" <?= ($user->oa_type ?? '') === 'FULL_SORA' ? 'selected' : '' ?>>Full SORA</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Reference</label>
                            <input type="text" name="oa_reference" class="form-control form-control-sm"
                                   value="<?= esc($user->oa_reference ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">OA Expiry</label>
                            <input type="date" name="oa_expiry" class="form-control form-control-sm"
                                   value="<?= $user->oa_expiry ?? '' ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Certificate of Competency -->
            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-patch-check"></i> Certificate of Competency</div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-12 mb-2">
                            <label class="form-label small">Drone Law (Theory)</label>
                            <?php if (!empty($user->drone_law_theory_valid)): ?>
                            <span class="badge bg-success">VALID</span>
                            <?php else: ?>
                            <span class="badge bg-danger">EXPIRED</span>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Practical Competency Date</label>
                            <input type="date" name="practical_competency_date" class="form-control form-control-sm"
                                   value="<?= $user->practical_competency_date ?? '' ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Mentor / Examiner</label>
                            <input type="text" name="mentor_examiner" class="form-control form-control-sm"
                                   value="<?= esc($user->mentor_examiner ?? '') ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Article 16 -->
            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-file-text"></i> Article 16 Operational Authorisation</div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-md-8">
                            <div class="form-check">
                                <input type="checkbox" name="article16_agreed" class="form-check-input" id="article16"
                                       <?= !empty($user->article16_agreed) ? 'checked' : '' ?>>
                                <label class="form-check-label small" for="article16">
                                    I have read and understood the Article 16 operational authorisation requirements
                                </label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Date Agreed</label>
                            <input type="date" name="article16_agreed_date" class="form-control form-control-sm"
                                   value="<?= $user->article16_agreed_date ?? '' ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Insurance -->
            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-shield"></i> Insurance</div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label small">Provider</label>
                            <input type="text" name="insurance_provider" class="form-control form-control-sm"
                                   value="<?= esc($user->insurance_provider ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Policy Number</label>
                            <input type="text" name="insurance_policy_no" class="form-control form-control-sm"
                                   value="<?= esc($user->insurance_policy_no ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Expiry</label>
                            <input type="date" name="insurance_expiry" class="form-control form-control-sm"
                                   value="<?= $user->insurance_expiry ?? '' ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bio -->
            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-journal-text"></i> Bio / Notes</div>
                <div class="card-body">
                    <textarea name="pilot_bio" class="form-control form-control-sm" rows="3"><?= esc($user->pilot_bio ?? '') ?></textarea>
                </div>
            </div>

            <!-- Save -->
            <button type="submit" class="btn btn-primary mb-3">
                <i class="bi bi-save"></i> Save Profile
            </button>
        </form>
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
                    <form method="POST" action="<?= site_url('pilot/add-certification') ?>">
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
                                    <i class="bi bi-plus"></i> Add
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
                            <td><?= esc($cert->issuing_body ?? '-') ?></td>
                            <td><?= esc($cert->cert_number ?? '-') ?></td>
                            <td><?= !empty($cert->expiry_date) ? date('d M Y', strtotime($cert->expiry_date)) : '-' ?></td>
                            <td>
                                <form method="POST" action="<?= site_url('pilot/delete-certification/' . $cert->id) ?>" class="d-inline">
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
                    <form method="POST" action="<?= site_url('pilot/add-membership') ?>">
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
                                    <i class="bi bi-plus"></i> Add
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
                            <td><?= esc($mem->membership_number ?? '-') ?></td>
                            <td><?= esc($mem->membership_type ?? '-') ?></td>
                            <td><?= !empty($mem->expiry_date) ? date('d M Y', strtotime($mem->expiry_date)) : '-' ?></td>
                            <td>
                                <form method="POST" action="<?= site_url('pilot/delete-membership/' . $mem->id) ?>" class="d-inline">
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
                    <form method="POST" action="<?= site_url('pilot/add-equipment') ?>">
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
                                <label class="form-label small">Class Mark</label>
                                <select name="class_mark" class="form-select form-select-sm">
                                    <option value="">Not set</option>
                                    <option value="C0">C0 (&lt;250g)</option>
                                    <option value="C1">C1 (&lt;900g)</option>
                                    <option value="C2">C2 (&lt;4kg)</option>
                                    <option value="C3">C3 (&lt;25kg)</option>
                                    <option value="C4">C4 (&lt;25kg)</option>
                                    <option value="legacy">Legacy</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">MTOM (grams)</label>
                                <input type="number" name="mtom_grams" class="form-control form-control-sm"
                                       placeholder="e.g. 249" min="0" max="50000">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Green Light</label>
                                <select name="green_light_type" class="form-select form-select-sm" id="greenLightType">
                                    <option value="none">None</option>
                                    <option value="built_in">Built-in</option>
                                    <option value="external">External</option>
                                </select>
                            </div>
                            <div class="col-md-3" id="greenLightWeightGroup" style="display: none;">
                                <label class="form-label small">Light Weight (g)</label>
                                <input type="number" name="green_light_weight_grams" class="form-control form-control-sm"
                                       placeholder="e.g. 20" min="0">
                            </div>
                            <div class="col-md-3">
                                <div class="form-check mt-3">
                                    <input type="checkbox" name="has_camera" class="form-check-input" id="hasCamera" value="1" checked>
                                    <label class="form-check-label small" for="hasCamera">Has Camera</label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-check mt-3">
                                    <input type="checkbox" name="has_low_speed_mode" class="form-check-input" id="lowSpeed" value="1">
                                    <label class="form-check-label small" for="lowSpeed">Low Speed Mode</label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-check mt-3">
                                    <input type="checkbox" name="remote_id_capable" class="form-check-input" id="remoteId" value="1">
                                    <label class="form-check-label small" for="remoteId">Remote ID</label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <input type="text" name="notes" class="form-control form-control-sm"
                                       placeholder="Notes">
                            </div>
                            <details class="col-12">
                                <summary class="small text-muted" style="cursor:pointer;">SORA Details (optional)</summary>
                                <div class="row g-2 mt-1">
                                    <div class="col-md-6">
                                        <label class="form-label small">Max Speed (m/s)</label>
                                        <input type="number" name="max_speed_ms" class="form-control form-control-sm" step="0.1" min="0">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small">Max Dimension (m)</label>
                                        <input type="number" name="max_dimension_m" class="form-control form-control-sm" step="0.01" min="0">
                                    </div>
                                </div>
                            </details>
                            <div class="col-12">
                                <button type="submit" class="btn btn-sm btn-success">Add</button>
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
                                <?php else: ?>
                                <span class="text-muted small">-</span>
                                <?php endif; ?>
                            </td>
                            <td><?= esc($eq->mtom_display ?? '') ?></td>
                            <td><?= esc($eq->serial_number ?? '-') ?></td>
                            <td><?= esc($eq->registration_id ?? '-') ?></td>
                            <td>
                                <form method="POST" action="<?= site_url('pilot/delete-equipment/' . $eq->id) ?>" class="d-inline">
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
                    <i class="bi bi-upload"></i> Upload
                </button>
            </div>
            <div class="collapse" id="addDoc">
                <div class="card-body border-bottom">
                    <form method="POST" action="<?= site_url('pilot/upload-document') ?>" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <div class="row g-2">
                            <div class="col-md-3">
                                <select name="doc_type" class="form-select form-select-sm">
                                    <option value="certificate">Certificate</option>
                                    <option value="insurance">Insurance</option>
                                    <option value="license">License</option>
                                    <option value="other" selected>Other</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <input type="text" name="label" class="form-control form-control-sm"
                                       placeholder="Label">
                            </div>
                            <div class="col-md-3">
                                <input type="date" name="expiry_date" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-3">
                                <input type="file" name="file" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-sm btn-success">
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
                                <a href="<?= site_url('pilot/download-document/' . $doc->id) ?>">
                                    <?= esc($doc->original_filename) ?>
                                </a>
                            </td>
                            <td><?= !empty($doc->expiry_date) ? date('d M Y', strtotime($doc->expiry_date)) : '-' ?></td>
                            <td>
                                <form method="POST" action="<?= site_url('pilot/delete-document/' . $doc->id) ?>" class="d-inline">
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

<?= $this->section('scripts') ?>
<script>
(function() {
    var gl = document.getElementById('greenLightType');
    var wg = document.getElementById('greenLightWeightGroup');
    if (gl && wg) {
        gl.addEventListener('change', function() {
            wg.style.display = this.value === 'external' ? '' : 'none';
        });
    }
})();
</script>
<?= $this->endSection() ?>
