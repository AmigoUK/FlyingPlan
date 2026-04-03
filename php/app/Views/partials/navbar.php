<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?= site_url('admin') ?>">
            <i class="bi bi-airplane"></i> FlyingPlan
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="<?= site_url('admin') ?>">Dashboard</a>
                </li>
                <?php if ((new \App\Models\AppSettingsModel())->isModuleEnabled('team')): ?>
                <li class="nav-item">
                    <a class="nav-link" href="<?= site_url('orders') ?>">Orders</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= site_url('pilots') ?>">Pilots</a>
                </li>
                <?php endif; ?>
                <?php if (session('role') === 'admin'): ?>
                <li class="nav-item">
                    <a class="nav-link" href="<?= site_url('settings') ?>">Settings</a>
                </li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item">
                    <span class="nav-link text-light"><?= esc(session('display_name')) ?></span>
                </li>
                <li class="nav-item">
                    <form method="POST" action="<?= site_url('logout') ?>" class="d-inline">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-outline-light btn-sm">Logout</button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</nav>
