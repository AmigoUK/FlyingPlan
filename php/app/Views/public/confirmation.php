<?= $this->extend('layouts/base') ?>
<?= $this->section('title') ?>Submission Confirmed<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="row justify-content-center mt-5">
    <div class="col-md-6 text-center">
        <div class="card shadow-sm">
            <div class="card-body p-5">
                <div class="mb-3">
                    <i class="bi bi-check-circle text-success" style="font-size: 4rem;"></i>
                </div>
                <h3 class="mb-3">Your request has been received</h3>
                <p class="text-muted mb-2">
                    We'll review your brief and get in touch by email or phone to discuss the details.
                </p>
                <p class="text-muted mb-4">
                    We aim to respond within <strong>1 working day</strong>.
                </p>
                <div class="alert alert-info">
                    <strong>Your Reference Code:</strong>
                    <h4 class="mt-1 mb-0"><?= esc($reference ?? '') ?></h4>
                </div>
                <p class="text-muted small">Keep your reference code safe — you may need it if you contact us.</p>
                <p class="mt-4 mb-0">Thank you for choosing our drone services.</p>
                <p class="mt-3"><a href="<?= site_url('/') ?>" class="text-muted small">Submit another request</a></p>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
