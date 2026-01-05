(function(global){
    function AdminConfigurations(type){
        this.type = type;
        this.selectedRow = null;
        this.csrfTokenName = global.csrfTokenName || 'csrf_test_name';
        this.csrfToken = global.csrfToken || '';
        this.table = document.querySelector('table[data-type]');
        this.init();
    }

    AdminConfigurations.prototype.init = function(){
        this.initDataTable();
        this.bindRowSelection();
        this.bindActionPanelButtons();
        this.bindGlobalClickClear();
    this._ensureSelectionStyle();
    this.autoSelectFirst();
        // ensure action buttons reflect current data state (disable edit/delete when no rows)
        try{ this.updateActionButtonsState(); }catch(e){}
        global.adminConfigInstance = this; // expose if needed elsewhere
    };

    // Disable or enable action buttons based on whether the table has any real data rows
    AdminConfigurations.prototype.updateActionButtonsState = function(){
        var self = this;
        if(!this.table) return;
        // consider rows with a data-id attribute as 'real' selectable rows
        var hasRows = !!this.table.querySelector('tbody tr[data-id]');

        function setDisabled(el, disable){
            if(!el) return;
            var tag = (el.tagName || '').toLowerCase();
            if(tag === 'a'){
                if(disable){ el.classList.add('disabled'); el.setAttribute('aria-disabled','true'); el.setAttribute('tabindex','-1'); }
                else { el.classList.remove('disabled'); el.removeAttribute('aria-disabled'); el.removeAttribute('tabindex'); }
            } else {
                try{ el.disabled = !!disable; }catch(e){}
            }
        }

        if(this.type === 'panels'){
            var panelBtns = document.querySelectorAll('#panelSelectionActions a,#panelSelectionActions button');
            Array.prototype.forEach.call(panelBtns, function(b){ setDisabled(b, !hasRows); });
            // Add Panel button should remain enabled
            setDisabled(document.getElementById('btnAddPanelModal'), false);
        } else {
            var selBtns = document.querySelectorAll('#selectionActions a,#selectionActions button');
            Array.prototype.forEach.call(selBtns, function(b){ setDisabled(b, !hasRows); });
            // template group buttons (forms) should be disabled when there's no rows
            var tmplGroupBtns = document.querySelectorAll('#templateGroup a,#templateGroup button');
            Array.prototype.forEach.call(tmplGroupBtns, function(b){ setDisabled(b, !hasRows); });
            // Ensure Add (outside selectionActions) remains enabled
            setDisabled(document.getElementById('btnAdd'), false);
        }

        // If no rows, clear selection so UI doesn't show a selected placeholder
        if(!hasRows) this.clearSelection();
    };

    AdminConfigurations.prototype._ensureSelectionStyle = function(){
        if(document.getElementById('admin-config-selection-style')) return;
        var css = 'table[data-type] tbody tr.admin-selected{outline:3px solid rgba(13,110,253,.12);box-shadow:inset 0 0 0 1px rgba(13,110,253,.06);background-color:rgba(13,110,253,.06) !important}table[data-type] tbody tr.admin-selected td{font-weight:600}';
        var st = document.createElement('style'); st.id = 'admin-config-selection-style'; st.textContent = css; document.head.appendChild(st);
    };

    AdminConfigurations.prototype.initDataTable = function(){
        if(!this.table || !global.jQuery || !jQuery.fn.dataTable) return;

        // Ensure the table has an id (DataTables requires a selector); generate one if missing
        if(!this.table.id){
            this.table.id = 'admin-config-' + (this.type || 'table') + '-' + Math.floor(Math.random()*100000);
        }

        // Defensive fix: make sure each tbody row has the same number of TDs as there are THs.
        // DataTables throws internal errors when rows have fewer cells than headers (
        // _DT_CellIndex assignment fails). If a row is short, append empty TDs so indexing is stable.
        try{
            var headerCount = (this.table.querySelectorAll && this.table.querySelectorAll('thead th').length) || 0;
            if(headerCount){
                var rows = this.table.querySelectorAll('tbody tr');
                Array.prototype.forEach.call(rows, function(r){
                    var tdCount = r.querySelectorAll('td').length || 0;
                    for(var i = tdCount; i < headerCount; i++){
                        var td = document.createElement('td');
                        // keep markup visually consistent when empty
                        td.innerHTML = '&nbsp;';
                        r.appendChild(td);
                    }
                });
            }
        }catch(e){ /* defensive: if DOM operations fail, continue to init and let DataTables handle it */ }

        this.dataTable = jQuery('#'+this.table.id).DataTable({
            paging:true,searching:true,lengthChange:false,pageLength:25,order:[],
            columnDefs:[{targets:0,visible:false,searchable:false}]
        });
    };

    AdminConfigurations.prototype.bindRowSelection = function(){
        if(!this.table) return;
        var self=this;
        this.table.addEventListener('click', function(e){
            var tr = e.target.closest('tbody tr');
            if(!tr) return;
            if(self.selectedRow === tr){
                self.clearSelection();
            } else {
                self.setSelection(tr);
            }
            // prevent the document click listener from clearing selection immediately after
            try{ e.stopPropagation(); }catch(err){}
        });
    };

    AdminConfigurations.prototype.setSelection = function(tr){
        this.clearSelection();
        this.selectedRow = tr;
        tr.classList.add('table-primary');
        tr.classList.add('admin-selected');
        this.toggleActions(true);
    };

    AdminConfigurations.prototype.clearSelection = function(){
        if(this.selectedRow){
            this.selectedRow.classList.remove('table-primary');
            this.selectedRow.classList.remove('admin-selected');
        }
        this.selectedRow = null;
        this.toggleActions(false);
    };

    AdminConfigurations.prototype.autoSelectFirst = function(){
        if(this.selectedRow) return; // already selected
        if(!this.table) return;
        // pick the first row that has a data-id attribute; skip placeholder rows which often
        // contain a single td with colspan
        var rows = this.table.querySelectorAll('tbody tr');
        var found = null;
        Array.prototype.forEach.call(rows, function(r){
            if(found) return;
            if(r.getAttribute && r.getAttribute('data-id')){ found = r; }
        });
        if(found){ this.setSelection(found); }
        else { this.clearSelection(); }
    };


    AdminConfigurations.prototype.currentId = function(){
    if(!this.selectedRow) return null;
    var did = this.selectedRow.getAttribute('data-id');
    if(did) return did;
    // fallback to first cell if data-id missing
    var firstCell = this.selectedRow.querySelector('td');
    return firstCell? firstCell.textContent.trim(): null;
    };
    AdminConfigurations.prototype.currentCode = function(){
        if(!this.selectedRow) return null;
        return this.selectedRow.getAttribute('data-code') || '';
    };

    AdminConfigurations.prototype.toggleActions = function(show){
        if(this.type==='panels'){
            var wrap=document.getElementById('panelSelectionActions');
            if(wrap) wrap.style.display = show? 'block':'none';
        } else {
            var sa=document.getElementById('selectionActions');
            if(sa) sa.style.display = show? 'block':'none';
            if(this.type==='forms'){
                var tg=document.getElementById('templateGroup');
                if(tg) tg.style.display=show? 'block':'none';
            }
        }
    };

    // inline no-selection message removed; use SimpleModal.alert when selection cleared

    AdminConfigurations.prototype.bindGlobalClickClear = function(){
        var self=this;
        document.addEventListener('click', function(e){
            var tgt = e.target;
            // if click inside the table element, or inside the selection/action panels, or inside the modal overlay, ignore
            if(self.table && tgt.closest && tgt.closest('table[data-type]')) return;
            if(tgt.closest && (tgt.closest('#selectionActions') || tgt.closest('#panelSelectionActions') || tgt.closest('.config-actions-panel'))) return;
            if(tgt.closest && tgt.closest('#simpleModalOverlay')) return;
            // user clicked outside: clear selection
            self.clearSelection();
        });
    };

    AdminConfigurations.prototype.bindActionPanelButtons = function(){
        var self=this;
        if(this.type==='panels'){
            document.getElementById('btnAddPanelModal')?.addEventListener('click', function(e){ e.preventDefault(); self.createPanel(); });
            document.getElementById('btnPanelBuilder')?.addEventListener('click', function(e){ e.preventDefault(); if(!self.selectedRow) return; window.location = window.baseUrl + 'admin/dynamicforms/form-builder/'+ encodeURIComponent(self.currentCode()); });
            document.getElementById('btnPanelEditFields')?.addEventListener('click', function(e){ e.preventDefault(); if(!self.selectedRow) return; window.location = window.baseUrl + 'admin/dynamicforms/edit-panel/'+ encodeURIComponent(self.currentCode()); });
            document.getElementById('btnPanelEditInfo')?.addEventListener('click', function(e){ e.preventDefault(); if(!self.selectedRow) return; window.location = window.baseUrl + 'admin/dynamicforms/edit-panel-info/'+ encodeURIComponent(self.currentCode()); });
            document.getElementById('btnPanelCopy')?.addEventListener('click', function(e){ e.preventDefault(); self.copyPanel(); });
            document.getElementById('btnPanelDelete')?.addEventListener('click', function(e){ e.preventDefault(); self.deletePanel(); });
        } else {
            document.getElementById('btnEdit')?.addEventListener('click', function(e){ e.preventDefault(); if(!self.selectedRow) return; var id=self.currentId(); window.location= window.baseUrl + 'admin/configurations/edit/'+id+'?type='+self.type; });
            document.getElementById('btnDelete')?.addEventListener('click', function(e){ e.preventDefault(); self.deleteCurrent(); });
            if(this.type==='forms'){
                document.getElementById('btnSignatories')?.addEventListener('click', function(e){ e.preventDefault(); if(!self.selectedRow) return; var id=self.currentId(); window.location = window.baseUrl + 'admin/configurations/form-signatories/'+id; });
                document.getElementById('tmplUpload')?.addEventListener('click', function(e){ e.preventDefault(); self.openTemplateUpload(); });
                document.getElementById('tmplDownload')?.addEventListener('click', function(e){ e.preventDefault(); self.downloadTemplate(); });
                document.getElementById('tmplDelete')?.addEventListener('click', function(e){ e.preventDefault(); self.deleteTemplate(); });
            }
        }
    };

    // CRUD actions (configs)
    AdminConfigurations.prototype.deleteCurrent = function(){
        if(!this.selectedRow) return; var id=this.currentId(); var type=this.type;
        var single = type.slice(0,-1);
        var self=this;
        SimpleModal.confirm('Delete this '+single+'? This cannot be undone.','Confirm Delete','warning').then(function(ok){ if(!ok) return; self.performDelete(type,id,self.selectedRow); });
    };

    AdminConfigurations.prototype.performDelete = function(type,id,rowEl){
        var self=this;
    fetch(window.baseUrl + 'admin/configurations/delete/'+id+'?type='+type+'&ajax=1', { method:'GET', headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'} })
        .then(function(response){
            var contentType = (response.headers.get('content-type')||'').toLowerCase();
            if(contentType.indexOf('application/json') !== -1){
                return response.json();
            }
            // Non-JSON response: if successful status, treat as success; attempt to parse JSON from text otherwise fallback
            return response.text().then(function(text){
                if(response.ok){
                    try{ return JSON.parse(text); }catch(e){ return { success: true, message: 'Deleted.' }; }
                }
                // non-ok and non-json: bubble error
                throw new Error(text || 'Request failed');
            });
        })
        .then(function(j){
            if(j && j.csrfToken) self.csrfToken=j.csrfToken;
            if(j && j.success){
                try{ rowEl.remove(); }catch(e){}
                self.clearSelection();
                try{ self.updateActionButtonsState(); }catch(e){}
                SimpleModal.alert((j && j.message) || 'Deleted.','Success','success');
                return;
            }
            var msg = (j && j.message) || 'Unable to delete.';
            if(j && j.dependencies && j.dependencies.length){
                msg += '<div class="mt-2"><strong>Dependencies:</strong><ul class="mb-0 small">'+ j.dependencies.map(d=>'<li>'+self.escapeHtml(d)+'</li>').join('') + '</ul></div>';
            }
            SimpleModal.alert(msg,'Delete Blocked','warning');
        })
        .catch(function(err){
            // Use error message if available
            var msg = (err && err.message) ? err.message : 'Request failed.';
            SimpleModal.alert(msg,'Error','error');
        });
    };

    // Templates (forms)
    AdminConfigurations.prototype.openTemplateUpload = function(){
        if(!this.selectedRow) return; var id=this.currentId(); var self=this;
        SimpleModal.show({title:'Upload / Replace Template',variant:'info',message:'<div class="mb-2 small text-muted">Upload a DOCX template for this form.</div><input type="file" id="sm_tmpl_file" accept="application/vnd.openxmlformats-officedocument.wordprocessingml.document" class="form-control form-control-sm">',buttons:[{text:'Cancel',value:'x'},{text:'Upload',value:'u',primary:true}]}).then(function(v){
            if(v==='u'){
                var inp=document.getElementById('sm_tmpl_file'); if(!inp.files.length){ SimpleModal.alert('File required.','Validation','warning'); return; }
                var fd=new FormData(); fd.append('template', inp.files[0]); fd.append(self.csrfTokenName, self.csrfToken);
                fetch(window.baseUrl + 'admin/configurations/upload-template/'+id,{method:'POST',body:fd})
                    .then(r=>r.json()).then(j=>{ if(j.csrfToken) self.csrfToken=j.csrfToken; if(j.success){ SimpleModal.alert('Template uploaded.','Success','success'); } else SimpleModal.alert(j.message||'Upload failed.','Error','error'); })
                    .catch(()=> SimpleModal.alert('Upload failed.','Error','error'));
            }
        });
    };
    AdminConfigurations.prototype.downloadTemplate = function(){ if(!this.selectedRow) return; var id=this.currentId(); window.location = window.baseUrl + 'admin/configurations/download-template/'+id; };
    AdminConfigurations.prototype.deleteTemplate = function(){ if(!this.selectedRow) return; var id=this.currentId(); var self=this; SimpleModal.confirm('Delete the template file?','Delete Template','warning').then(function(ok){ if(!ok) return;
            // Read fresh CSRF from meta tags
            var csrfNameMeta = document.querySelector('meta[name="csrf-name"]');
            var csrfHashMeta = document.querySelector('meta[name="csrf-hash"]');
            var csrfName = (csrfNameMeta && csrfNameMeta.getAttribute('content')) || self.csrfTokenName || 'csrf_test_name';
            var csrfHash = (csrfHashMeta && csrfHashMeta.getAttribute('content')) || self.csrfToken || '';
            var params = new URLSearchParams(); params.append(csrfName, csrfHash);
            fetch(window.baseUrl + 'admin/configurations/delete-template/'+id,{method:'POST',headers:{'X-Requested-With':'XMLHttpRequest','Content-Type':'application/x-www-form-urlencoded'},body: params.toString()}).then(r=>r.json()).then(j=>{ if(j.csrfToken) self.csrfToken=j.csrfToken; if(j.success){ SimpleModal.alert(j.message||'Template deleted.','Success','success'); } else SimpleModal.alert(j.message||'Delete failed.','Error','error'); }).catch(()=> SimpleModal.alert('Request failed.','Error','error'));
        }); };

    // Panels
    AdminConfigurations.prototype.createPanel = function(){
        var self=this;
        
        var formHtml = `
            <div class="mb-3">
                <label class="form-label">Panel Name <span class="text-danger">*</span></label>
                <input type="text" id="sm_new_panel_name" class="form-control form-control-sm" placeholder="Enter panel name" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Form <small class="text-muted">(Optional - assign later from Forms tab)</small></label>
                <select id="sm_panel_form_id" class="form-select form-select-sm">
                    <option value="">-- No Form Assignment --</option>
                </select>
                <small class="text-muted">Select a form to associate this panel with</small>
            </div>
            <div class="mb-3">
                <label class="form-label">Department (Optional)</label>
                <select id="sm_panel_department" class="form-select form-select-sm">
                    <option value="">-- Select Department --</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Office (Optional)</label>
                <select id="sm_panel_office" class="form-select form-select-sm">
                    <option value="">-- Select Office --</option>
                </select>
            </div>
        `;
        
        SimpleModal.show({
            title:'Create Panel',
            variant:'info',
            message:formHtml,
            buttons:[
                {text:'Cancel',value:'x'},
                {text:'Create',value:'c',primary:true}
            ]
        }).then(function(v){ 
            if(v==='c'){ 
                var name=(document.getElementById('sm_new_panel_name').value||'').trim(); 
                var formId=(document.getElementById('sm_panel_form_id').value||'');
                var deptId=(document.getElementById('sm_panel_department').value||'');
                var officeId=(document.getElementById('sm_panel_office').value||'');
                
                if(!name){ 
                    SimpleModal.alert('Panel name required.','Validation','warning'); 
                    return; 
                }
                
                console.log('Creating panel with data:', {panel_name: name, form_id: formId, department_id: deptId, office_id: officeId});
                
                // Use AJAX instead of form submission for better error handling
                self.createPanelAjax(name, formId, deptId, officeId); 
            }
        });
        
        // Load departments, offices, and forms after modal is shown
        setTimeout(function(){
            document.getElementById('sm_new_panel_name')?.focus();
            self.loadPanelDepartments();
            self.loadPanelOffices();
            self.loadFormsForPanelModal();
        },60);
    };
    
    AdminConfigurations.prototype.loadFormsForPanelModal = function(){
        fetch(window.baseUrl + 'admin/configurations/get-forms')
            .then(r => r.json())
            .then(data => {
                var select = document.getElementById('sm_panel_form_id');
                if(!select || !data.forms) return;
                
                data.forms.forEach(function(form){
                    var opt = document.createElement('option');
                    opt.value = form.id;
                    opt.textContent = form.code + ' - ' + form.description;
                    select.appendChild(opt);
                });
            })
            .catch(function(err){
                console.error('Error loading forms:', err);
            });
    };
    
    AdminConfigurations.prototype.createPanelAjax = function(panelName, formId, deptId, officeId){
        var self = this;
        
        // Get fresh CSRF tokens
        var csrfNameMeta = document.querySelector('meta[name="csrf-name"]');
        var csrfHashMeta = document.querySelector('meta[name="csrf-hash"]');
        var csrfName = (csrfNameMeta && csrfNameMeta.getAttribute('content')) || 'csrf_test_name';
        var csrfHash = (csrfHashMeta && csrfHashMeta.getAttribute('content')) || '';
        
        var formData = new FormData();
        formData.append(csrfName, csrfHash);
        formData.append('panel_name', panelName);
        if(formId) formData.append('form_id', formId);
        if(deptId) formData.append('department_id', deptId);
        if(officeId) formData.append('office_id', officeId);
        
        // Show loading state
        if(window.SimpleModal && window.SimpleModal.showLoading) {
            window.SimpleModal.showLoading('Creating panel...');
        }
        
        fetch(window.baseUrl + 'admin/dynamicforms/create-panel', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(function(response) {
            // Try to parse as JSON first
            var contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                return response.json();
            }
            // If not JSON, it's likely a redirect - treat as success
            if (response.ok) {
                return { success: true, message: 'Panel created successfully' };
            }
            throw new Error('Server returned status ' + response.status);
        })
        .then(function(data) {
            if(window.SimpleModal && window.SimpleModal.hideLoading) {
                window.SimpleModal.hideLoading();
            }
            
            // Update CSRF token if provided
            if (data.csrf_token) {
                var meta = document.querySelector('meta[name="csrf-hash"]');
                if(meta) meta.setAttribute('content', data.csrf_token);
                self.csrfToken = data.csrf_token;
            }
            
            if (data.success !== false) {
                // Success - reload page to show new panel
                SimpleModal.alert('Panel created successfully!', 'Success', 'success').then(function(){
                    window.location.reload();
                });
            } else {
                // Error from server
                SimpleModal.alert(data.message || 'Failed to create panel', 'Error', 'error');
            }
        })
        .catch(function(error) {
            if(window.SimpleModal && window.SimpleModal.hideLoading) {
                window.SimpleModal.hideLoading();
            }
            console.error('Panel creation error:', error);
            SimpleModal.alert('Failed to create panel: ' + error.message + '. Please check the console for details.', 'Error', 'error');
        });
    };
    
    AdminConfigurations.prototype.loadPanelDepartments = function(){
        var self = this;
        fetch(window.baseUrl + 'admin/configurations/get-departments')
            .then(r => r.json())
            .then(data => {
                var select = document.getElementById('sm_panel_department');
                if(!select || !data.departments) return;
                
                // Check if user is department admin
                var isDeptAdmin = data.is_department_admin || false;
                var userDeptId = data.user_department_id || null;
                
                data.departments.forEach(function(dept){
                    var opt = document.createElement('option');
                    opt.value = dept.id;
                    opt.textContent = dept.code + ' - ' + dept.description;
                    select.appendChild(opt);
                });
                
                // If department admin, lock to their department
                if(isDeptAdmin && userDeptId){
                    select.value = userDeptId;
                    select.disabled = true;
                    // Trigger change to load offices
                    select.dispatchEvent(new Event('change'));
                }
                
                // Setup department change listener
                select.addEventListener('change', function(){
                    var deptId = this.value;
                    var officeSelect = document.getElementById('sm_panel_office');
                    if(!officeSelect) return;
                    
                    // Clear offices
                    officeSelect.innerHTML = '<option value="">-- Select Office --</option>';
                    
                    if(!deptId) return;
                    
                    // Load offices for department
                    fetch(window.baseUrl + 'admin/offices/by-department/' + deptId)
                        .then(r => r.json())
                        .then(data => {
                            if(data.offices){
                                data.offices.forEach(function(office){
                                    var opt = document.createElement('option');
                                    opt.value = office.id;
                                    opt.textContent = office.code + ' - ' + office.description;
                                    officeSelect.appendChild(opt);
                                });
                            }
                        })
                        .catch(function(err){
                            console.error('Error loading offices:', err);
                        });
                });
            });
    };
    
    AdminConfigurations.prototype.loadPanelOffices = function(){
        fetch(window.baseUrl + 'admin/configurations/get-offices')
            .then(r => r.json())
            .then(data => {
                // Don't populate initially - will be populated when department is selected
            });
    };
    AdminConfigurations.prototype.copyPanel = function(){ if(!this.selectedRow) return; var p=this.currentCode(); var self=this; SimpleModal.show({title:'Copy Panel',variant:'info',message:'<div class="mb-2 small text-muted">Copy from <strong>'+self.escapeHtml(p)+'</strong></div><label class="form-label">New Panel Name</label><input type="text" id="sm_copy_panel_name" class="form-control form-control-sm" value="'+self.escapeHtml(p)+'_copy">',buttons:[{text:'Cancel',value:'x'},{text:'Copy',value:'copy',primary:true}]}).then(function(v){ if(v==='copy'){ var newName=(document.getElementById('sm_copy_panel_name').value||'').trim(); if(!newName){ SimpleModal.alert('Panel name required.','Validation','warning'); return; } self.postPanelForm('copy-panel',{source_panel_name:p,new_panel_name:newName}); }}); };
    AdminConfigurations.prototype.copyPanel = function(){ if(!this.selectedRow) return; var p=this.currentCode(); var self=this; SimpleModal.show({title:'Copy Panel',variant:'info',message:'<div class="mb-2 small text-muted">Copy from <strong>'+self.escapeHtml(p)+'</strong></div><label class="form-label">New Panel Name</label><input type="text" id="sm_copy_panel_name" class="form-control form-control-sm" value="'+self.escapeHtml(p)+'_copy">',buttons:[{text:'Cancel',value:'x'},{text:'Copy',value:'copy',primary:true}]}).then(function(v){ if(v==='copy'){ var newName=(document.getElementById('sm_copy_panel_name').value||'').trim(); if(!newName){ SimpleModal.alert('Panel name required.','Validation','warning'); return; } self.postPanelForm('copy-panel',{source_panel_name:p,new_panel_name:newName}); }}); };
    
    AdminConfigurations.prototype.deletePanel = function(){ 
        if(!this.selectedRow) return; 
        var p=this.currentCode(); 
        var self=this; 
        
        // Delete confirmation with warning about cleaning up all references
        SimpleModal.confirm(
            'Delete panel "'+this.escapeHtml(p)+'"? This will also remove all references to this panel from forms and submissions. This cannot be undone.',
            'Confirm Delete',
            'warning'
        ).then(function(ok){ 
            if(!ok) return; 
            self.postPanelForm('delete-panel',{panel_name:p}); 
        });
    };
    AdminConfigurations.prototype.postPanelForm = function(endpoint, fields){
        // Read freshest CSRF tokens from meta tags to avoid stale-token 403s
        var csrfNameMeta = document.querySelector('meta[name="csrf-name"]');
        var csrfHashMeta = document.querySelector('meta[name="csrf-hash"]');
        var csrfName = (csrfNameMeta && csrfNameMeta.getAttribute('content')) || this.csrfTokenName || 'csrf_test_name';
        var csrfHash = (csrfHashMeta && csrfHashMeta.getAttribute('content')) || this.csrfToken || '';

        var f=document.createElement('form');
        f.method='POST';
        f.action= window.baseUrl + 'admin/dynamicforms/'+endpoint;

        var c=document.createElement('input'); c.type='hidden'; c.name=csrfName; c.value=csrfHash; c.setAttribute('data-csrf-managed','1'); f.appendChild(c);
        Object.entries(fields).forEach(([k,v])=>{ var i=document.createElement('input'); i.type='hidden'; i.name=k; i.value=v; f.appendChild(i); });
        document.body.appendChild(f);
        // Use submit() to navigate; note: programmatic submit() does not trigger submit handlers, so token must be present above
        f.submit();
    };

    AdminConfigurations.prototype.escapeHtml = function(str){ return (str||'').toString().replace(/[&<>"'`]/g,function(s){return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#39;','`':'&#96;'}[s]);}); };

    // Boot
    document.addEventListener('DOMContentLoaded', function(){
        var table = document.querySelector('table[data-type]');
        if(table){
            var type = table.getAttribute('data-type');
            new AdminConfigurations(type);
        }
        
        // Handle panel assignment radios in Forms tab
        document.querySelectorAll('.panel-assignment-radio').forEach(function(radio){
            radio.addEventListener('change', function(){
                var formId = this.getAttribute('data-form-id');
                var panelName = this.value;
                assignPanelToForm(formId, panelName);
            });
        });
        
        // Handle unassign panel buttons
        document.querySelectorAll('.clear-panel-btn').forEach(function(btn){
            btn.addEventListener('click', function(){
                var formId = this.getAttribute('data-form-id');
                assignPanelToForm(formId, '');
            });
        });
    });
    
    function assignPanelToForm(formId, panelName){
        // Get CSRF tokens
        var csrfNameMeta = document.querySelector('meta[name="csrf-name"]');
        var csrfHashMeta = document.querySelector('meta[name="csrf-hash"]');
        var csrfName = (csrfNameMeta && csrfNameMeta.getAttribute('content')) || 'csrf_test_name';
        var csrfHash = (csrfHashMeta && csrfHashMeta.getAttribute('content')) || '';
        
        var formData = new FormData();
        formData.append(csrfName, csrfHash);
        formData.append('form_id', formId);
        formData.append('panel_name', panelName);
        
        fetch(window.baseUrl + 'admin/configurations/assign-panel-to-form', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        })
        .then(function(r){ return r.json(); })
        .then(function(data){
            // Update CSRF token
            if(data.csrf_token){
                var meta = document.querySelector('meta[name="csrf-hash"]');
                if(meta) meta.setAttribute('content', data.csrf_token);
            }
            
            if(data.success){
                // Show success message and reload page
                if(typeof SimpleModal !== 'undefined'){
                    SimpleModal.alert(data.message, 'Success', 'success').then(function(){
                        window.location.reload();
                    });
                } else {
                    alert(data.message);
                    window.location.reload();
                }
            } else {
                if(typeof SimpleModal !== 'undefined'){
                    SimpleModal.alert(data.message || 'Failed to assign panel', 'Error', 'error');
                } else {
                    alert(data.message || 'Failed to assign panel');
                }
            }
        })
        .catch(function(err){
            console.error('Error assigning panel:', err);
            if(typeof SimpleModal !== 'undefined'){
                SimpleModal.alert('Request failed. Please try again.', 'Error', 'error');
            } else {
                alert('Request failed. Please try again.');
            }
        });
    }
})(window);
