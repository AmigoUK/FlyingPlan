<?php
$templates = \Config\TemplateDefinitions::getAll();
$activeTemplate = $settings->active_template ?? 'general';

$templateFeatures = [
    'solo_roof_inspector' => [
        'Solo operator mode — no pilot management',
        'Inspection-focused job types',
        'Facade scanner and GSD calculator enabled',
        'Simplified customer form — no creative fields',
    ],
    'wedding_event_videographer' => [
        'Solo operator mode — streamlined workflow',
        'Video duration, shot types, and output format required',
        'No planning tools — you fly manually',
        'Creative job types: events, real estate, aerial photo',
    ],
    'survey_mapping_company' => [
        'Multi-pilot team management',
        'All planning tools: grid, GSD, coverage, terrain, 3D',
        'Full compliance: flight params + risk assessment',
        'Business billing fields on customer form',
    ],
    'general' => [
        'Solo mode with most features enabled',
        'Balanced form — essential fields only',
        'Guide mode on — step-by-step instructions',
        'All 9 job types active',
    ],
    'custom' => [
        'Everything enabled from the start',
        'Full manual control over all settings',
        'Team management + all planning tools',
        'Configure each field and module yourself',
    ],
];
?>

<!-- Template Selection -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-grid-3x3-gap"></i> Choose Your Setup</span>
        <?php if ($activeTemplate && $activeTemplate !== 'custom'): ?>
        <span class="badge bg-success"><i class="bi bi-check-circle"></i> <?= esc(ucwords(str_replace('_', ' ', $activeTemplate))) ?></span>
        <?php elseif ($activeTemplate === 'custom'): ?>
        <span class="badge bg-secondary">Custom Configuration</span>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <p class="text-muted mb-4">Select the profile that best matches how you operate. You can customise everything afterwards.</p>

        <div class="row g-3">
            <?php foreach ($templates as $t): ?>
            <div class="col-sm-6 col-lg-4">
                <div class="card h-100 <?= $activeTemplate === $t['id'] ? 'border-primary' : '' ?>" style="<?= $activeTemplate === $t['id'] ? 'border-width: 2px;' : '' ?>">
                    <?php if ($activeTemplate === $t['id']): ?>
                    <div class="card-header bg-primary text-white py-1 text-center" style="font-size: 0.75rem; font-weight: 600;">
                        Current Setup
                    </div>
                    <?php endif; ?>
                    <div class="card-body d-flex flex-column">
                        <div class="text-center mb-3">
                            <i class="bi <?= esc($t['icon']) ?>" style="font-size: 2rem; color: var(--fp-primary);"></i>
                        </div>
                        <h5 class="card-title text-center mb-2"><?= esc($t['label']) ?></h5>
                        <p class="card-text text-muted small text-center mb-3"><?= esc($t['description']) ?></p>

                        <?php if (isset($templateFeatures[$t['id']])): ?>
                        <ul class="list-unstyled small mb-3" style="flex-grow: 1;">
                            <?php foreach ($templateFeatures[$t['id']] as $feat): ?>
                            <li class="mb-1"><i class="bi bi-check2 text-success me-1"></i> <?= esc($feat) ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <?php endif; ?>

                        <form method="POST" action="<?= site_url('settings/apply-template') ?>" class="mt-auto">
                            <?= csrf_field() ?>
                            <input type="hidden" name="template_id" value="<?= esc($t['id']) ?>">
                            <?php if ($activeTemplate === $t['id']): ?>
                            <button type="submit" class="btn btn-sm btn-outline-primary w-100" onclick="return confirm('Re-apply this template? Your custom changes will be reset.')">
                                <i class="bi bi-arrow-repeat"></i> Re-apply
                            </button>
                            <?php else: ?>
                            <button type="submit" class="btn btn-sm btn-primary w-100" onclick="return confirm('Apply this template? This will reconfigure your form fields, modules, and job types.')">
                                <i class="bi bi-check-lg"></i> Use This Setup
                            </button>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
