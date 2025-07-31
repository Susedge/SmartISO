/**
 * Drag and Drop Form Builder JavaScript
 * For SmartISO Dynamic Forms
 */

class FormBuilder {
    constructor() {
        this.panelName = window.panelName || '';
        this.fields = [];
        this.draggedElement = null;
        this.sortableInstances = [];
        this.init();
    }

    // Panel name editing removed

    init() {
        // Check if required DOM elements exist
        const formBuilderContainer = document.querySelector('.form-builder-container');
        const dropZone = document.getElementById('formBuilderDropZone');
        
        if (!formBuilderContainer || !dropZone) {
            console.error('Required form builder elements not found');
            return;
        }
        
        this.setupEventListeners();
        this.loadExistingFields();
    }

    setupEventListeners() {
        // Field palette draggable setup
        this.setupFieldPalette();
        
        // Form builder area setup
        this.setupFormBuilder();
        
        // Field actions
        this.setupFieldActions();
        
        // Save and Preview buttons
        this.setupSavePreviewButtons();
    }

    setupFieldPalette() {
        const palette = document.querySelector('.field-palette');
        if (!palette) return;

        const fieldTypes = palette.querySelectorAll('.field-type-item');
        fieldTypes.forEach(item => {
            // Make field type items draggable
            item.draggable = true;
            item.setAttribute('draggable', 'true');
            
            item.addEventListener('dragstart', (e) => {
                const fieldType = e.target.getAttribute('data-field-type') || 
                                e.target.closest('.field-type-item').getAttribute('data-field-type');
                console.log('Dragging field type:', fieldType);
                e.dataTransfer.setData('text/plain', fieldType);
                e.dataTransfer.setData('application/x-palette-item', 'true'); // Mark as palette drag
                e.dataTransfer.effectAllowed = 'copy';
                e.target.classList.add('dragging');
            });

            item.addEventListener('dragend', (e) => {
                e.target.classList.remove('dragging');
            });
        });
    }

    setupFormBuilder() {
        const dropZone = document.getElementById('formBuilderDropZone');
        if (!dropZone) return;

        dropZone.addEventListener('dragover', (e) => {
            // Only handle external drags (from palette), not internal sortable drags
            const isPaletteItem = e.dataTransfer.types.includes('application/x-palette-item');
            if (isPaletteItem) {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'copy';
                dropZone.classList.add('drag-over');
                // Highlight the row under the cursor
                const rows = Array.from(dropZone.querySelectorAll('.row'));
                let found = false;
                for (const row of rows) {
                    const rect = row.getBoundingClientRect();
                    if (e.clientY >= rect.top && e.clientY <= rect.bottom) {
                        row.classList.add('drag-target');
                        found = true;
                    } else {
                        row.classList.remove('drag-target');
                    }
                }
                // If not over any row, remove all drag-targets
                if (!found) {
                    rows.forEach(row => row.classList.remove('drag-target'));
                }
            }
        });

        dropZone.addEventListener('dragleave', (e) => {
            if (!dropZone.contains(e.relatedTarget)) {
                dropZone.classList.remove('drag-over');
                // Remove all row highlights
                const rows = dropZone.querySelectorAll('.row');
                rows.forEach(row => row.classList.remove('drag-target'));
            }
        });

        dropZone.addEventListener('drop', (e) => {
            // Remove all row highlights
            const rows = dropZone.querySelectorAll('.row');
            rows.forEach(row => row.classList.remove('drag-target'));
            // Only handle external drags (from palette), not internal sortable drags
            const isPaletteItem = e.dataTransfer.types.includes('application/x-palette-item');
            const fieldType = e.dataTransfer.getData('text/plain');
            if (isPaletteItem && fieldType) {
                e.preventDefault();
                e.stopPropagation();
                dropZone.classList.remove('drag-over');

                // Find the row under the drop position
                const rows = Array.from(dropZone.querySelectorAll('.row'));
                const dropY = e.clientY;
                let insertRow = null;
                let insertIndex = this.fields.length;
                for (const row of rows) {
                    const rect = row.getBoundingClientRect();
                    if (dropY >= rect.top && dropY <= rect.bottom) {
                        insertRow = row;
                        // Find the field in this row after which to insert
                        const children = Array.from(row.querySelectorAll('.field-item-container'));
                        for (let i = 0; i < children.length; i++) {
                            const childRect = children[i].getBoundingClientRect();
                            if (dropY < childRect.top + childRect.height / 2) {
                                const fieldId = children[i].dataset.fieldId;
                                insertIndex = this.fields.findIndex(f => f.id === fieldId);
                                break;
                            }
                        }
                        // If not found, insert at end of row
                        if (insertIndex === this.fields.length && children.length > 0) {
                            const lastFieldId = children[children.length - 1].dataset.fieldId;
                            insertIndex = this.fields.findIndex(f => f.id === lastFieldId) + 1;
                        }
                        break;
                    }
                }

                // Create the new field
                const fieldData = this.getFieldConfigFromPanel(fieldType);
                // Insert at the calculated index
                this.fields.splice(insertIndex, 0, fieldData);
                this.reorganizeFormLayout();
            }
        });

        // Setup sortable for existing fields
        this.setupSortable();
    }

