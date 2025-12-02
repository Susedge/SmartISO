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

                <style>
                /* Star rating visuals */
                .rating-stars { display:inline-flex; flex-direction:row-reverse; gap:0.25rem; align-items:center; }
                .rating-stars input[type="radio"] { display:none; }
                .rating-stars label { cursor:pointer; color:#c8ccd0; font-size:1.25rem; display:inline-flex; align-items:center; justify-content:center; width:34px; height:34px; border-radius:6px; transition: color .12s ease, transform .08s ease, background .12s ease; }
                .rating-stars label .fa-star { pointer-events:none; }
                /* Hover and checked states */
                .rating-stars label:hover, .rating-stars label:hover ~ label, .rating-stars input:focus + label, .rating-stars input:checked + label, .rating-stars input:checked + label ~ label { color:#ffca28; transform: translateY(-2px); background: rgba(255,202,40,0.06); }
                /* Make sure label text for screen readers is hidden visually */
                .sr-only { position:absolute !important; width:1px; height:1px; padding:0; margin:-1px; overflow:hidden; clip:rect(0,0,0,0); white-space:nowrap; border:0; }
                </style>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Overall Rating <span class="text-danger">*</span></label>
                        <div class="rating-stars" role="radiogroup" aria-label="Overall rating">
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <input type="radio" name="rating" id="rating_<?= $i ?>" value="<?= $i ?>" <?= (string)old('rating') === (string)$i ? 'checked' : '' ?> required aria-label="<?= $i ?> star<?= $i>1? 's':'' ?>">
                                <label for="rating_<?= $i ?>" title="<?= $i ?> stars"><i class="fas fa-star" aria-hidden="true"></i><span class="sr-only"><?= $i ?> star<?= $i>1? 's':'' ?></span></label>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Service Quality</label>
                        <div class="rating-stars mt-2" role="radiogroup" aria-label="Service quality rating">
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <input type="radio" name="service_quality" id="service_quality_<?= $i ?>" value="<?= $i ?>" <?= (string)old('service_quality') === (string)$i ? 'checked' : '' ?> aria-label="<?= $i ?> star<?= $i>1? 's':'' ?>">
                                <label for="service_quality_<?= $i ?>" title="<?= $i ?> stars"><i class="fas fa-star" aria-hidden="true"></i><span class="sr-only"><?= $i ?> star<?= $i>1? 's':'' ?></span></label>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Timeliness</label>
                        <div class="rating-stars mt-2" role="radiogroup" aria-label="Timeliness rating">
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <input type="radio" name="timeliness" id="timeliness_<?= $i ?>" value="<?= $i ?>" <?= (string)old('timeliness') === (string)$i ? 'checked' : '' ?> aria-label="<?= $i ?> star<?= $i>1? 's':'' ?>">
                                <label for="timeliness_<?= $i ?>" title="<?= $i ?> stars"><i class="fas fa-star" aria-hidden="true"></i><span class="sr-only"><?= $i ?> star<?= $i>1? 's':'' ?></span></label>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Staff Professionalism</label>
                        <div class="rating-stars mt-2" role="radiogroup" aria-label="Staff professionalism rating">
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <input type="radio" name="staff_professionalism" id="staff_professionalism_<?= $i ?>" value="<?= $i ?>" <?= (string)old('staff_professionalism') === (string)$i ? 'checked' : '' ?> aria-label="<?= $i ?> star<?= $i>1? 's':'' ?>">
                                <label for="staff_professionalism_<?= $i ?>" title="<?= $i ?> stars"><i class="fas fa-star" aria-hidden="true"></i><span class="sr-only"><?= $i ?> star<?= $i>1? 's':'' ?></span></label>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Overall Satisfaction</label>
                        <div class="rating-stars mt-2" role="radiogroup" aria-label="Overall satisfaction rating">
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <input type="radio" name="overall_satisfaction" id="overall_satisfaction_<?= $i ?>" value="<?= $i ?>" <?= (string)old('overall_satisfaction') === (string)$i ? 'checked' : '' ?> aria-label="<?= $i ?> star<?= $i>1? 's':'' ?>">
                                <label for="overall_satisfaction_<?= $i ?>" title="<?= $i ?> stars"><i class="fas fa-star" aria-hidden="true"></i><span class="sr-only"><?= $i ?> star<?= $i>1? 's':'' ?></span></label>
                            <?php endfor; ?>
                        </div>
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
