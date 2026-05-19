<?= $this->extend('layouts/base') ?>
<?= $this->section('title') ?>Pilots<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-people"></i> Pilots</h2>
    <?php if (session('role') === 'admin'): ?>
    <a href="<?= site_url('pilots/new') ?>" class="btn btn-primary btn-sm"><i class="bi bi-person-plus"></i> New Pilot</a>
    <?php endif; ?>
</div>

<div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead class="table-light">
            <tr><th>Name</th><th>Email</th><th>Status</th><th>Availability</th><th>Flyer ID</th><th>Actions</th></tr>
        </thead>
        <tbody>
            <?php if (empty($pilots)): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">No pilots found.</td></tr>
            <?php else: foreach ($pilots as $p): ?>
            <tr class="<?= !$p->is_active_user ? 'table-secondary' : '' ?>">
                <td><strong><?= esc($p->display_name) ?></strong><br><small class="text-muted"><?= esc($p->username) ?></small></td>
                <td><?= esc($p->email ?? '-') ?></td>
                <td>
                    <?php if ($p->is_active_user): ?>
                    <span class="badge bg-success">Active</span>
                    <?php else: ?>
                    <span class="badge bg-secondary">Inactive</span>
                    <?php endif; ?>
                </td>
                <td><span class="badge bg-light text-dark"><?= ucwords(str_replace('_', ' ', $p->availability_status ?? 'available')) ?></span></td>
                <td><?= esc($p->flying_id ?? '-') ?></td>
                <td><a href="<?= site_url('pilots/' . $p->id) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i> View</a></td>
            </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
<?= $this->endSection() ?>