    setupSortable() {
        const dropZone = document.getElementById('formBuilderDropZone');
        if (!dropZone) return;

        // Check if Sortable is available
        if (typeof Sortable === 'undefined') {
            console.error('SortableJS library is not loaded');
            return;
        }

        // Destroy existing sortable instances to prevent memory leaks
        if (this.sortableInstances && Array.isArray(this.sortableInstances)) {
            this.sortableInstances.forEach(instance => {
                if (instance && typeof instance.destroy === 'function') {
                    try {
                        instance.destroy();
                    } catch (e) {
                        console.warn('Error destroying sortable instance:', e);
                    }
                }
            });
        }
        this.sortableInstances = [];

        // Make each row sortable with proper cross-row dragging
        const rows = dropZone.querySelectorAll('.row');
        console.log(`Setting up sortable for ${rows.length} rows`);
        
        rows.forEach((row, rowIndex) => {
            try {
                const sortable = new Sortable(row, {
                    group: {
                        name: 'form-fields',
                        pull: true,
                        put: true
                    },
                    animation: 200,
                    ghostClass: 'sortable-ghost',
                    chosenClass: 'sortable-chosen',
                    dragClass: 'sortable-drag',
                    handle: '.drag-btn',
                    draggable: '.field-item-container',
                    filter: '.field-type-item', // Exclude palette items
                    preventOnFilter: false,
                    onStart: (evt) => {
                        console.log('SORTABLE START: Field', evt.item.dataset.fieldId);
                        evt.item.classList.add('dragging');
                        // Only highlight rows, not the whole form builder
                        dropZone.querySelectorAll('.row').forEach(r => r.classList.add('drop-zone-active'));
                    },
                    onEnd: (evt) => {
                        console.log('SORTABLE END: Field moved');
                        evt.item.classList.remove('dragging');
                        // Remove drop zone highlights
                        dropZone.querySelectorAll('.row').forEach(r => {
                            r.classList.remove('drop-zone-active', 'drop-zone-invalid');
                        });
                        this.updateFieldOrderFromDOM();
                        this.validateRowWidths();
                    },
                    onMove: (evt) => {
                        // Only allow moving existing fields, not palette items
                        if (evt.dragged.classList.contains('field-type-item')) {
                            return false;
                        }
                        
                        // Check if drop would exceed row width limit
                        const targetRow = evt.to;
                        const draggedField = this.fields.find(f => f.id === evt.dragged.dataset.fieldId);
                        
                        if (this.wouldExceedRowWidth(targetRow, draggedField, evt.dragged)) {
                            targetRow.classList.add('drop-zone-invalid');
                            return false; // Prevent drop
                        } else {
                            targetRow.classList.remove('drop-zone-invalid');
                            return true;
                        }
                    }
                });
                
                this.sortableInstances.push(sortable);
            } catch (error) {
                console.error(`Error setting up sortable for row ${rowIndex}:`, error);
            }
        });
    }

    wouldExceedRowWidth(row, draggedField, draggedElement) {
        if (!draggedField) return false;
        
        const currentFields = Array.from(row.children)
            .filter(child => child !== draggedElement && child.classList.contains('field-item-container'))
            .map(element => this.fields.find(f => f.id === element.dataset.fieldId))
            .filter(field => field);
        
        const currentWidth = currentFields.reduce((sum, field) => sum + parseInt(field.width), 0);
        const newTotalWidth = currentWidth + parseInt(draggedField.width);
        
        return newTotalWidth > 12;
    }

    validateRowWidths() {
        const formBuilder = document.querySelector('.form-builder-area');
        if (!formBuilder) return;
        
        const rows = formBuilder.querySelectorAll('.row');
        
        rows.forEach(row => {
            const fields = Array.from(row.children)
                .filter(child => child.classList.contains('field-item-container'))
                .map(element => this.fields.find(f => f.id === element.dataset.fieldId))
                .filter(field => field);
            
            const totalWidth = fields.reduce((sum, field) => sum + parseInt(field.width), 0);
            
            if (totalWidth > 12) {
                row.classList.add('row-width-exceeded');
                row.style.backgroundColor = 'rgba(220, 53, 69, 0.1)';
                row.style.border = '2px solid #dc3545';
            } else {
                row.classList.remove('row-width-exceeded');
                row.style.backgroundColor = '';
                row.style.border = '';
            }
        });
    }

    updateFieldOrderFromDOM() {
        const formBuilder = document.querySelector('.form-builder-area');
        if (!formBuilder) return;
        
        const fieldElements = formBuilder.querySelectorAll('.field-item-container');
        
        fieldElements.forEach((element, index) => {
            const fieldId = element.dataset.fieldId;
            const field = this.fields.find(f => f.id === fieldId);
            if (field) {
                field.field_order = index + 1;
                console.log(`Updated field ${fieldId} order to ${index + 1}`);
            }
        });
        
        // Sort the fields array to match the new order
        this.fields.sort((a, b) => a.field_order - b.field_order);
    }

