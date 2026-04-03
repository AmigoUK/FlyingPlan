<!-- Quick Create Modal -->
<div class="modal fade" id="quickCreateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= site_url('admin/quick-create') ?>">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-plus-circle"></i> New Brief</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3">Quick-create a flight plan from a phone call or email. You can add details later.</p>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Customer Name <span class="text-danger">*</span></label>
                        <input type="text" name="customer_name" class="form-control form-control-sm" required placeholder="Full name">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Email <span class="text-danger">*</span></label>
                        <input type="email" name="customer_email" class="form-control form-control-sm" required placeholder="email@example.com">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Phone</label>
                        <input type="text" name="customer_phone" class="form-control form-control-sm" placeholder="+44 7700 ...">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Job Type <span class="text-danger">*</span></label>
                        <select name="job_type" class="form-select form-select-sm" required>
                            <option value="">-- Select --</option>
                            <?php
                            $jobTypes = \Config\Database::connect()->table('job_types')->where('is_active', 1)->orderBy('sort_order')->get()->getResult();
                            foreach ($jobTypes as $jt): ?>
                            <option value="<?= esc($jt->value) ?>"><?= esc($jt->label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Description <span class="text-danger">*</span></label>
                        <textarea name="job_description" class="form-control form-control-sm" rows="3" required placeholder="Brief description of what the customer needs..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Location</label>
                        <input type="text" name="location_address" class="form-control form-control-sm" placeholder="Address or postcode (optional — add pin later)">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Preferred Dates</label>
                        <input type="text" name="preferred_dates" class="form-control form-control-sm" placeholder="e.g. Next week, 15 April, ASAP">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-plus-circle"></i> Create Brief</button>
                </div>
            </form>
        </div>
    </div>
</div>
