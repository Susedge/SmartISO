<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3><?= $title ?></h3>
        <a href="<?= base_url('forms') ?>" class="btn btn-secondary">Back to Forms</a>
    </div>
    <div class="card-body">
        <?php if (session('message')): ?>
            <div class="alert alert-success"><?= session('message') ?></div>
        <?php endif; ?>
        
        <?php if (session('error')): ?>
            <div class="alert alert-danger"><?= session('error') ?></div>
        <?php endif; ?>
        
        <?php if (empty($panel_fields)): ?>
            <div class="alert alert-warning">
                No fields configured for this form.
            </div>
        <?php else: ?>
            <form action="<?= base_url('forms/submit') ?>" method="post" id="dynamicForm">
                <?= csrf_field() ?>
                <input type="hidden" name="form_id" value="<?= $form['id'] ?>">
                <input type="hidden" name="panel_name" value="<?= $panel_name ?>">
                
                <div class="row">
                    <?php 
                    $isRowOpen = false;
                    foreach ($panel_fields as $index => $field): 
                        // Determine if this field should be shown to the current user
                        $userType = session()->get('user_type');
                        $fieldRole = $field['field_role'] ?? 'both';
                        
                        $showField = false;
                        if ($fieldRole === 'both') {
                            $showField = true;
                        } elseif ($fieldRole === 'requestor' && $userType === 'requestor') {
                            $showField = true;
                        } elseif ($fieldRole === 'service_staff' && $userType === 'service_staff') {
                            $showField = true;
                        } elseif ($fieldRole === 'readonly') {
                            $showField = true;
                            $isReadOnly = true;
                        }
                        
                        if (!$showField) continue;
                        
                        $fieldWidth = isset($field['width']) ? (int)$field['width'] : 6;
                        $isRequired = isset($field['required']) && $field['required'] == 1;
                        $bumpNext = isset($field['bump_next_field']) && $field['bump_next_field'] == 1;
                        
                        // Close the row if the previous field didn't have bump_next_field set
                        if (!$isRowOpen) {
                            echo '<div class="row">';
                            $isRowOpen = true;
                        }
                    ?>
                        <div class="col-md-<?= $fieldWidth ?> mb-3">
                            <label for="<?= $field['field_name'] ?>" class="form-label">
                                <?= esc($field['field_label']) ?> <?= $isRequired ? '<span class="text-danger">*</span>' : '' ?>
                            </label>
                            
                            <?php if ($field['field_type'] === 'input'): ?>
                                <input type="text" 
                                    class="form-control" 
                                    id="<?= $field['field_name'] ?>" 
                                    name="<?= $field['field_name'] ?>" 
                                    <?= $field['length'] ? 'maxlength="' . $field['length'] . '"' : '' ?>
                                    <?= $field['bump_next_field'] ? 'data-bump-next="true"' : '' ?>
                                    <?= $isRequired ? 'required' : '' ?>>
                                    
                            <?php elseif ($field['field_type'] === 'dropdown'): ?>
                                <select class="form-select" 
                                    id="<?= $field['field_name'] ?>" 
                                    name="<?= $field['field_name'] ?>"
                                    <?= $field['bump_next_field'] ? 'data-bump-next="true"' : '' ?>
                                    <?= $isRequired ? 'required' : '' ?>>
                                    <option value="">Select...</option>
                                    <?php if ($field['code_table'] === 'departments'): ?>
                                        <?php foreach ($departments as $dept): ?>
                                            <option value="<?= $dept['id'] ?>"><?= esc($dept['code'] . ' - ' . $dept['description']) ?></option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                                
                            <?php elseif ($field['field_type'] === 'textarea'): ?>
                                <textarea class="form-control" 
                                    id="<?= $field['field_name'] ?>" 
                                    name="<?= $field['field_name'] ?>"
                                    rows="3"
                                    <?= $field['length'] ? 'maxlength="' . $field['length'] . '"' : '' ?>
                                    <?= $isRequired ? 'required' : '' ?>></textarea>
                                        
                            <?php elseif ($field['field_type'] === 'datepicker'): ?>
                                <input type="date" 
                                    class="form-control datepicker" 
                                    id="<?= $field['field_name'] ?>" 
                                    name="<?= $field['field_name'] ?>"
                                    <?= $field['bump_next_field'] ? 'data-bump-next="true"' : '' ?>
                                    <?= $isRequired ? 'required' : '' ?>>
                                    <?php elseif ($field['field_type'] === 'yesno'): ?>
                                <div class="d-flex">
                                    <div class="form-check me-4">
                                        <input class="form-check-input" type="radio" 
                                            id="<?= $field['field_name'] ?>_yes" 
                                            name="<?= $field['field_name'] ?>" 
                                            value="Yes"
                                            <?= $field['bump_next_field'] ? 'data-bump-next="true"' : '' ?>
                                            <?= $isRequired ? 'required' : '' ?>>
                                        <label class="form-check-label" for="<?= $field['field_name'] ?>_yes">
                                            Yes
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" 
                                            id="<?= $field['field_name'] ?>_no" 
                                            name="<?= $field['field_name'] ?>" 
                                            value="No"
                                            <?= $field['bump_next_field'] ? 'data-bump-next="true"' : '' ?>>
                                        <label class="form-check-label" for="<?= $field['field_name'] ?>_no">
                                            No
                                        </label>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php 
                        // Check if we need to close the row
                        if (!$bumpNext || $index == count($panel_fields) - 1) {
                            echo '</div>';  // Close the row
                            $isRowOpen = false;
                        }
                    endforeach; 
                    
                    // Ensure any open row is closed
                    if ($isRowOpen) {
                        echo '</div>';
                    }
                    ?>
                </div>
                
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">Submit</button>
                    <button type="reset" class="btn btn-secondary">Reset</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle bump_next_field functionality
    const fields = document.querySelectorAll('[data-bump-next="true"]');
    
    fields.forEach(field => {
        field.addEventListener('change', function() {
            // Find the next input in the form
            const inputs = Array.from(document.querySelectorAll('#dynamicForm input, #dynamicForm select, #dynamicForm textarea'));
            const currentIndex = inputs.indexOf(this);
            
            if (currentIndex !== -1 && currentIndex < inputs.length - 1) {
                // Move focus to the next input
                inputs[currentIndex + 1].focus();
            }
        });
    });
});
</script>
<?= $this->endSection() ?>
