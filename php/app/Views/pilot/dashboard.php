<?= $this->extend('layouts/base') ?>
<?= $this->section('title') ?>My Orders<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-clipboard-check"></i> My Orders</h2>
    <span class="badge bg-secondary"><?= count($orders) ?> order<?= count($orders) != 1 ? 's' : '' ?></span>
</div>

<?php
$pending = array_filter($orders, fn($o) => in_array($o->status, ['assigned']));
$active = array_filter($orders, fn($o) => in_array($o->status, ['accepted', 'in_progress']));
$completed = array_filter($orders, fn($o) => !in_array($o->status, ['assigned', 'accepted', 'in_progress']));
?>

<?php if (!empty($pending)): ?>
<h5 class="mb-3"><i class="bi bi-bell text-warning"></i> Awaiting Response</h5>
<div class="row g-3 mb-4">
    <?php foreach ($pending as $o): ?>
    <div class="col-md-6 col-lg-4">
        <div class="card border-warning">
            <div class="card-body">
                <h6 class="card-title"><?= esc($o->reference) ?></h6>
                <p class="mb-1 text-muted"><?= esc($o->customer_name) ?> &mdash; <?= ucwords(str_replace('_', ' ', $o->job_type)) ?></p>
                <span class="badge badge-<?= $o->status ?>"><?= ucwords(str_replace('_', ' ', $o->status)) ?></span>
                <?php if ($o->scheduled_date): ?>
                <small class="text-muted d-block mt-1"><?= date('d M Y', strtotime($o->scheduled_date)) ?></small>
                <?php endif; ?>
                <a href="<?= site_url('pilot/orders/' . $o->id) ?>" class="btn btn-sm btn-outline-primary mt-2">View</a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (!empty($active)): ?>
<h5 class="mb-3"><i class="bi bi-play-circle text-success"></i> Active</h5>
<div class="row g-3 mb-4">
    <?php foreach ($active as $o): ?>
    <div class="col-md-6 col-lg-4">
        <div class="card border-success">
            <div class="card-body">
                <h6 class="card-title"><?= esc($o->reference) ?></h6>
                <p class="mb-1 text-muted"><?= esc($o->customer_name) ?> &mdash; <?= ucwords(str_replace('_', ' ', $o->job_type)) ?></p>
                <span class="badge badge-<?= $o->status ?>"><?= ucwords(str_replace('_', ' ', $o->status)) ?></span>
                <a href="<?= site_url('pilot/orders/' . $o->id) ?>" class="btn btn-sm btn-outline-primary mt-2">View</a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (!empty($completed)): ?>
<h5 class="mb-3"><i class="bi bi-check-circle"></i> Previous</h5>
<div class="table-responsive">
    <table class="table table-sm table-hover">
        <thead class="table-light"><tr><th>Reference</th><th>Customer</th><th>Status</th><th>Date</th><th></th></tr></thead>
        <tbody>
            <?php foreach ($completed as $o): ?>
            <tr>
                <td><code><?= esc($o->reference) ?></code></td>
                <td><?= esc($o->customer_name) ?></td>
                <td><span class="badge badge-<?= $o->status ?>"><?= ucwords(str_replace('_', ' ', $o->status)) ?></span></td>
                <td><?= date('d M Y', strtotime($o->created_at)) ?></td>
                <td><a href="<?= site_url('pilot/orders/' . $o->id) ?>" class="btn btn-sm btn-outline-secondary">View</a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php if (empty($orders)): ?>
<div class="text-center text-muted py-5">
    <i class="bi bi-inbox" style="font-size: 3rem;"></i>
    <p class="mt-2">No orders assigned to you yet.</p>
</div>
<?php endif; ?>
<?= $this->endSection() ?>
