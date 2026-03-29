<!DOCTYPE html>
<html lang="en" data-bs-theme="<?= session('dark_mode') ? 'dark' : 'light' ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= csrf_hash() ?>">
    <title><?= $this->renderSection('title') ?> - FlyingPlan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= base_url('static/css/style.css') ?>" rel="stylesheet">
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
