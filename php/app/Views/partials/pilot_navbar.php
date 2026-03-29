<nav class="navbar navbar-expand-lg navbar-dark bg-success">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?= site_url('pilot') ?>">
            <i class="bi bi-airplane"></i> FlyingPlan
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#pilotNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="pilotNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="<?= site_url('pilot') ?>">My Orders</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= site_url('pilot/profile') ?>">Profile</a>
                </li>
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