    addFieldToForm(fieldType, dropY = null) {
        const field = {
            id: 'field_' + Date.now() + '_' + Math.random(),
            type: fieldType,
            label: this.getDefaultLabel(fieldType),
            name: 'field_' + fieldType + '_' + Date.now(),
            required: false,
            placeholder: '',
            value: '',
            width: 12, // Default full width
            size: 'col-md-12', // Bootstrap class
            field_order: this.fields.length + 1,
            options: fieldType === 'select' ? ['Option 1', 'Option 2'] : null
        };
        
        this.fields.push(field);
        
        // Show configuration modal for new field
        this.openFieldConfig(field, true);
        
        // Reorganize layout after adding
        this.reorganizeFormLayout();
    }

    openFieldConfig(field, isNew = false) {
        this.showFieldConfigurationPanel(field);
    }

    getDefaultLabel(fieldType) {
        const labels = {
            'text': 'Text Field',
            'email': 'Email Field',
            'password': 'Password Field',
            'number': 'Number Field',
            'textarea': 'Text Area',
            'select': 'Select Field',
            'checkbox': 'Checkbox',
            'radio': 'Radio Button',
            'date': 'Date Field',
            'file': 'File Upload',
            'hidden': 'Hidden Field'
        };
        return labels[fieldType] || 'Field';
    }

    reorganizeFormLayout() {
        const dropZone = document.getElementById('formBuilderDropZone');
        if (!dropZone) return;
        
        // Clear existing layout
        dropZone.innerHTML = '';
        
        if (this.fields.length === 0) {
            this.updateEmptyState();
            return;
        }
        
        dropZone.classList.remove('empty');
        
        // Sort fields by order
        const sortedFields = [...this.fields].sort((a, b) => a.field_order - b.field_order);
        
        let currentRow = document.createElement('div');
        currentRow.className = 'row';
        dropZone.appendChild(currentRow);
        let currentRowWidth = 0;
        
        sortedFields.forEach((field, index) => {
            field.field_order = index + 1;
            const fieldWidth = parseInt(field.width) || 12;

            // Check if field fits in current row
            if (currentRowWidth + fieldWidth > 12 && currentRowWidth > 0) {
                // Start new row
                currentRow = document.createElement('div');
                currentRow.className = 'row';
                dropZone.appendChild(currentRow);
                currentRowWidth = 0;
            }

            const fieldElement = this.createFieldElement(field);
            currentRow.appendChild(fieldElement);
            currentRowWidth += fieldWidth;

            // If bump_next_field is false and not the last field, force new row
            if (field.bump_next_field === false && index < sortedFields.length - 1) {
                currentRow = document.createElement('div');
                currentRow.className = 'row';
                dropZone.appendChild(currentRow);
                currentRowWidth = 0;
            }
        });
        
        // Re-setup sortable after reorganizing
        this.setupSortable();
        
        // Validate row widths
        this.validateRowWidths();
    }

    getDefaultFieldData(fieldType, fieldId) {
        const baseData = {
            id: fieldId,
            field_name: fieldId,
            field_label: this.capitalize(fieldType) + ' Field',
            field_type: fieldType,
            field_role: 'both',
            required: false,
            width: 6,
            field_order: this.fields.length + 1,
            bump_next_field: false,
            code_table: '',
            length: ''
        };

        // Type-specific defaults
        switch (fieldType) {
            case 'input':
                baseData.field_label = 'Text Input';
                break;
            case 'textarea':
                baseData.field_label = 'Text Area';
                baseData.width = 12;
                break;
            case 'dropdown':
                baseData.field_label = 'Dropdown';
                baseData.code_table = 'departments';
                break;
            case 'datepicker':
                baseData.field_label = 'Date';
                break;
            case 'yesno':
                baseData.field_label = 'Yes/No';
                break;
        }

        return baseData;
    }

    createFieldElement(fieldData) {
        // Get width from various possible field properties
        const width = fieldData.width || fieldData.size || 12;
        const colClass = `col-md-${width}`;

        // Create the Bootstrap column container
        const colDiv = document.createElement('div');
        let bumpClass = '';
        if (fieldData.bump_next_field) {
            bumpClass = ' field-bump-next';
        }
        colDiv.className = `${colClass} field-item-container${bumpClass}`;
        colDiv.dataset.fieldId = fieldData.id;

        // Create the mini panel for hover controls
        const miniPanel = document.createElement('div');
        miniPanel.className = 'field-mini-panel';
        miniPanel.innerHTML = `
            <span class="field-type-label">${fieldData.type || fieldData.field_type}</span>
            <span class="field-width-label">W: ${width}/12</span>
            <select class="field-width-dropdown" data-field-id="${fieldData.id}" title="Change width">
                <option value="3" ${width == 3 ? 'selected' : ''}>3</option>
                <option value="4" ${width == 4 ? 'selected' : ''}>4</option>
                <option value="6" ${width == 6 ? 'selected' : ''}>6</option>
                <option value="8" ${width == 8 ? 'selected' : ''}>8</option>
                <option value="9" ${width == 9 ? 'selected' : ''}>9</option>
                <option value="12" ${width == 12 ? 'selected' : ''}>12</option>
            </select>
            <div class="field-controls">
                <button class="field-control-btn drag-btn" title="Drag to reorder" type="button">
                    <i class="fas fa-grip-vertical"></i>
                </button>
                <button class="field-control-btn edit-btn" title="Edit field" type="button" onclick="formBuilder.editField('${fieldData.id}')">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="field-control-btn delete-btn" title="Delete field" type="button" onclick="formBuilder.deleteField('${fieldData.id}')">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `;

        // Add event listener for width dropdown
        setTimeout(() => {
            const widthDropdown = miniPanel.querySelector('.field-width-dropdown');
            if (widthDropdown) {
                widthDropdown.addEventListener('change', (e) => {
                    const newWidth = parseInt(e.target.value);
                    const fieldId = e.target.getAttribute('data-field-id');
                    formBuilder.updateFieldWidth(fieldId, newWidth);
                });
            }
        }, 0);

        // Generate the actual form field HTML
        const fieldHTML = this.generateFieldPreview(fieldData);

        // Create field content container
        const fieldContent = document.createElement('div');
        fieldContent.className = 'field-content';
        fieldContent.innerHTML = fieldHTML;

        // Assemble the complete field element
        colDiv.appendChild(miniPanel);
        colDiv.appendChild(fieldContent);

        return colDiv;
    }
    // Add method to update field width and re-render
    updateFieldWidth(fieldId, newWidth) {
        const field = this.fields.find(f => f.id === fieldId);
        if (field) {
            field.width = newWidth;
            this.reorganizeFormLayout();
        }
    }

