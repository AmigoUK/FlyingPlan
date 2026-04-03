<?= $this->extend('layouts/base') ?>
<?= $this->section('title') ?>Dashboard<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-grid-3x3-gap"></i> Flight Plan Dashboard</h2>
    <span class="badge bg-secondary"><?= count($plans) ?> submission<?= count($plans) != 1 ? 's' : '' ?></span>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small mb-0">Status</label>
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <?php foreach (['new', 'in_review', 'route_planned', 'completed', 'cancelled'] as $s): ?>
                    <option value="<?= $s ?>" <?= ($status_filter ?? '') === $s ? 'selected' : '' ?>>
                        <?= ucwords(str_replace('_', ' ', $s)) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-0">Job Type</label>
                <select name="job_type" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Types</option>
                    <?php
                    $jobTypes = (new \App\Models\JobTypeModel())->getActive();
                    foreach ($jobTypes as $jt): ?>
                    <option value="<?= $jt->value ?>" <?= ($job_type_filter ?? '') === $jt->value ? 'selected' : '' ?>>
                        <?= esc($jt->label) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small mb-0">Search</label>
                <div class="input-group input-group-sm">
                    <input type="text" name="q" class="form-control" value="<?= esc($search ?? '') ?>"
                           placeholder="Name, email, reference...">
                    <button class="btn btn-outline-primary" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </div>
            <div class="col-md-2">
                <?php if (!empty($status_filter) || !empty($job_type_filter) || !empty($search)): ?>
                <a href="<?= site_url('admin') ?>" class="btn btn-outline-secondary btn-sm w-100">
                    <i class="bi bi-x"></i> Clear
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Table -->
<div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead class="table-light">
            <tr>
                <th>Reference</th>
                <th>Customer</th>
                <th>Job Type</th>
                <th>Urgency</th>
                <th>Status</th>
                <th>Order</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($plans)): ?>
            <tr>
                <td colspan="8" class="text-center text-muted py-4">No flight plans found.</td>
            </tr>
            <?php else: ?>
            <?php foreach ($plans as $fp): ?>
            <tr>
                <td><code><?= esc($fp->reference) ?></code></td>
                <td>
                    <strong><?= esc($fp->customer_name) ?></strong>
                    <?php if (!empty($fp->customer_company)): ?>
                    <br><small class="text-muted"><?= esc($fp->customer_company) ?></small>
                    <?php endif; ?>
                </td>
                <td><?= ucwords(str_replace('_', ' ', $fp->job_type)) ?></td>
                <td><span class="urgency-<?= $fp->urgency ?>"><?= ucfirst($fp->urgency) ?></span></td>
                <td>
                    <span class="badge badge-<?= $fp->status ?>">
                        <?= ucwords(str_replace('_', ' ', $fp->status)) ?>
                    </span>
                </td>
                <td>
                    <?php if (!empty($fp->order)): ?>
                    <a href="<?= site_url('orders/' . $fp->order->id) ?>">
                        <span class="badge badge-<?= $fp->order->status ?>">
                            <?= ucwords(str_replace('_', ' ', $fp->order->status)) ?>
                        </span>
                    </a>
                    <?php else: ?>
                    <button class="btn btn-sm btn-outline-success"
                            data-bs-toggle="modal" data-bs-target="#assignModal"
                            onclick="document.getElementById('assignForm').action='<?= site_url('orders/create/' . $fp->id) ?>'">
                        <i class="bi bi-person-plus"></i> Assign
                    </button>
                    <?php endif; ?>
                </td>
                <td><?= date('d M Y', strtotime($fp->created_at)) ?></td>
                <td>
                    <a href="<?= site_url('admin/' . $fp->id) ?>" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-eye"></i> View
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if (isset($pager)): ?>
<div class="d-flex justify-content-center mt-3">
    <?= $pager->links() ?>
</div>
<?php endif; ?>

<?= $this->include('partials/assign_modal') ?>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
if (typeof gtag === 'function') {
    gtag('event', 'demo_login', { role: '<?= esc(session('role') ?? 'unknown') ?>', page: 'admin_dashboard' });
}
</script>
<?= $this->endSection() ?>
