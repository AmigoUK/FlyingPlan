<?= $this->extend('layouts/base') ?>
<?= $this->section('title') ?>Flight Parameters - Order #<?= $order->id ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <a href="<?= site_url('pilot/orders/' . $order->id) ?>" class="btn btn-sm btn-outline-secondary me-2">
            <i class="bi bi-arrow-left"></i> Back to Order
        </a>
        <strong class="fs-5">Flight Parameters</strong>
        <span class="badge bg-secondary ms-2">Order #<?= $order->id ?></span>
    </div>
</div>

<form method="POST" id="flightParamsForm">
    <?= csrf_field() ?>
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-drone"></i> Select Equipment</div>
                <div class="card-body">
                    <select name="equipment_id" id="equipmentSelect" class="form-select form-select-sm" required>
                        <option value="">-- Select Drone --</option>
                        <?php foreach ($equipment as $eq): ?>
                        <?php if ($eq->is_active): ?>
                        <option value="<?= $eq->id ?>" <?= ($order->equipment_id ?? '') == $eq->id ? 'selected' : '' ?>>
                            <?= esc($eq->drone_model) ?>
                            <?php if ($eq->class_mark): ?> (<?= esc($eq->class_mark) ?>)<?php endif; ?>
                            <?php if ($eq->mtom_grams): ?> — <?= \App\Models\PilotEquipmentModel::mtomDisplay($eq) ?><?php endif; ?>
                        </option>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                    <?php if (empty($equipment)): ?>
                    <div class="alert alert-warning mt-2 mb-0 small">
                        <i class="bi bi-exclamation-triangle"></i> No equipment registered.
                        <a href="<?= site_url('pilot/profile') ?>">Add equipment in your profile</a>.
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-sliders"></i> Flight Parameters</div>
                <div class="card-body">
                    <div class="row g-3">
                        <?php
                        $selects = [
                            ['Time of Day', 'time_of_day', ['day'=>'Day','twilight'=>'Twilight','night'=>'Night']],
                            ['VLOS Type', 'vlos_type', ['vlos'=>'VLOS','extended_vlos'=>'Extended VLOS','bvlos'=>'BVLOS']],
                            ['Proximity to People', 'proximity_to_people', ['50m_plus'=>'50m+ from people','near_under_50m'=>'Near people (<50m)','over_uninvolved'=>'Over uninvolved people','over_crowds'=>'Over assemblies of people','controlled_area'=>'Controlled/cordoned area']],
                            ['Environment', 'environment_type', ['open_countryside'=>'Open countryside','suburban'=>'Suburban','urban'=>'Urban','industrial'=>'Industrial','congested'=>'Congested area']],
                            ['Proximity to Buildings', 'proximity_to_buildings', ['over_150m'=>'150m+ from buildings','50_to_150m'=>'50-150m from buildings','under_50m'=>'<50m from buildings']],
                            ['Airspace', 'airspace_type', ['uncontrolled'=>'Uncontrolled (Class G)','frz'=>'Flight Restriction Zone','controlled'=>'Controlled airspace','restricted'=>'Restricted area','danger'=>'Danger area']],
                            ['Speed Mode', 'speed_mode', ['normal'=>'Normal','low_speed'=>'Low Speed','sport'=>'Sport']],
                        ];
                        foreach ($selects as [$label, $name, $options]): ?>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold"><?= $label ?></label>
                            <select name="<?= $name ?>" class="form-select form-select-sm fp-field" required>
                                <option value="">-- Select --</option>
                                <?php foreach ($options as $val => $text): ?>
                                <option value="<?= $val ?>" <?= ($order->$name ?? '') === $val ? 'selected' : '' ?>><?= $text ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-primary" id="checkCategoryBtn"><i class="bi bi-search"></i> Check Category</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Save &amp; Continue</button>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card sticky-top" style="top: 1rem;">
                <div class="card-header"><i class="bi bi-shield-check"></i> Category Determination</div>
                <div class="card-body" id="categoryResult">
                    <p class="text-muted small">Select equipment and fill in parameters, then click "Check Category".</p>
                </div>
            </div>
        </div>
    </div>
</form>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function() {
    var resultDiv = document.getElementById('categoryResult');
    var csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    function mkAlert(type, icon, text) {
        var d = document.createElement('div');
        d.className = 'alert alert-' + type + ' py-1 small mb-1';
        var i = document.createElement('i');
        i.className = 'bi bi-' + icon;
        d.appendChild(i);
        d.appendChild(document.createTextNode(' ' + text));
        return d;
    }

    document.getElementById('checkCategoryBtn').addEventListener('click', function() {
        var data = {};
        ['equipment_id','time_of_day','proximity_to_people','environment_type','proximity_to_buildings','airspace_type','vlos_type','speed_mode'].forEach(function(n) {
            var el = document.querySelector('[name="' + n + '"]');
            data[n] = el ? el.value : '';
        });
        data.equipment_id = document.getElementById('equipmentSelect').value;

        resultDiv.textContent = 'Checking...';

        fetch('<?= site_url('pilot/orders/' . $order->id . '/category-check') ?>', {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRFToken': csrfToken},
            body: JSON.stringify(data),
        })
        .then(function(r) { return r.json(); })
        .then(function(r) {
            resultDiv.textContent = '';
            var cc = r.category.indexOf('open_') === 0 ? (r.blockers.length ? 'warning' : 'success') : (r.category.indexOf('specific_') === 0 ? 'primary' : (r.category === 'certified' ? 'danger' : 'secondary'));
            var catName = r.category.replace(/_/g, ' ').replace(/\b\w/g, function(l){return l.toUpperCase();});

            var h5 = document.createElement('h5');
            var badge = document.createElement('span');
            badge.className = 'badge bg-' + cc;
            badge.textContent = catName;
            h5.appendChild(badge);
            resultDiv.appendChild(h5);

            var info = document.createElement('div');
            info.className = 'small mb-2';
            info.textContent = 'People: ' + r.min_distance_people_m + 'm | Buildings: ' + r.min_distance_buildings_m + 'm';
            resultDiv.appendChild(info);

            if (r.is_legal_ra_required) resultDiv.appendChild(mkAlert('info','info-circle','Documented risk assessment legally required.'));
            r.blockers.forEach(function(b){resultDiv.appendChild(mkAlert('danger','x-circle',b));});
            r.warnings.forEach(function(w){resultDiv.appendChild(mkAlert('warning','exclamation-triangle',w));});
        })
        .catch(function() { resultDiv.textContent = ''; resultDiv.appendChild(mkAlert('danger','x-circle','Error. Try again.')); });
    });
})();
</script>
<?= $this->endSection() ?>
