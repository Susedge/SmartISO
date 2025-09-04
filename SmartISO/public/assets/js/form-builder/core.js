// Core FormBuilder class extracted from monolith
// NOTE: This is an initial extraction; only essential constructor + init + loadExistingFields for incremental modularization.
import { notify } from './notify.js';

export class FormBuilder {
  constructor() {
    this.panelName = window.panelName || '';
    this.fields = [];
    this.draggedElement = null;
    this.sortableInstances = [];
    this._placeholderEl = null;
    this._placeholderRaf = null;
    this._lastPlaceholderY = null;
  }

  init() {
    const formBuilderContainer = document.querySelector('.form-builder-container');
    const dropZone = document.getElementById('formBuilderDropZone');
    if (!formBuilderContainer || !dropZone) {
      console.error('Required panels elements not found');
      return;
    }
    this.loadExistingFields();
  }

  loadExistingFields() {
    let existingFields = window.panelFields || [];
    existingFields = existingFields.filter(f => f && (f.field_type || f.type));
    let normalized = existingFields.map(field => {
      const rawLabel = field.label || field.field_label || 'Field';
      const cleanLabel = rawLabel.replace(/\s+/g,' ').trim();
      const baseName = field.name || field.field_name || cleanLabel;
      const cleanName = baseName.toLowerCase().replace(/\s+/g,'_').replace(/[^a-z0-9_]+/g,'').replace(/^_+|_+$/g,'');
      return { ...field, id: field.id || 'field_' + Date.now() + '_' + Math.random(), width: field.width || 12, type: field.type || field.field_type, label: cleanLabel, field_label: cleanLabel, name: cleanName, field_name: cleanName };
    });
    const seenIds = new Set(); const seenNames = new Set();
    normalized = normalized.filter(f => { if (seenIds.has(f.id)) return false; if (f.name && seenNames.has(f.name)) return false; seenIds.add(f.id); if (f.name) seenNames.add(f.name); return true; });
    const comboSeen = new Set();
    normalized = normalized.filter(f => { const type = f.type || f.field_type || ''; const nk = (f.name||'')+'::'+type; const lk = (f.label||'')+'::'+type; if (comboSeen.has(nk) || comboSeen.has(lk)) return false; comboSeen.add(nk); comboSeen.add(lk); return true; });
    normalized.forEach((f,i)=> f.field_order = i+1);
    this.fields = normalized;
  }

  reorganizeFormLayout() {
    // Placeholder: in legacy script this arranges DOM columns/rows.
    // During incremental modularization we just re-render a minimalist list if drop zone present.
    const dropZone = document.getElementById('formBuilderDropZone');
    if (!dropZone) return;
    // Simple render: clear and list field labels (development placeholder)
    dropZone.innerHTML = '';
    if (!this.fields.length) {
      const empty = document.createElement('div');
      empty.className = 'text-muted py-4 text-center';
      empty.textContent = 'No fields. Use the palette to add some.';
      dropZone.appendChild(empty);
      return;
    }
    this.fields.forEach(f => {
      const div = document.createElement('div');
      div.className = 'border rounded p-2 mb-2 bg-light';
      div.textContent = f.label + ' (' + (f.type||'') + ')';
      dropZone.appendChild(div);
    });
  }

  updateEmptyState() {
    // For now rely on reorganizeFormLayout placeholder output.
    // Could toggle a dedicated empty state element later.
  }
}

// Backward compatibility for scripts expecting window.FormBuilder after original monolith
if (!window.FormBuilder) window.FormBuilder = FormBuilder;
