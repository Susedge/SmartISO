<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-header">
        <h3>Submit Feedback</h3>
    </div>
    <div class="card-body">
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
            <div class="mb-3">
                <label for="message" class="form-label">Your Feedback</label>
                <textarea class="form-control" id="message" name="message" rows="4" required><?= old('message') ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Submit</button>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
