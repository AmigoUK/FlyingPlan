<?php
$modules = json_decode($settings->modules_json ?? '{}', true) ?: [];
$formFields = json_decode($settings->form_fields_json ?? '{}', true) ?: [];
$panels = json_decode($settings->planning_panels_json ?? '{}', true) ?: [];
$pilotSteps = json_decode($settings->pilot_steps_json ?? '{}', true) ?: [];
$fieldRegistry = \Config\FormFieldRegistry::getFieldsByStep();
$stepLabels = [1 => 'Your Details', 2 => 'Job Brief', 3 => 'Location', 4 => 'Preferences'];
$alwaysRequired = \Config\FormFieldRegistry::getAlwaysRequired();
?>

<!-- Operating Mode & Modules -->
<div class="card mb-4">
    <div class="card-header"><i class="bi bi-toggles"></i> Operating Mode &amp; Modules</div>
    <div class="card-body">
        <form method="POST" action="<?= site_url('settings/operating-mode') ?>">
            <?= csrf_field() ?>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label fw-bold">Mode</label>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="solo_mode" id="soloMode" value="1"
                               <?= !empty($settings->solo_mode) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="soloMode">Solo operator <small class="text-muted">— skip pilot assignment, merged dashboard</small></label>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="guide_mode" id="guideMode" value="1"
                               <?= !empty($settings->guide_mode) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="guideMode">Guide mode <small class="text-muted">— step-by-step instructions in planning tools</small></label>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Default Drone</label>
                    <select name="default_drone_model" class="form-select form-select-sm">
                        <?php foreach (\App\Services\DroneProfiles::getChoices() as $choice): ?>
                        <option value="<?= esc($choice[0]) ?>" <?= ($settings->default_drone_model ?? '') === $choice[0] ? 'selected' : '' ?>><?= esc($choice[1]) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <label class="form-label fw-bold mb-2">Modules</label>
            <div class="row g-3 mb-3">
                <?php
                $moduleList = [
                    'planning'   => ['icon' => 'bi-map',           'label' => 'Planning Tools',     'desc' => 'GSD, grid, patterns, coverage, terrain, exports'],
                    'compliance' => ['icon' => 'bi-shield-check',  'label' => 'Compliance',         'desc' => 'Flight params, category engine, risk assessment'],
                    'team'       => ['icon' => 'bi-people',        'label' => 'Team Management',    'desc' => 'Pilot accounts, order assignment, certifications'],
                    'analytics'  => ['icon' => 'bi-graph-up',      'label' => 'Analytics',          'desc' => 'Weather, airspace, elevation data'],
                ];
                foreach ($moduleList as $key => $mod): ?>
                <div class="col-sm-6 col-lg-3">
                    <div class="card h-100 <?= !empty($modules[$key]) ? 'border-success' : '' ?>" style="<?= !empty($modules[$key]) ? 'border-width: 2px;' : '' ?>">
                        <div class="card-body text-center py-3">
                            <i class="bi <?= $mod['icon'] ?> d-block mb-1" style="font-size: 1.5rem; color: <?= !empty($modules[$key]) ? 'var(--fp-primary)' : '#adb5bd' ?>;"></i>
                            <div class="form-check form-switch d-inline-block mt-1">
                                <input class="form-check-input" type="checkbox" name="module_<?= $key ?>" id="mod_<?= $key ?>" value="1"
                                       <?= !empty($modules[$key]) ? 'checked' : '' ?>>
                                <label class="form-check-label fw-bold small" for="mod_<?= $key ?>"><?= $mod['label'] ?></label>
                            </div>
                            <p class="text-muted small mb-0 mt-1"><?= $mod['desc'] ?></p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <button type="submit" class="btn btn-primary btn-sm">
                <i class="bi bi-save"></i> Save Operating Mode
            </button>
        </form>
    </div>
</div>

<!-- Customer Form Fields -->
<div class="card mb-4">
    <div class="card-header"><i class="bi bi-ui-checks"></i> Customer Form Fields</div>
    <div class="card-body">
        <p class="text-muted small mb-3">Configure which fields customers see on the booking form. <strong>Required</strong> = must fill in. <strong>Optional</strong> = shown but not mandatory. <strong>Hidden</strong> = not shown at all.</p>
        <form method="POST" action="<?= site_url('settings/form-fields') ?>">
            <?= csrf_field() ?>

            <?php foreach ($fieldRegistry as $step => $fields): ?>
            <?php $stepIcons = [1 => 'bi-person', 2 => 'bi-briefcase', 3 => 'bi-geo-alt', 4 => 'bi-sliders']; ?>
            <h6 class="mt-3 mb-2 text-muted"><i class="bi <?= $stepIcons[$step] ?? 'bi-circle' ?>"></i> Step <?= $step ?>: <?= $stepLabels[$step] ?? '' ?></h6>
            <div class="table-responsive">
                <table class="table table-sm table-borderless mb-3">
                    <?php foreach ($fields as $name => $def): ?>
                    <tr>
                        <td class="py-1" style="width: 45%;">
                            <span class="small"><?= esc($def['label']) ?></span>
                            <?php if ($def['always']): ?>
                            <span class="badge bg-secondary ms-1" style="font-size: 0.6rem;">Always required</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-1">
                            <?php if ($def['always']): ?>
                            <input type="hidden" name="fields[<?= $name ?>]" value="required">
                            <span class="badge bg-primary">Required</span>
                            <?php else: ?>
                            <?php $current = $formFields[$name] ?? $def['default']; ?>
                            <select name="fields[<?= $name ?>]" class="form-select form-select-sm" style="max-width: 140px; font-size: 0.8rem;">
                                <option value="required" <?= $current === 'required' ? 'selected' : '' ?>>Required</option>
                                <option value="visible" <?= $current === 'visible' ? 'selected' : '' ?>>Optional</option>
                                <option value="hidden" <?= $current === 'hidden' ? 'selected' : '' ?>>Hidden</option>
                            </select>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
            <?php endforeach; ?>

            <button type="submit" class="btn btn-primary btn-sm">
                <i class="bi bi-save"></i> Save Form Configuration
            </button>
        </form>
    </div>
