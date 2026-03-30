<?= $this->extend('layouts/base') ?>
<?= $this->section('title') ?>Page<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="alert alert-info">
    <i class="bi bi-info-circle"></i> This page template is being ported from the Python version. 
    The backend logic is fully functional — this view needs template conversion.
</div>
<?= $this->endSection() ?>
