<?= $this->extend('layouts/base') ?>
<?= $this->section('title') ?>Orders<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-clipboard2-check"></i> Orders</h2>
    <span class="badge bg-secondary"><?= count($orders) ?> order<?= count($orders) != 1 ? 's' : '' ?></span>
</div>

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <?php foreach (['pending_assignment','assigned','accepted','in_progress','flight_complete','delivered','closed','declined','cancelled'] as $s): ?>
                    <option value="<?= $s ?>" <?= ($status_filter ?? '') === $s ? 'selected' : '' ?>><?= ucwords(str_replace('_', ' ', $s)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <select name="pilot_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Pilots</option>
                    <?php foreach ($pilots as $p): ?>
                    <option value="<?= $p->id ?>" <?= ($pilot_filter ?? '') == $p->id ? 'selected' : '' ?>><?= esc($p->display_name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <?php if (!empty($status_filter) || !empty($pilot_filter)): ?>
                <a href="<?= site_url('orders') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x"></i> Clear</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead class="table-light">
            <tr><th>Reference</th><th>Customer</th><th>Pilot</th><th>Status</th><th>Scheduled</th><th>Actions</th></tr>
        </thead>
        <tbody>
            <?php if (empty($orders)): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">No orders found.</td></tr>
            <?php else: foreach ($orders as $o): ?>
            <tr>
                <td><code><?= esc($o->reference) ?></code></td>
                <td><?= esc($o->customer_name) ?></td>
                <td><?= esc($o->pilot_name ?? 'Unassigned') ?></td>
                <td><span class="badge badge-<?= $o->status ?>"><?= ucwords(str_replace('_', ' ', $o->status)) ?></span></td>
                <td><?= $o->scheduled_date ? date('d M Y', strtotime($o->scheduled_date)) : '-' ?></td>
                <td><a href="<?= site_url('orders/' . $o->id) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a></td>
            </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<?php if (($total_pages ?? 1) > 1): ?>
<nav class="d-flex justify-content-center mt-3">
    <ul class="pagination pagination-sm">
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
        <li class="page-item <?= ($page ?? 1) == $i ? 'active' : '' ?>">
            <a class="page-link" href="?page=<?= $i ?><?= !empty($status_filter) ? '&status=' . urlencode($status_filter) : '' ?><?= !empty($pilot_filter) ? '&pilot_id=' . urlencode($pilot_filter) : '' ?>"><?= $i ?></a>
        </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>

<?= $this->endSection() ?>
