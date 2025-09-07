<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3><?= $title ?> - <?= $panel_name ?></h3>
        <div>
        <a href="<?= base_url('admin/dynamicforms') ?>" class="btn btn-secondary">Back to Forms</a>
    <a href="<?= base_url('admin/configurations?type=panels') ?>" class="btn btn-primary me-2">
                <i class="fas fa-cog"></i> Panel Configuration
            </a>
            </div>
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
                No fields configured for this panel. 
                <a href="<?= base_url('admin/dynamicforms/edit-panel/' . $panel_name) ?>" class="alert-link">
                    Configure panel fields
                </a>
            </div>
        <?php else: ?>
            <form action="<?= base_url('admin/dynamicforms/submit') ?>" method="post" id="dynamicForm">
                <?= csrf_field() ?>
                <input type="hidden" name="form_id" value="<?= $form['id'] ?>">
                <input type="hidden" name="panel_name" value="<?= $panel_name ?>">
                
                <div class="row">
                    <?php 
                    $curWidth = 0;
                    $totalFields = count($panel_fields);
                    foreach ($panel_fields as $index => $field): 
                        $fieldWidth = isset($field['width']) ? (int)$field['width'] : 6;
                        $isRequired = isset($field['required']) && $field['required'] == 1;
                        $bumpNext = isset($field['bump_next_field']) && $field['bump_next_field'] == 1;

                        // Start a new row if needed (either at beginning or overflow)
                        if ($curWidth === 0) {
                            echo '<div class="row">';
                        } elseif ($curWidth + $fieldWidth > 12) {
                            // overflow -> close previous and start new row
                            echo '</div><div class="row">';
                            $curWidth = 0;
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
                                    <?= $isRequired ? 'required' : '' ?> />

                            <?php elseif ($field['field_type'] === 'dropdown'): ?>
                                <select class="form-select" 
                                    id="<?= $field['field_name'] ?>" 
                                    name="<?= $field['field_name'] ?>"
                                    <?= $field['bump_next_field'] ? 'data-bump-next="true"' : '' ?>
                                    <?= $isRequired ? 'required' : '' ?> >
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
                                <?php 
                                    // Support CURRENTDATE keyword for date default values
                                    $defaultVal = isset($field['default_value']) ? trim($field['default_value']) : '';
                                    if (preg_match('/^CURRENTDATE$/i', $defaultVal)) {
                                        $dateDefault = date('Y-m-d');
                                    } else {
                                        $dateDefault = $defaultVal;
                                    }
                                ?>
                                <input type="date" 
                                    class="form-control datepicker" 
                                    id="<?= $field['field_name'] ?>" 
                                    name="<?= $field['field_name'] ?>"
                                    value="<?= esc($dateDefault) ?>"
                                    <?= $field['bump_next_field'] ? 'data-bump-next="true"' : '' ?>
                                    <?= $isRequired ? 'required' : '' ?> />

                            <?php elseif ($field['field_type'] === 'radio'): ?>
                                <?php
                                    $opts = [];
                                    if (!empty($field['options']) && is_array($field['options'])) {
                                        $opts = $field['options'];
                                    } elseif (!empty($field['default_value'])) {
                                        // Try to decode JSON stored in default_value (builder saves option arrays as JSON)
                                        $decoded = json_decode($field['default_value'], true);
                                        if (is_array($decoded) && !empty($decoded)) {
                                            $opts = $decoded;
                                        } else {
                                            // fallback: check newline separated defaults
                                            $lines = array_filter(array_map('trim', explode("\n", $field['default_value'])));
                                            if (!empty($lines)) $opts = $lines;
                                        }
                                    }
                                ?>
                                <div class="d-flex flex-wrap gap-2 align-items-center">
                                    <?php if (!empty($opts)): ?>
                                        <?php foreach ($opts as $oi => $opt): ?>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="checkbox" 
                                                    id="<?= $field['field_name'] ?>_<?= $oi ?>" 
                                                    name="<?= $field['field_name'] ?>[]" 
                                                    value="<?= esc($opt) ?>" 
                                                    <?= $field['bump_next_field'] ? 'data-bump-next="true"' : '' ?>
                                                    <?= $isRequired ? 'required' : '' ?> >
                                                <label class="form-check-label small" for="<?= $field['field_name'] ?>_<?= $oi ?>"><?= esc($opt) ?></label>
                                            </div>
                                        <?php endforeach; ?>
                                        <?php
                                            // Render an 'Other' input if one of the options is 'other' (case-insensitive)
                                            $hasOther = false;
                                            foreach ($opts as $optCheck) { if (preg_match('/^others?$/i', trim($optCheck))) { $hasOther = true; break; } }
                                        ?>
                                        <?php if ($hasOther): ?>
                                            <div class="d-inline-block ms-2">
                                                <input type="text" class="form-control form-control-sm other-input" name="<?= $field['field_name'] ?>_other" placeholder="Other (please specify)" style="display:none; max-width:220px">
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="checkbox" disabled>
                                            <label class="form-check-label small">Option 1</label>
                                        </div>
                                    <?php endif; ?>
                                </div>

                            
                            <?php endif; ?>
                        </div>
                    <?php 
                        // Update current width and decide if we should close the row
                        $curWidth += $fieldWidth;
                        $isLast = ($index == $totalFields - 1);
                        if (!$bumpNext || $isLast) {
                            echo '</div>'; // close current row
                            $curWidth = 0;
                        }
                    endforeach; 
                    // Ensure any open row is closed
                    if ($curWidth > 0) {
                        echo '</div>';
                    }
                    ?>
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
