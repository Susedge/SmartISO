<?php // Compact schedule create form ?>
<div class="card">
  <div class="card-body small">
    <form method="post" action="<?= base_url('schedule/store') ?>">
      <?= csrf_field() ?>
      <div class="row g-2">
        <div class="col-6">
          <label class="form-label">Scheduled Date</label>
          <input type="date" name="scheduled_date" class="form-control form-control-sm" required />
        </div>
        <div class="col-6">
          <label class="form-label">Scheduled Time</label>
          <input type="time" name="scheduled_time" class="form-control form-control-sm" />
        </div>
        <div class="col-6">
          <label class="form-label">Duration (mins)</label>
          <input type="number" name="duration_minutes" min="0" class="form-control form-control-sm" />
        </div>
        <div class="col-6">
          <label class="form-label">Location</label>
          <input type="text" name="location" class="form-control form-control-sm" />
        </div>
        <div class="col-12">
          <label class="form-label">Notes</label>
          <textarea name="notes" class="form-control form-control-sm" rows="2"></textarea>
        </div>
        <div class="col-6">
          <label class="form-label">Priority</label>
          <select name="priority_level" class="form-control form-control-sm">
            <option value="">None</option>
            <option value="high">High (3d)</option>
            <option value="medium">Medium (4d)</option>
            <option value="low">Low (5d)</option>
          </select>
        </div>
        <div class="col-6 d-flex align-items-end">
          <button type="submit" class="btn btn-primary btn-sm ms-auto">Save</button>
        </div>
      </div>
    </form>
  </div>
</div>
