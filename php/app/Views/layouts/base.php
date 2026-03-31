<?php
$_appSettings = (new \App\Models\AppSettingsModel())->getSettings();
$_primaryColor = $_appSettings->primary_color ?? '#0d6efd';
$_primaryRgb = \App\Models\AppSettingsModel::primaryColorRgb($_appSettings);
$_darkMode = !empty($_appSettings->dark_mode);
$_businessName = $_appSettings->business_name ?? 'FlyingPlan';
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="<?= $_darkMode ? 'dark' : 'light' ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= csrf_hash() ?>">
    <title><?= $this->renderSection('title') ?> - <?= esc($_businessName) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= base_url('static/css/style.css') ?>" rel="stylesheet">
    <style>
    :root {
        --fp-primary: <?= $_primaryColor ?>;
        --fp-primary-text: var(--fp-primary);
        --fp-primary-subtle: var(--fp-primary);
        --bs-primary: <?= $_primaryColor ?>;
        --bs-primary-rgb: <?= $_primaryRgb ?>;
        --bs-link-color: <?= $_primaryColor ?>;
        --bs-link-hover-color: <?= $_primaryColor ?>;
    }
    /* Dark mode: lighten primary for text/borders on dark backgrounds */
    [data-bs-theme="dark"] {
        --fp-primary-text: color-mix(in srgb, var(--fp-primary), white 40%);
        --fp-primary-subtle: color-mix(in srgb, var(--fp-primary), white 60%);
        --bs-link-color: color-mix(in srgb, var(--fp-primary), white 40%);
        --bs-link-hover-color: color-mix(in srgb, var(--fp-primary), white 50%);
    }
    .btn-primary {
        --bs-btn-bg: var(--fp-primary);
        --bs-btn-border-color: var(--fp-primary);
        --bs-btn-hover-bg: color-mix(in srgb, var(--fp-primary), black 15%);
        --bs-btn-hover-border-color: color-mix(in srgb, var(--fp-primary), black 20%);
        --bs-btn-active-bg: color-mix(in srgb, var(--fp-primary), black 20%);
        --bs-btn-active-border-color: color-mix(in srgb, var(--fp-primary), black 25%);
    }
    .btn-outline-primary {
        --bs-btn-color: var(--fp-primary);
        --bs-btn-border-color: var(--fp-primary);
        --bs-btn-hover-bg: var(--fp-primary);
        --bs-btn-hover-border-color: var(--fp-primary);
        --bs-btn-active-bg: var(--fp-primary);
        --bs-btn-active-border-color: var(--fp-primary);
    }
    [data-bs-theme="dark"] .btn-outline-primary {
        --bs-btn-color: var(--fp-primary-text);
        --bs-btn-border-color: var(--fp-primary-text);
    }
    [data-bs-theme="dark"] .text-primary {
        color: var(--fp-primary-text) !important;
    }
    .bg-primary {
        background-color: var(--fp-primary) !important;
    }
    </style>
    <?= $this->renderSection('head_extra') ?>
</head>
<body>
<?php if (session('logged_in')): ?>
    <?php if (session('role') === 'pilot'): ?>
        <?= $this->include('partials/pilot_navbar') ?>
    <?php else: ?>
        <?= $this->include('partials/navbar') ?>
    <?php endif; ?>
<?php endif; ?>

<div class="container-fluid py-3">
    <?php foreach (['success', 'danger', 'warning', 'info'] as $type): ?>
        <?php if (session("flash_{$type}")): ?>
            <div class="alert alert-<?= $type ?> alert-dismissible fade show">
                <?= session("flash_{$type}") ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>

    <?= $this->renderSection('content') ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?= $this->renderSection('scripts') ?>
</body>
</html>
