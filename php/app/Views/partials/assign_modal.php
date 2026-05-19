<!-- Assign Pilot Modal -->
<div class="modal fade" id="assignModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="assignForm">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-person-check"></i> Assign Pilot</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Pilot</label>
                        <select name="pilot_id" class="form-select" required>
                            <option value="">Select Pilot...</option>
                            <?php foreach ($pilots ?? [] as $p): ?>
                            <option value="<?= $p->id ?>">
                                <?= esc($p->display_name) ?>
                                <?php if (!empty($p->flying_id)): ?>(<?= esc($p->flying_id) ?>)<?php endif; ?>
                                - <?= ucwords(str_replace('_', ' ', $p->availability_status ?? 'available')) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Scheduled Date</label>
                        <input type="date" name="scheduled_date" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Scheduled Time</label>
                        <input type="text" name="scheduled_time" class="form-control" placeholder="e.g. 9:00 AM">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Assignment Notes</label>
                        <textarea name="assignment_notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-person-check"></i> Assign
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