    generateFieldPreview(fieldData) {
        let fieldHTML = '';
        const requiredLabel = fieldData.required ? '<span class="text-danger">*</span>' : '';
        const label = fieldData.label || fieldData.field_label || 'Field Label';
        const name = fieldData.name || fieldData.field_name || 'field_name';
        const placeholder = fieldData.placeholder || `Enter ${label.toLowerCase()}`;
        
        // Add field label
        fieldHTML += `<label class="form-label">${label} ${requiredLabel}</label>`;
        
        // Generate field based on type
        const fieldType = fieldData.type || fieldData.field_type;
        
        switch (fieldType) {
            case 'text':
            case 'input':
                fieldHTML += `<input type="text" class="form-control" name="${name}" placeholder="${placeholder}" ${fieldData.required ? 'required' : ''} disabled>`;
                break;
            case 'email':
                fieldHTML += `<input type="email" class="form-control" name="${name}" placeholder="${placeholder}" ${fieldData.required ? 'required' : ''} disabled>`;
                break;
            case 'password':
                fieldHTML += `<input type="password" class="form-control" name="${name}" placeholder="${placeholder}" ${fieldData.required ? 'required' : ''} disabled>`;
                break;
            case 'number':
                fieldHTML += `<input type="number" class="form-control" name="${name}" placeholder="${placeholder}" ${fieldData.required ? 'required' : ''} disabled>`;
                break;
            case 'textarea':
                fieldHTML += `<textarea class="form-control" name="${name}" rows="3" placeholder="${placeholder}" ${fieldData.required ? 'required' : ''} disabled></textarea>`;
                break;
            case 'select':
            case 'dropdown':
                fieldHTML += `<select class="form-select" name="${name}" ${fieldData.required ? 'required' : ''} disabled>
                    <option value="">Select ${label.toLowerCase()}</option>`;
                if (fieldData.options && Array.isArray(fieldData.options)) {
                    fieldData.options.forEach(option => {
                        fieldHTML += `<option value="${option}">${option}</option>`;
                    });
                } else {
                    fieldHTML += `
                        <option value="option1">Option 1</option>
                        <option value="option2">Option 2</option>
                        <option value="option3">Option 3</option>`;
                }
                fieldHTML += `</select>`;
                break;
            case 'checkbox':
                fieldHTML += `
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="${name}" ${fieldData.required ? 'required' : ''} disabled>
                        <label class="form-check-label">${label}</label>
                    </div>
                `;
                break;
            case 'radio':
            case 'yesno':
                fieldHTML += `<div class="d-flex">
                    <div class="form-check me-3">
                        <input class="form-check-input" type="radio" name="${name}" value="yes" ${fieldData.required ? 'required' : ''} disabled>
                        <label class="form-check-label">Yes</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="${name}" value="no" disabled>
                        <label class="form-check-label">No</label>
                    </div>
                </div>`;
                break;
            case 'date':
            case 'datepicker':
                fieldHTML += `<input type="date" class="form-control" name="${name}" ${fieldData.required ? 'required' : ''} disabled>`;
                break;
            case 'file':
                fieldHTML += `<input type="file" class="form-control" name="${name}" ${fieldData.required ? 'required' : ''} disabled>`;
                break;
            case 'hidden':
                fieldHTML += `<input type="hidden" name="${name}" value="${fieldData.value || ''}" disabled>
                    <small class="text-muted">Hidden field: ${name}</small>`;
                break;
            default:
                fieldHTML += `<input type="text" class="form-control" name="${name}" placeholder="${placeholder}" ${fieldData.required ? 'required' : ''} disabled>`;
        }
        
        return fieldHTML;
    }

    getWidthDescription(width) {
        const descriptions = {
            1: '(Tiny)',
            2: '(Very Small)', 
            3: '(Quarter)',
            4: '(Third)',
            5: '(Small)',
            6: '(Half)',
            7: '(Large)',
            8: '(Two Thirds)',
            9: '(Three Quarters)',
            10: '(Very Large)',
            11: '(Almost Full)',
            12: '(Full Width)'
        };
        return descriptions[width] || '';
    }

