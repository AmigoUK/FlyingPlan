<?= $this->extend('layouts/base') ?>
<?= $this->section('title') ?>Settings<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-gear"></i> Settings</h2>
    <a href="<?= site_url('admin') ?>" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back to Dashboard
    </a>
</div>

<?= $this->include('admin/settings_templates') ?>

<!-- Branding -->
<div class="card mb-4">
    <div class="card-header"><i class="bi bi-palette"></i> Branding</div>
    <div class="card-body">
        <form method="POST" action="<?= site_url('settings/branding') ?>">
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Business Name</label>
                    <input type="text" class="form-control" name="business_name"
                           value="<?= esc($settings->business_name ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Contact Email</label>
                    <input type="email" class="form-control" name="contact_email"
                           value="<?= esc($settings->contact_email ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Logo URL</label>
                    <input type="text" class="form-control" name="logo_url"
                           value="<?= esc($settings->logo_url ?? '') ?>" placeholder="https://...">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Primary Color</label>
                    <div class="input-group">
                        <input type="color" class="form-control form-control-color" name="primary_color"
                               value="<?= esc($settings->primary_color ?? '#0d6efd') ?>" id="color-picker">
                        <input type="text" class="form-control" id="color-text"
                               value="<?= esc($settings->primary_color ?? '#0d6efd') ?>" readonly style="max-width: 100px;">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Tagline</label>
                    <input type="text" class="form-control" name="tagline"
                           value="<?= esc($settings->tagline ?? '') ?>">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="dark_mode"
                               id="sw-dark" <?= !empty($settings->dark_mode) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="sw-dark">Dark Mode</label>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary mt-3">
                <i class="bi bi-save"></i> Save Branding
            </button>
        </form>
    </div>
</div>

<!-- Guide Mode -->
<div class="card mb-4">
    <div class="card-header"><i class="bi bi-mortarboard"></i> Guide Mode</div>
    <div class="card-body">
        <form method="POST" action="<?= site_url('settings/form-visibility') ?>">
            <?= csrf_field() ?>
            <!-- carry current visibility values so they aren't wiped -->
            <?php if (!empty($settings->show_heard_about)): ?><input type="hidden" name="show_heard_about" value="on"><?php endif; ?>
            <?php if (!empty($settings->show_customer_type_toggle)): ?><input type="hidden" name="show_customer_type_toggle" value="on"><?php endif; ?>
            <?php if (!empty($settings->show_purpose_fields)): ?><input type="hidden" name="show_purpose_fields" value="on"><?php endif; ?>
            <?php if (!empty($settings->show_output_format)): ?><input type="hidden" name="show_output_format" value="on"><?php endif; ?>
            <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox" name="guide_mode"
                       id="sw-guide" <?= !empty($settings->guide_mode) ? 'checked' : '' ?>>
                <label class="form-check-label" for="sw-guide">Enable Guide Mode</label>
            </div>
            <p class="text-muted small mb-2">When enabled, shows step-by-step instructions inside each flight planning tool to help new pilots learn the workflow.</p>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save"></i> Save
            </button>
        </form>
    </div>
</div>

<!-- Form Visibility -->
<div class="card mb-4">
    <div class="card-header"><i class="bi bi-eye"></i> Form Visibility</div>
    <div class="card-body">
        <form method="POST" action="<?= site_url('settings/form-visibility') ?>">
            <?= csrf_field() ?>
            <!-- carry guide_mode so it isn't wiped -->
            <?php if (!empty($settings->guide_mode)): ?><input type="hidden" name="guide_mode" value="on"><?php endif; ?>
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="show_heard_about"
                               id="sw-heard" <?= !empty($settings->show_heard_about) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="sw-heard">Show "How did you hear about us?"</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="show_customer_type_toggle"
                               id="sw-ctype" <?= !empty($settings->show_customer_type_toggle) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="sw-ctype">Show Private/Business Toggle</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="show_purpose_fields"
                               id="sw-purpose" <?= !empty($settings->show_purpose_fields) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="sw-purpose">Show Purpose Fields</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="show_output_format"
                               id="sw-output" <?= !empty($settings->show_output_format) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="sw-output">Show Output Format</label>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary mt-3">
                <i class="bi bi-save"></i> Save Visibility
            </button>
        </form>
    </div>
</div>

