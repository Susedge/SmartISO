<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-header">
        <h3>Submit Feedback</h3>
    </div>
    <div class="card-body">
        <?php if (isset($submission) && !empty($submission)): ?>
            <?php if (session('validation')): ?>
                <div class="alert alert-danger">
                    <ul>
                        <?php foreach (session('validation')->getErrors() as $error): ?>
                            <li><?= esc($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form action="<?= base_url('feedback/store') ?>" method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="submission_id" value="<?= esc($submission['id']) ?>">

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Overall Rating <span class="text-danger">*</span></label>
                        <select name="rating" class="form-select" required>
                            <option value="">-- Select rating --</option>
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <option value="<?= $i ?>" <?= old('rating') == $i ? 'selected' : '' ?>><?= $i ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Service Quality</label>
                        <select name="service_quality" class="form-select">
                            <option value="">-- Optional --</option>
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <option value="<?= $i ?>" <?= old('service_quality') == $i ? 'selected' : '' ?>><?= $i ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Timeliness</label>
                        <select name="timeliness" class="form-select">
                            <option value="">-- Optional --</option>
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <option value="<?= $i ?>" <?= old('timeliness') == $i ? 'selected' : '' ?>><?= $i ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Staff Professionalism</label>
                        <select name="staff_professionalism" class="form-select">
                            <option value="">-- Optional --</option>
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <option value="<?= $i ?>" <?= old('staff_professionalism') == $i ? 'selected' : '' ?>><?= $i ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Overall Satisfaction</label>
                        <select name="overall_satisfaction" class="form-select">
                            <option value="">-- Optional --</option>
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <option value="<?= $i ?>" <?= old('overall_satisfaction') == $i ? 'selected' : '' ?>><?= $i ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="comments" class="form-label">Comments</label>
                    <textarea class="form-control" id="comments" name="comments" rows="4"><?= old('comments') ?></textarea>
                </div>

                <div class="mb-3">
                    <label for="suggestions" class="form-label">Suggestions</label>
                    <textarea class="form-control" id="suggestions" name="suggestions" rows="3"><?= old('suggestions') ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary">Submit Feedback</button>
            </form>
        <?php else: ?>
            <div class="alert alert-warning">Invalid submission for feedback.</div>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>
