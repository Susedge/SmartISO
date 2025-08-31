<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<?php // Compact schedule edit form. Expects $schedule array with fields ?>
<div class="card">
  <div class="card-body small">
    <form method="post" action="<?= base_url('schedule/update/'.$schedule['id']) ?>">
      <?= csrf_field() ?>
      <div class="row g-2">
        <div class="col-6">
          <label class="form-label">Scheduled Date</label>
          <input type="date" name="scheduled_date" value="<?= esc($schedule['scheduled_date'] ?? '') ?>" class="form-control form-control-sm" required />
        </div>
        <div class="col-6">
          <label class="form-label">Scheduled Time</label>
          <input type="time" name="scheduled_time" value="<?= esc($schedule['scheduled_time'] ?? '') ?>" class="form-control form-control-sm" />
        </div>
        <div class="col-6">
          <label class="form-label">Duration (mins)</label>
          <input type="number" name="duration_minutes" min="0" value="<?= esc($schedule['duration_minutes'] ?? '') ?>" class="form-control form-control-sm" />
        </div>
        <div class="col-6">
          <label class="form-label">Location</label>
          <input type="text" name="location" value="<?= esc($schedule['location'] ?? '') ?>" class="form-control form-control-sm" />
        </div>
        <div class="col-12">
          <label class="form-label">Notes</label>
          <textarea name="notes" class="form-control form-control-sm" rows="2"><?= esc($schedule['notes'] ?? '') ?></textarea>
        </div>
        <div class="col-6">
          <label class="form-label">Priority</label>
          <select name="priority_level" class="form-control form-control-sm">
            <option value="" <?= empty($schedule['priority_level']) ? 'selected' : '' ?>>None</option>
            <option value="high" <?= isset($schedule['priority_level']) && $schedule['priority_level']==='high' ? 'selected' : '' ?>>High (3d)</option>
            <option value="medium" <?= isset($schedule['priority_level']) && $schedule['priority_level']==='medium' ? 'selected' : '' ?>>Medium (4d)</option>
            <option value="low" <?= isset($schedule['priority_level']) && $schedule['priority_level']==='low' ? 'selected' : '' ?>>Low (5d)</option>
          </select>
        </div>
        <div class="col-6 d-flex align-items-end">
          <button type="submit" class="btn btn-primary btn-sm ms-auto">Update</button>
        </div>
      </div>
    </form>
  </div>
</div>
<?= $this->endSection() ?>