<!-- Job Types -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-briefcase"></i> Job Types</span>
        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addJobTypeModal">
            <i class="bi bi-plus"></i> Add
        </button>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Icon</th>
                        <th>Label</th>
                        <th>Value</th>
                        <th>Category</th>
                        <th>Active</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($job_types as $jt): ?>
                    <tr class="<?= empty($jt->is_active) ? 'text-muted' : '' ?>">
                        <td><i class="bi <?= esc($jt->icon) ?>"></i></td>
                        <td><?= esc($jt->label) ?></td>
                        <td><code><?= esc($jt->value) ?></code></td>
                        <td><?= ucfirst(esc($jt->category)) ?></td>
                        <td>
                            <div class="form-check form-switch">
                                <input class="form-check-input settings-toggle" type="checkbox"
                                       <?= !empty($jt->is_active) ? 'checked' : '' ?>
                                       data-toggle-url="<?= site_url('settings/job-types/' . $jt->id . '/toggle') ?>">
                            </div>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                    data-bs-target="#editJobTypeModal-<?= $jt->id ?>">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal"
                                    data-bs-target="#confirmDeleteModal"
                                    data-item-name="<?= esc($jt->label) ?>"
                                    data-delete-url="<?= site_url('settings/job-types/' . $jt->id . '/delete') ?>">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Purpose Options -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-bullseye"></i> Footage Purpose Options</span>
        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addPurposeModal">
            <i class="bi bi-plus"></i> Add
        </button>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Icon</th>
                        <th>Label</th>
                        <th>Value</th>
                        <th>Active</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($purposes as $po): ?>
                    <tr class="<?= empty($po->is_active) ? 'text-muted' : '' ?>">
                        <td><i class="bi <?= esc($po->icon) ?>"></i></td>
                        <td><?= esc($po->label) ?></td>
                        <td><code><?= esc($po->value) ?></code></td>
                        <td>
                            <div class="form-check form-switch">
                                <input class="form-check-input settings-toggle" type="checkbox"
                                       <?= !empty($po->is_active) ? 'checked' : '' ?>
                                       data-toggle-url="<?= site_url('settings/purposes/' . $po->id . '/toggle') ?>">
                            </div>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                    data-bs-target="#editPurposeModal-<?= $po->id ?>">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal"
                                    data-bs-target="#confirmDeleteModal"
                                    data-item-name="<?= esc($po->label) ?>"
                                    data-delete-url="<?= site_url('settings/purposes/' . $po->id . '/delete') ?>">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Heard About Options -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-megaphone"></i> "How Did You Hear About Us?" Options</span>
        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addHeardAboutModal">
            <i class="bi bi-plus"></i> Add
        </button>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Icon</th>
                        <th>Label</th>
                        <th>Value</th>
                        <th>Active</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($heard_about as $ha): ?>
                    <tr class="<?= empty($ha->is_active) ? 'text-muted' : '' ?>">
                        <td><i class="bi <?= esc($ha->icon) ?>"></i></td>
                        <td><?= esc($ha->label) ?></td>
                        <td><code><?= esc($ha->value) ?></code></td>
                        <td>
                            <div class="form-check form-switch">
                                <input class="form-check-input settings-toggle" type="checkbox"
                                       <?= !empty($ha->is_active) ? 'checked' : '' ?>
                                       data-toggle-url="<?= site_url('settings/heard-about/' . $ha->id . '/toggle') ?>">
                            </div>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                    data-bs-target="#editHeardAboutModal-<?= $ha->id ?>">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal"
                                    data-bs-target="#confirmDeleteModal"
                                    data-item-name="<?= esc($ha->label) ?>"
                                    data-delete-url="<?= site_url('settings/heard-about/' . $ha->id . '/delete') ?>">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ── Modals ──────────────────────────────────────────────────── -->

<!-- Add Job Type Modal -->
<div class="modal fade" id="addJobTypeModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="<?= site_url('settings/job-types/new') ?>">
            <?= csrf_field() ?>
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Job Type</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Value (slug)</label>
                        <input type="text" class="form-control" name="value" required
                               placeholder="e.g. aerial_photo">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Label</label>
                        <input type="text" class="form-control" name="label" required
                               placeholder="e.g. Aerial Photography">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Icon (Bootstrap Icons)</label>
                        <input type="text" class="form-control" name="icon" value="bi-briefcase"
                               placeholder="e.g. bi-camera">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select class="form-select" name="category">
                            <option value="technical">Technical</option>
                            <option value="creative">Creative</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Edit Job Type Modals -->
