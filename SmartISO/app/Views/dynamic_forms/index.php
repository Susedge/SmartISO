<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container">
    <h1><?= $title ?></h1>
    
    <div class="card">
        <div class="card-header">
            Select a Form
        </div>
        <div class="card-body">
            <div class="form-group">
                <label for="form-select">Available Forms:</label>
                <select class="form-control" id="form-select">
                    <option value="">-- Select a form --</option>
                    <?php foreach ($forms as $form): ?>
                        <option value="<?= $form['id'] ?>"><?= $form['description'] ?> (<?= $form['code'] ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mt-3">
                <button id="load-form" class="btn btn-primary">Load Form</button>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('load-form').addEventListener('click', function() {
    const formId = document.getElementById('form-select').value;
    if (formId) {
        window.location.href = '<?= base_url('dynamic-forms/show/') ?>' + formId;
    } else {
        alert('Please select a form first');
    }
});
</script>
<?= $this->endSection() ?>
