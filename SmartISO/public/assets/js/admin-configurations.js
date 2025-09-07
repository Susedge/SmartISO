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
        global.adminConfigInstance = this; // expose if needed elsewhere
    };

    AdminConfigurations.prototype._ensureSelectionStyle = function(){
        if(document.getElementById('admin-config-selection-style')) return;
        var css = 'table[data-type] tbody tr.admin-selected{outline:3px solid rgba(13,110,253,.12);box-shadow:inset 0 0 0 1px rgba(13,110,253,.06);background-color:rgba(13,110,253,.06) !important}table[data-type] tbody tr.admin-selected td{font-weight:600}';
        var st = document.createElement('style'); st.id = 'admin-config-selection-style'; st.textContent = css; document.head.appendChild(st);
    };

    AdminConfigurations.prototype.initDataTable = function(){
        if(!this.table || !global.jQuery || !jQuery.fn.dataTable) return;
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
        var first = this.table.querySelector('tbody tr');
        if(first){ this.setSelection(first); }
        else {
            // no rows: ensure actions reflect no selection
            this.clearSelection();
        }
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
        SimpleModal.show({title:'Create Panel',variant:'info',message:'<label class="form-label">Panel Name</label><input type="text" id="sm_new_panel_name" class="form-control form-control-sm" placeholder="Enter panel name">',buttons:[{text:'Cancel',value:'x'},{text:'Create',value:'c',primary:true}]}).then(function(v){ if(v==='c'){ var name=(document.getElementById('sm_new_panel_name').value||'').trim(); if(!name){ SimpleModal.alert('Panel name required.','Validation','warning'); return; } self.postPanelForm('create-panel',{panel_name:name}); }});
        setTimeout(()=>document.getElementById('sm_new_panel_name')?.focus(),60);
    };
    AdminConfigurations.prototype.copyPanel = function(){ if(!this.selectedRow) return; var p=this.currentCode(); var self=this; SimpleModal.show({title:'Copy Panel',variant:'info',message:'<div class="mb-2 small text-muted">Copy from <strong>'+self.escapeHtml(p)+'</strong></div><label class="form-label">New Panel Name</label><input type="text" id="sm_copy_panel_name" class="form-control form-control-sm" value="'+self.escapeHtml(p)+'_copy">',buttons:[{text:'Cancel',value:'x'},{text:'Copy',value:'copy',primary:true}]}).then(function(v){ if(v==='copy'){ var newName=(document.getElementById('sm_copy_panel_name').value||'').trim(); if(!newName){ SimpleModal.alert('Panel name required.','Validation','warning'); return; } self.postPanelForm('copy-panel',{source_panel_name:p,new_panel_name:newName}); }}); };
    AdminConfigurations.prototype.deletePanel = function(){ if(!this.selectedRow) return; var p=this.currentCode(); var self=this; SimpleModal.confirm('Delete panel "'+this.escapeHtml(p)+'"? This cannot be undone.','Confirm Delete','warning').then(function(ok){ if(!ok) return; self.postPanelForm('delete-panel',{panel_name:p}); }); };
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
        if(!table) return; var type = table.getAttribute('data-type');
        new AdminConfigurations(type);
    });
})(window);