    getBootstrapColumnClass(width) {
        // Ensure width is a valid number between 1 and 12
        const validWidth = Math.max(1, Math.min(12, parseInt(width) || 12));
        return `col-md-${validWidth}`;
    }

    setupFieldActions() {
        document.addEventListener('click', (e) => {
            if (e.target.closest('.edit-field')) {
                const fieldId = e.target.closest('.edit-field').dataset.fieldId;
                this.editField(fieldId);
            }
            if (e.target.closest('.delete-field')) {
                const fieldId = e.target.closest('.delete-field').dataset.fieldId;
                this.showDeleteModal(fieldId);
            }
        });

        // Preview Form button
        const previewBtn = document.getElementById('previewForm');
        if (previewBtn) {
            previewBtn.addEventListener('click', () => {
                this.previewForm();
            });
        }
    }

    showDeleteModal(fieldId) {
        // Deprecated: no confirmation, call deleteField directly
        this.deleteField(fieldId);
    }

    setupSavePreviewButtons() {
        // Save Form button
        const saveBtn = document.getElementById('saveFormBuilder');
        if (saveBtn) {
            saveBtn.addEventListener('click', () => {
                this.saveForm();
            });
        }

        // Save Edited Field button
        const saveEditedFieldBtn = document.getElementById('saveEditedField');
        if (saveEditedFieldBtn) {
            saveEditedFieldBtn.addEventListener('click', () => {
                this.saveEditedField();
            });
        }

        // Preview Form button - already handled in setupFieldActions
    }

    saveForm() {
        // Get panel ID from URL
        const urlParts = window.location.pathname.split('/');
        const panelId = urlParts[urlParts.length - 1];
        
        if (!panelId || isNaN(panelId)) {
            alert('Invalid panel ID');
            return;
        }

        const formData = {
            panel_id: panelId,
            fields: this.fields
        };

        fetch('/SmartISO/SmartISO/public/admin/dynamicforms/save-form-builder', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(formData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Form saved successfully!');
            } else {
                alert('Error saving form: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error saving form');
        });
    }