</div>

<!-- Planning Tools -->
<?php if (!empty($modules['planning'])): ?>
<div class="card mb-4">
    <div class="card-header"><i class="bi bi-map"></i> Planning Tool Panels</div>
    <div class="card-body">
        <p class="text-muted small mb-3">Choose which planning tools appear in the admin flight plan detail view.</p>
        <form method="POST" action="<?= site_url('settings/planning-panels') ?>">
            <?= csrf_field() ?>
            <div class="row g-2">
                <?php
                $panelList = [
                    'route_stats' => ['icon' => 'bi-speedometer2', 'label' => 'Route Stats',       'desc' => 'Distance, duration, battery estimates'],
                    'path_tools'  => ['icon' => 'bi-bezier2',      'label' => 'Path Tools',        'desc' => 'Reverse, straighten, offset route'],
                    'gsd'         => ['icon' => 'bi-rulers',       'label' => 'GSD Calculator',    'desc' => 'Ground sampling distance from altitude'],
                    'patterns'    => ['icon' => 'bi-circle',       'label' => 'Mission Patterns',  'desc' => 'Orbit, spiral, cable cam, multi-orbit'],
                    'grid'        => ['icon' => 'bi-grid-3x3',    'label' => 'Grid Planner',      'desc' => 'Automated lawnmower survey grid'],
                    'oblique'     => ['icon' => 'bi-box',          'label' => '3D Mapping Grid',   'desc' => 'Oblique angles for 3D reconstruction'],
                    'facade'      => ['icon' => 'bi-building',     'label' => 'Facade Scanner',    'desc' => 'Vertical face scanning patterns'],
                    'coverage'    => ['icon' => 'bi-heatmap',      'label' => 'Coverage Analysis',  'desc' => 'Photo overlap heatmap'],
                    'quality'     => ['icon' => 'bi-trophy',       'label' => 'Quality Report',    'desc' => 'Photogrammetry quality estimates'],
                ];
                foreach ($panelList as $key => $p): ?>
                <div class="col-sm-6 col-lg-4">
                    <div class="form-check form-switch p-2 rounded <?= !empty($panels[$key]) ? 'bg-light' : '' ?>">
                        <input class="form-check-input ms-0 me-2" type="checkbox" name="panels[<?= $key ?>]" id="panel_<?= $key ?>" value="1"
                               <?= ($panels[$key] ?? true) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="panel_<?= $key ?>">
                            <i class="bi <?= $p['icon'] ?> me-1"></i>
                            <strong class="small"><?= $p['label'] ?></strong>
                            <br><span class="text-muted" style="font-size: 0.75rem;"><?= $p['desc'] ?></span>
                        </label>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="submit" class="btn btn-primary btn-sm mt-3">
                <i class="bi bi-save"></i> Save Planning Tools
            </button>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Pilot Workflow -->
<?php if (!empty($modules['compliance'])): ?>
<div class="card mb-4">
    <div class="card-header"><i class="bi bi-clipboard-check"></i> Pilot Workflow Steps</div>
    <div class="card-body">
        <p class="text-muted small mb-3">Choose which compliance steps pilots must complete before marking a job done.</p>
        <form method="POST" action="<?= site_url('settings/pilot-steps') ?>">
            <?= csrf_field() ?>
            <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox" name="flight_params" id="step_fp" value="1"
                       <?= ($pilotSteps['flight_params'] ?? true) ? 'checked' : '' ?>>
                <label class="form-check-label" for="step_fp">
                    <strong>Flight Parameters</strong> <small class="text-muted">— equipment selection, category determination</small>
                </label>
            </div>
            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" name="risk_assessment" id="step_ra" value="1"
                       <?= ($pilotSteps['risk_assessment'] ?? true) ? 'checked' : '' ?>>
                <label class="form-check-label" for="step_ra">
                    <strong>Risk Assessment</strong> <small class="text-muted">— pre-flight safety checklist with weather</small>
                </label>
            </div>
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="bi bi-save"></i> Save Pilot Workflow
            </button>
        </form>
    </div>
</div>
<?php endif; ?>