<?php foreach ($job_types as $jt): ?>
<div class="modal fade" id="editJobTypeModal-<?= $jt->id ?>" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="<?= site_url('settings/job-types/' . $jt->id . '/edit') ?>">
            <?= csrf_field() ?>
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Job Type</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Value (slug)</label>
                        <input type="text" class="form-control" value="<?= esc($jt->value) ?>" disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Label</label>
                        <input type="text" class="form-control" name="label" value="<?= esc($jt->label) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Icon</label>
                        <input type="text" class="form-control" name="icon" value="<?= esc($jt->icon) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select class="form-select" name="category">
                            <option value="technical" <?= ($jt->category ?? '') === 'technical' ? 'selected' : '' ?>>Technical</option>
                            <option value="creative" <?= ($jt->category ?? '') === 'creative' ? 'selected' : '' ?>>Creative</option>
                            <option value="other" <?= ($jt->category ?? '') === 'other' ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </div>
        </form>
    </div>
</div>
<?php endforeach; ?>

<!-- Add Purpose Modal -->
<div class="modal fade" id="addPurposeModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="<?= site_url('settings/purposes/new') ?>">
            <?= csrf_field() ?>
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Purpose Option</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Value (slug)</label>
                        <input type="text" class="form-control" name="value" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Label</label>
                        <input type="text" class="form-control" name="label" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Icon</label>
                        <input type="text" class="form-control" name="icon" value="bi-question-circle">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Edit Purpose Modals -->
<?php foreach ($purposes as $po): ?>
<div class="modal fade" id="editPurposeModal-<?= $po->id ?>" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="<?= site_url('settings/purposes/' . $po->id . '/edit') ?>">
            <?= csrf_field() ?>
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Purpose Option</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Value (slug)</label>
                        <input type="text" class="form-control" value="<?= esc($po->value) ?>" disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Label</label>
                        <input type="text" class="form-control" name="label" value="<?= esc($po->label) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Icon</label>
                        <input type="text" class="form-control" name="icon" value="<?= esc($po->icon) ?>">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </div>
        </form>
    </div>
</div>
<?php endforeach; ?>

<!-- Add Heard About Modal -->
<div class="modal fade" id="addHeardAboutModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="<?= site_url('settings/heard-about/new') ?>">
            <?= csrf_field() ?>
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add "Heard About" Option</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Value (slug)</label>
                        <input type="text" class="form-control" name="value" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Label</label>
                        <input type="text" class="form-control" name="label" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Icon</label>
                        <input type="text" class="form-control" name="icon" value="bi-question-circle">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Edit Heard About Modals -->
<?php foreach ($heard_about as $ha): ?>
<div class="modal fade" id="editHeardAboutModal-<?= $ha->id ?>" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="<?= site_url('settings/heard-about/' . $ha->id . '/edit') ?>">
            <?= csrf_field() ?>
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit "Heard About" Option</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Value (slug)</label>
                        <input type="text" class="form-control" value="<?= esc($ha->value) ?>" disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Label</label>
                        <input type="text" class="form-control" name="label" value="<?= esc($ha->label) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Icon</label>
                        <input type="text" class="form-control" name="icon" value="<?= esc($ha->icon) ?>">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </div>
        </form>
    </div>
</div>
<?php endforeach; ?>

<!-- Confirm Delete Modal (shared) -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Delete <strong id="delete-item-name"></strong>?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" id="delete-form" style="display: inline;">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
// Color picker sync
const colorPicker = document.getElementById("color-picker");
const colorText = document.getElementById("color-text");
if (colorPicker && colorText) {
    colorPicker.addEventListener("input", function() {
        colorText.value = colorPicker.value;
    });
}

// Toggle switches (AJAX)
document.querySelectorAll(".settings-toggle").forEach(function(sw) {
    sw.addEventListener("change", function() {
        const url = sw.dataset.toggleUrl;
        const csrf = document.querySelector('meta[name="csrf-token"]').content;
        fetch(url, {
            method: "POST",
            headers: {
                "X-Requested-With": "XMLHttpRequest",
                "X-CSRFToken": csrf,
                "<?= csrf_token() ?>": csrf,
            },
        })
        .then(r => r.json())
        .then(data => {
            if (data.ok) {
                const row = sw.closest("tr");
                row.classList.toggle("text-muted", !data.is_active);
            }
        });
    });
});

// Confirm delete modal
const deleteModal = document.getElementById("confirmDeleteModal");
if (deleteModal) {
    deleteModal.addEventListener("show.bs.modal", function(event) {
        const btn = event.relatedTarget;
        document.getElementById("delete-item-name").textContent = btn.dataset.itemName;
        document.getElementById("delete-form").action = btn.dataset.deleteUrl;
    });
}
</script>
<?= $this->endSection() ?>