    previewForm() {
        if (this.fields.length === 0) {
            alert('Please add at least one field to preview the form.');
            return;
        }
        
        // Create preview modal
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.id = 'formPreviewModal';
        modal.innerHTML = `
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Form Preview</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="previewForm">
                            ${this.generateFormPreview()}
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
        
        // Clean up modal when hidden
        modal.addEventListener('hidden.bs.modal', () => {
            modal.remove();
        });
    }

    generateFormPreview() {
        const sortedFields = [...this.fields].sort((a, b) => a.field_order - b.field_order);
        let html = '<div class="row">';
        
        sortedFields.forEach(field => {
            const colClass = this.getBootstrapColumnClass(field.width);
            html += `<div class="${colClass} mb-3">`;
            html += this.generateFieldPreview(field);
            html += '</div>';
        });
        
        html += '</div>';
        return html;
    }

    editField(fieldId) {
        const fieldData = this.fields.find(f => f.id === fieldId);
        if (fieldData) {
            this.showEditModal(fieldData);
        }
    }

    showEditModal(field) {
        // Store the current editing field ID
        window.currentEditingFieldId = field.id;
        
        // Populate the modal form
        document.getElementById('editFieldType').value = field.type || field.field_type || 'input';
        document.getElementById('editFieldLabel').value = field.label || field.field_label || '';
        document.getElementById('editFieldName').value = field.name || field.field_name || '';
        document.getElementById('editFieldWidth').value = field.width || 12;
        document.getElementById('editFieldRequired').checked = field.required || false;
        document.getElementById('editFieldBumpNext').checked = field.bump_next_field || false;
        
        // Handle options for dropdown fields
        const optionsContainer = document.getElementById('editOptionsContainer');
        const fieldType = field.type || field.field_type;
        if (fieldType === 'dropdown') {
            optionsContainer.style.display = 'block';
            if (field.options && Array.isArray(field.options)) {
                document.getElementById('editFieldOptions').value = field.options.join('\n');
            }
        } else {
            optionsContainer.style.display = 'none';
        }
        
        // Show the modal
        const modal = new bootstrap.Modal(document.getElementById('fieldEditModal'));
        modal.show();
    }

    saveEditedField() {
        const fieldId = window.currentEditingFieldId;
        if (!fieldId) return;

        const field = this.fields.find(f => f.id === fieldId);
        if (!field) return;

        // Get values from the modal form
        field.type = document.getElementById('editFieldType').value;
        field.field_type = field.type; // Keep both for compatibility
        field.label = document.getElementById('editFieldLabel').value;
        field.field_label = field.label; // Keep both for compatibility
        field.name = document.getElementById('editFieldName').value;
        field.field_name = field.name; // Keep both for compatibility
        field.width = parseInt(document.getElementById('editFieldWidth').value);
        field.required = document.getElementById('editFieldRequired').checked;
        field.bump_next_field = document.getElementById('editFieldBumpNext').checked;

        // Handle options for dropdown fields
        if (field.type === 'dropdown') {
            const optionsText = document.getElementById('editFieldOptions').value;
            field.options = optionsText.split('\n').filter(option => option.trim() !== '');
        }

        // Update the field in the DOM
        this.updateFieldInDOM(field);
        
        // Hide the modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('fieldEditModal'));
        modal.hide();
        
        // Clear the current editing field ID
        window.currentEditingFieldId = null;
    }

    updateFieldInDOM(field) {
        const fieldElement = document.querySelector(`[data-field-id="${field.id}"]`);
        if (!fieldElement) return;

        // Update the field content
        const newFieldHTML = this.createFieldElement(field);
        fieldElement.outerHTML = newFieldHTML;
        
        // Reorganize the form layout
        this.reorganizeFormLayout();
    }

    deleteField(fieldId) {
        // No confirmation - delete immediately
        console.log('Deleting field:', fieldId);
        
        // Remove from DOM
        const fieldElement = document.querySelector(`[data-field-id="${fieldId}"]`);
        if (fieldElement) {
            fieldElement.remove();
        }
        
        // Remove from fields array
        this.fields = this.fields.filter(f => f.id !== fieldId);
        
        // Update order and reorganize
        this.updateFieldOrder();
        this.reorganizeFormLayout();
        
        // Validate row widths after deletion
        this.validateRowWidths();
    }

    showFieldConfigModal(fieldData) {
        // Create or update field configuration modal
        let modal = document.getElementById('fieldConfigModal');
        if (!modal) {
            modal = this.createFieldConfigModal();
            document.body.appendChild(modal);
        }
        
        // Populate modal with field data
        this.populateFieldConfigModal(modal, fieldData);
        
        // Show modal
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
    }

    createFieldConfigModal() {
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.id = 'fieldConfigModal';
        modal.innerHTML = `
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Configure Field</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="fieldConfigForm">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Field Name</label>
                                        <input type="text" class="form-control" name="field_name" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Field Label</label>
                                        <input type="text" class="form-control" name="field_label" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Field Type</label>
                                        <select class="form-select" name="field_type" required>
                                            <option value="input">Text Input</option>
                                            <option value="textarea">Text Area</option>
                                            <option value="dropdown">Dropdown</option>
                                            <option value="datepicker">Date Picker</option>
                                            <option value="yesno">Yes/No</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Field Role</label>
                                        <select class="form-select" name="field_role">
                                            <option value="both">Both (Requestor & Service Staff)</option>
                                            <option value="requestor">Requestor Only</option>
                                            <option value="service_staff">Service Staff Only</option>
                                            <option value="readonly">Read-only After Submission</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Width (1-12)</label>
                                        <select class="form-select" name="width">
                                            <option value="3">3 (Quarter)</option>
                                            <option value="4">4 (Third)</option>
                                            <option value="6" selected>6 (Half)</option>
                                            <option value="8">8 (Two-thirds)</option>
                                            <option value="9">9 (Three-quarters)</option>
                                            <option value="12">12 (Full)</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3 form-check pt-4">
                                        <input type="checkbox" class="form-check-input" name="required">
                                        <label class="form-check-label">Required Field</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3 form-check pt-4">
                                        <input type="checkbox" class="form-check-input" name="bump_next_field">
                                        <label class="form-check-label">Align Next Field</label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row dropdown-options" style="display: none;">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Code Table</label>
                                        <input type="text" class="form-control" name="code_table" placeholder="e.g., departments">
                                        <small class="text-muted">Table name for dropdown options</small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row length-options" style="display: none;">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Max Length</label>
                                        <input type="number" class="form-control" name="length">
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" id="saveFieldConfig">Save Field</button>
                    </div>
                </div>
            </div>
        `;
        
        // Setup save functionality
        modal.querySelector('#saveFieldConfig').addEventListener('click', () => {
            this.saveFieldConfig();
        });
        
        // Setup field type change listener
        modal.querySelector('[name="field_type"]').addEventListener('change', (e) => {
            this.toggleFieldTypeOptions(e.target.value);
        });
        
        return modal;
    }

    populateFieldConfigModal(modal, fieldData) {
        const form = modal.querySelector('#fieldConfigForm');
        
        // Set current field ID
        form.dataset.fieldId = fieldData.id;
        
        // Populate form fields
        Object.keys(fieldData).forEach(key => {
            const input = form.querySelector(`[name="${key}"]`);
            if (input) {
                if (input.type === 'checkbox') {
                    input.checked = fieldData[key];
                } else {
                    input.value = fieldData[key];
                }
            }
        });
        
        // Show/hide type-specific options
        this.toggleFieldTypeOptions(fieldData.field_type);
    }

    toggleFieldTypeOptions(fieldType) {
        const modal = document.getElementById('fieldConfigModal');
        const dropdownOptions = modal.querySelector('.dropdown-options');
        const lengthOptions = modal.querySelector('.length-options');
        
        // Hide all options first
        dropdownOptions.style.display = 'none';
        lengthOptions.style.display = 'none';
        
        // Show relevant options
        if (fieldType === 'dropdown') {
            dropdownOptions.style.display = 'block';
        }
        
        if (['input', 'textarea'].includes(fieldType)) {
            lengthOptions.style.display = 'block';
        }
    }

    saveFieldConfig() {
        const modal = document.getElementById('fieldConfigModal');
        const form = modal.querySelector('#fieldConfigForm');
        const fieldId = form.dataset.fieldId;
        
        // Get form data
        const formData = new FormData(form);
        const fieldData = {};
        
        for (let [key, value] of formData.entries()) {
            if (form.querySelector(`[name="${key}"]`).type === 'checkbox') {
                fieldData[key] = form.querySelector(`[name="${key}"]`).checked;
            } else {
                fieldData[key] = value;
            }
        }
        
        fieldData.id = fieldId;
        
        // Update field in array
        const fieldIndex = this.fields.findIndex(f => f.id === fieldId);
        if (fieldIndex >= 0) {
            this.fields[fieldIndex] = { ...this.fields[fieldIndex], ...fieldData };
        }
        
        // Update field element in DOM
        this.updateFieldElement(fieldId, fieldData);
        
        // Update live preview
        this.updateLiveFormPreview();
        
        // Hide modal
        const bsModal = bootstrap.Modal.getInstance(modal);
        bsModal.hide();
    }

    updateFieldElement(fieldId, fieldData) {
        const fieldElement = document.querySelector(`[data-field-id="${fieldId}"]`);
        if (!fieldElement) return;
        
        // Update the mini panel type and width labels
        const typeLabel = fieldElement.querySelector('.field-type-label');
        const widthLabel = fieldElement.querySelector('.field-width-label');
        if (typeLabel) typeLabel.textContent = fieldData.field_type;
        if (widthLabel) widthLabel.textContent = `W: ${fieldData.width}/12`;
        
        // Update the field element's column class
        const colClass = this.getBootstrapColumnClass(fieldData.width);
        fieldElement.className = `${colClass} field-item-container`;
        
        // Update preview content
        const preview = fieldElement.querySelector('.form-field-preview');
        if (preview) {
            preview.innerHTML = this.generateFieldPreview(fieldData);
        }
    }

    updateFieldOrder() {
        const fieldElements = document.querySelectorAll('.form-field-wrapper');
        fieldElements.forEach((element, index) => {
            const fieldId = element.dataset.fieldId;
            const field = this.fields.find(f => f.id === fieldId);
            if (field) {
                field.field_order = index + 1;
                
                // Update visual order indicator
                const info = element.querySelector('.field-info small');
                if (info) {
                    const currentText = info.innerHTML;
                    info.innerHTML = currentText.replace(/Order: \d+/, `Order: ${index + 1}`);
                }
            }
        });
        
        // Update live form preview if it exists
        this.updateLiveFormPreview();
    }

    updateLiveFormPreview() {
        // No longer needed - the drop area IS the preview
        // Form layout is handled by reorganizeFormLayout()
    }

    loadExistingFields() {
        const existingFields = window.panelFields || [];
        this.fields = existingFields.map(field => ({
            ...field,
            id: field.id || 'field_' + Date.now() + '_' + Math.random(),
            width: field.width || 12, // Ensure width is set
            type: field.type || field.field_type, // Normalize field type
            label: field.label || field.field_label, // Normalize field label
            name: field.name || field.field_name // Normalize field name
        }));
        
        // Reorganize the form layout
        this.reorganizeFormLayout();
    }

    showDeleteModal(fieldId) {
        // Deprecated: no confirmation, call deleteField directly
        this.deleteField(fieldId);
    }

    deleteField(fieldId) {
        // Remove from fields array
        this.fields = this.fields.filter(f => f.id !== fieldId);
        // Reorganize layout
        this.reorganizeFormLayout();
    }

    updateFieldElement(fieldId, fieldData) {
        // Find and update the field in the array
        const fieldIndex = this.fields.findIndex(f => f.id === fieldId);
        if (fieldIndex >= 0) {
            this.fields[fieldIndex] = { ...this.fields[fieldIndex], ...fieldData };
        }
        
        // Reorganize the entire layout to reflect changes
        this.reorganizeFormLayout();
    }

    saveForm() {
        if (this.fields.length === 0) {
            alert('Please add at least one field to the form.');
            return;
        }
        
        // Ensure all fields have required name/label properties
        const safeFields = this.fields.map(f => {
            const field = { ...f };
            // Ensure both field_name and name
            if (!field.field_name && field.name) field.field_name = field.name;
            if (!field.name && field.field_name) field.name = field.field_name;
            if (!field.field_name && !field.name) field.field_name = field.name = field.id || 'field_' + Date.now();
            // Ensure both field_label and label
            if (!field.field_label && field.label) field.field_label = field.label;
            if (!field.label && field.field_label) field.label = field.field_label;
            if (!field.field_label && !field.label) field.field_label = field.label = 'Field';
            return field;
        });
        const formData = {
            panel_name: this.panelName,
            fields: safeFields
        };
        
        // Show loading state
        const saveBtn = document.getElementById('saveFormBuilder');
        const originalText = saveBtn.innerHTML;
        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        saveBtn.disabled = true;
        
        // Submit via AJAX
        fetch(window.baseUrl + 'admin/dynamicforms/save-form-builder', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(formData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                const alert = document.createElement('div');
                alert.className = 'alert alert-success alert-dismissible fade show';
                alert.innerHTML = `
                    <i class="fas fa-check-circle"></i> ${data.message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                document.querySelector('.form-builder-toolbar').after(alert);
                // Auto-dismiss after 3 seconds
                setTimeout(() => {
                    alert.remove();
                }, 3000);
                // Do NOT redirect, remain on builder
            } else {
                alert('Error saving form: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error saving form. Please try again.');
        })
        .finally(() => {
            // Reset button state
            saveBtn.innerHTML = originalText;
            saveBtn.disabled = false;
        });
    }

    createFieldFromDrag(fieldType, dropPosition) {
        // Get configuration from the side panel
        const fieldData = this.getFieldConfigFromPanel(fieldType);
        
        // Add the field to the form
        this.addFieldWithData(fieldData, dropPosition);
    }

    addFieldWithData(fieldData, dropY = null) {
        // Add to fields array
        this.fields.push(fieldData);
        
        // Create and insert the field element
        const fieldElement = this.createFieldElement(fieldData);
        this.insertFieldAtPosition(fieldElement, dropY);
        
        // Update the form layout
        this.reorganizeFormLayout();
        
        // Hide empty state if this is the first field
        this.updateEmptyState();
    }

    insertFieldAtPosition(fieldElement, dropY = null) {
        const formBuilder = document.querySelector('.form-builder-area');
        if (!formBuilder) {
            console.error('Form builder area not found');
            return;
        }

        if (dropY === null || this.fields.length === 0) {
            // Just append to the end or if it's the first field
            const lastRow = formBuilder.querySelector('.row:last-child');
            if (lastRow) {
                lastRow.appendChild(fieldElement);
            } else {
                // Create first row if none exists
                const newRow = document.createElement('div');
                newRow.className = 'row';
                newRow.appendChild(fieldElement);
                formBuilder.appendChild(newRow);
            }
        } else {
            // Try to insert at the position based on dropY
            const insertPosition = this.getInsertPosition(dropY);
            if (insertPosition && insertPosition.parentNode) {
                insertPosition.parentNode.insertBefore(fieldElement, insertPosition.nextSibling);
            } else {
                // Fallback: append to last row
                const lastRow = formBuilder.querySelector('.row:last-child');
                if (lastRow) {
                    lastRow.appendChild(fieldElement);
                }
            }
        }
    }

    updateEmptyState() {
        const formBuilderArea = document.querySelector('.form-builder-area');
        const dropZone = document.getElementById('formBuilderDropZone');
        
        if (!formBuilderArea || !dropZone) {
            console.error('Form builder area or drop zone not found in updateEmptyState');
            return;
        }

        if (this.fields.length === 0) {
            dropZone.classList.add('empty');
            // Only add empty state if it doesn't exist
            if (!dropZone.querySelector('.empty-state')) {
                dropZone.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-plus-circle"></i>
                        <h5>Start Building Your Form</h5>
                        <p>Drag field types from the left panel to add them to your form</p>
                    </div>
                `;
            }
        } else {
            dropZone.classList.remove('empty');
            // Only remove empty state if it exists
            const emptyState = dropZone.querySelector('.empty-state');
            if (emptyState) {
                emptyState.remove();
            }
        }
    }

    getFieldConfigFromPanel(fieldType) {
        // Create field data from config panel values
        const fieldData = {
            id: this.generateFieldId(),
            type: document.getElementById('fieldType').value || fieldType,
            label: document.getElementById('fieldLabel').value || this.getDefaultLabel(fieldType),
            name: document.getElementById('fieldName').value || this.generateFieldName(fieldType),
            width: parseInt(document.getElementById('fieldWidth').value) || 12,
            required: document.getElementById('fieldRequired').checked || false,
            bump_next_field: document.getElementById('fieldBumpNext').checked || false
        };

        // Handle options for dropdown fields
        if (fieldData.type === 'dropdown') {
            const optionsText = document.getElementById('fieldOptions').value;
            if (optionsText.trim()) {
                fieldData.options = optionsText.split('\n').filter(option => option.trim() !== '');
            } else {
                fieldData.options = ['Option 1', 'Option 2', 'Option 3'];
            }
        }

        return fieldData;
    }

    getDefaultLabel(fieldType) {
        const labels = {
            input: 'Text Input',
            textarea: 'Text Area',
            dropdown: 'Dropdown',
            datepicker: 'Date Picker',
            yesno: 'Yes/No'
        };
        return labels[fieldType] || 'Field Label';
    }

    generateFieldId() {
        const timestamp = Date.now();
        const random = Math.floor(Math.random() * 1000);
        return `field_${timestamp}_${random}`;
    }

    generateFieldName(fieldType) {
        const timestamp = Date.now();
        return `${fieldType}_${timestamp}`;
    }

    getInsertPosition(dropY) {
        const fieldElements = [...document.querySelectorAll('.field-item-container')];
        let insertAfter = null;
        
        // If no existing fields, return null
        if (fieldElements.length === 0) {
            return null;
        }
        
        for (let element of fieldElements) {
            const rect = element.getBoundingClientRect();
            const middle = rect.top + rect.height / 2;
            
            if (dropY > middle) {
                insertAfter = element;
            } else {
                break;
            }
        }
        
        return insertAfter;
    }

    capitalize(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    if (document.querySelector('.form-builder-container')) {
        // Add a small delay to ensure all elements are rendered
        setTimeout(() => {
            window.formBuilder = new FormBuilder();
        }, 100);
    }
});
