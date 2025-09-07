// Modular Edit Configuration Page Script
// Handles: filtering, bulk select, AJAX saves for department (metadata & assignments), office/form assignments
(function(global){
  class ConfigEdit {
    constructor(opts){
      this.type = opts.type; // departments|offices|forms
      this.id = opts.id;
      this.csrf = opts.csrf || {}; // { name, hash }
      this.endpoints = opts.endpoints || {}; // ajaxDepartment, ajaxOffice
      this.init();
    }
    init(){
      this.bindFilters();
      this.bindBulkSelect();
      this.bindMetaForm();
      this.bindAssignments();
    }
    qs(sel){ return document.querySelector(sel); }
    qsa(sel){ return Array.from(document.querySelectorAll(sel)); }
    bindFilters(){
      const officeFilter = this.qs('#filterOffices');
      const officesList = this.qs('#officesList');
      const formsFilter = this.qs('#filterForms');
      const formsList = this.qs('#formsList');
      function attach(inp, container){ if(!inp||!container) return; inp.addEventListener('input',()=>{ const term = inp.value.trim().toLowerCase(); container.querySelectorAll('.form-check').forEach(fc=>{ fc.style.display = !term || fc.textContent.toLowerCase().includes(term) ? '' : 'none'; }); }); }
      attach(officeFilter, officesList); attach(formsFilter, formsList);
    }
    bindBulkSelect(){
      const map = [
        { all:'#selectAllOffices', none:'#clearAllOffices', scope:'#officesList' },
        { all:'#selectAllForms', none:'#clearAllForms', scope:'#formsList' }
      ];
      map.forEach(cfg=>{
        if(cfg.scope){
          const allBtn = this.qs(cfg.all); const noneBtn = this.qs(cfg.none); const box = this.qs(cfg.scope);
          allBtn && allBtn.addEventListener('click', ()=>{ box && box.querySelectorAll('input[type="checkbox"]').forEach(cb=> cb.checked=true); });
          noneBtn && noneBtn.addEventListener('click', ()=>{ box && box.querySelectorAll('input[type="checkbox"]').forEach(cb=> cb.checked=false); });
        }
      });
    }
    bindMetaForm(){
      const form = this.qs('#deptMetaForm');
      if(!form) return;
      // AJAX metadata for all types (departments, offices, forms)
      const status = this.qs('#metaFormStatus');
      const btn = form.querySelector('button[type="submit"]');
      const original = btn ? btn.innerHTML : '';
      form.addEventListener('submit', (e)=>{
        e.preventDefault();
        if(btn){ btn.disabled=true; btn.innerHTML='<span class="spinner-border spinner-border-sm me-1"></span>Saving'; }
        status.innerHTML='';
        const fd = new FormData(form);
        let url = this.endpoints.ajaxDepartment;
        if(this.type==='offices') url = this.endpoints.ajaxOffice;
        if(this.type==='forms') url = this.endpoints.ajaxForm;
        fetch(url, { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest'}, body: fd })
          .then(r=>r.json())
          .then(data=>{
            if(data.csrf){ this.refreshCsrf(data.csrf); }
            if(data.success){
              status.innerHTML='';
              // Update form fields with returned data (avoid reload so description-only edits persist visually)
              if(data.data){
                if(data.data.code && form.querySelector('input[name="code"]')){ form.querySelector('input[name="code"]').value = data.data.code; }
                if(data.data.description && form.querySelector('input[name="description"], textarea[name="description"]')){
                  const descEl = form.querySelector('input[name="description"], textarea[name="description"]');
                  if(descEl.value !== data.data.description){ descEl.value = data.data.description; }
                }
                if(typeof data.has_template !== 'undefined'){
                  const badge = document.querySelector('[data-template-indicator]');
                  if(badge){ badge.classList.remove('bg-secondary','bg-success'); badge.classList.add(data.has_template ? 'bg-success':'bg-secondary'); badge.textContent = data.has_template ? 'Template Present' : 'No Template'; }
                }
              }
              SimpleModal.alert(data.message||'Saved','Success','success');
            } else { status.innerHTML=''; SimpleModal.alert(data.message||'Save failed','Warning','warning'); }
          })
          .catch(()=> { status.innerHTML=''; SimpleModal.alert('Request failed','Error','error'); })
          .finally(()=>{ if(btn){ btn.disabled=false; btn.innerHTML=original; } });
      });
    }
    bindAssignments(){
      // Department office assignments (handled in same page via AJAX in original); keep full form submit to reuse controller pivot logic
      const assignForm = this.qs('#assignmentsForm');
      if(assignForm){
        const status = this.qs('#assignFormStatus');
        const btn = assignForm.querySelector('button[type="submit"]');
        const original = btn? btn.innerHTML: '';
        assignForm.addEventListener('submit', (e)=>{
          e.preventDefault();
          if(btn){ btn.disabled=true; btn.innerHTML='<span class="spinner-border spinner-border-sm me-1"></span>Saving'; }
          status.innerHTML='';
          const fd = new FormData(assignForm);
          fetch(this.endpoints.ajaxDepartment, { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest'}, body: fd })
            .then(r=>r.json())
            .then(data=>{ if(data.csrf){ this.refreshCsrf(data.csrf); } if(data.success){ status.innerHTML=''; SimpleModal.alert(data.message||'Saved','Success','success'); } else { status.innerHTML=''; SimpleModal.alert(data.message||'Save failed','Warning','warning'); } })
            .catch(()=> { status.innerHTML=''; SimpleModal.alert('Request failed','Error','error'); })
            .finally(()=>{ if(btn){ btn.disabled=false; btn.innerHTML=original; } });
        });
      }
      // Office -> forms assignments use existing ajaxSaveOffice endpoint already in inline code earlier; unify here if present
      const formAssign = this.qs('#formAssignmentsForm');
      if(formAssign){
        const status = this.qs('#formAssignStatus');
        const btn = formAssign.querySelector('button[type="submit"]');
        const original = btn? btn.innerHTML: '';
        formAssign.addEventListener('submit', (e)=>{
          e.preventDefault();
            if(btn){ btn.disabled=true; btn.innerHTML='<span class="spinner-border spinner-border-sm me-1"></span>Saving'; }
            status.innerHTML='';
            const fd = new FormData(formAssign);
            fetch(this.endpoints.ajaxOffice, { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest'}, body: fd })
              .then(r=>r.json())
              .then(data=>{ if(data.csrf){ this.refreshCsrf(data.csrf); } if(data.success){ status.innerHTML=''; SimpleModal.alert(data.message||'Saved','Success','success'); } else { status.innerHTML=''; SimpleModal.alert(data.message||'Save failed','Warning','warning'); } })
              .catch(()=> { status.innerHTML=''; SimpleModal.alert('Request failed','Error','error'); })
              .finally(()=>{ if(btn){ btn.disabled=false; btn.innerHTML=original; } });
        });
      }
    }
      refreshCsrf(csrf){
        if(!csrf || !csrf.hash) return; // CodeIgniter auto-rotates hash per request when enabled
        this.csrf = csrf;
        // Update any existing hidden input with current token
        document.querySelectorAll('input[name="'+csrf.name+'"], input[name="csrf_test_name"]').forEach(inp=>{ inp.value = csrf.hash; });
      }
  }
  global.ConfigEdit = ConfigEdit;
  document.addEventListener('DOMContentLoaded', function(){
    if(global.CONFIG_EDIT_DATA){
      new ConfigEdit(global.CONFIG_EDIT_DATA);
    }
  });
})(window);
