// utilities.js - global helper functions & lightweight custom modal
// Provides: window.Utils, window.SimpleModal
(function(){
  const Utils = {
    escapeHtml(str){
      return (str||'').toString().replace(/[&<>"'`]/g,s=>({
        '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#39;','`':'&#96;'
      }[s]));
    },
    qs(sel,ctx){ return (ctx||document).querySelector(sel); },
    qsa(sel,ctx){ return Array.from((ctx||document).querySelectorAll(sel)); },
    on(el, evt, handler, opts){ if(el) el.addEventListener(evt, handler, opts||false); },
    off(el, evt, handler){ if(el) el.removeEventListener(evt, handler); },
    ajax(url, {method='GET', data=null, headers={}, json=true}={}){
      const opts = { method, headers: Object.assign({'X-Requested-With':'XMLHttpRequest'}, headers) };
      if(data){
        if(data instanceof FormData){ opts.body = data; }
        else if(typeof data === 'object'){ opts.headers['Content-Type']='application/json'; opts.body = JSON.stringify(data); }
        else { opts.body = data; }
      }
      return fetch(url, opts).then(r=> json? r.json(): r.text());
    },
    toast(msg, type='info'){ if(!msg) return; if(typeof Toastify==='undefined'){ console.log(type+': '+msg); return; } Toastify({ text: msg, duration: 4000, gravity:'top', position:'right', close:true, style:{ background: type==='error'? '#dc3545':'#0d6efd' } }).showToast(); }
  };
  window.Utils = window.Utils || Utils;

  // SimpleModal: minimal dependency-free modal/alert/confirm
  const SimpleModal = (function(){
    let overlay, box, titleEl, bodyEl, footerEl, focusTrapStart, focusTrapEnd;
    let styleInjected = false;
    function injectStyles(){
      if(styleInjected) return; styleInjected=true;
      const css = `#simpleModalOverlay .simple-modal{opacity:0;transform:scale(.92);transition:opacity .18s ease,transform .18s ease}#simpleModalOverlay.show .simple-modal{opacity:1;transform:scale(1)}#simpleModalOverlay .simple-modal .sm-icon{display:none}#simpleModalOverlay .simple-modal.sm-variant-info .sm-icon,#simpleModalOverlay .simple-modal.sm-variant-success .sm-icon,#simpleModalOverlay .simple-modal.sm-variant-error .sm-icon,#simpleModalOverlay .simple-modal.sm-variant-warning .sm-icon{display:block}#simpleModalOverlay .simple-modal.sm-variant-info .sm-icon{color:#0d6efd}#simpleModalOverlay .simple-modal.sm-variant-success .sm-icon{color:#198754}#simpleModalOverlay .simple-modal.sm-variant-error .sm-icon{color:#dc3545}#simpleModalOverlay .simple-modal.sm-variant-warning .sm-icon{color:#f59e0b}#simpleModalOverlay .simple-modal.sm-variant-warning .sm-header{background:linear-gradient(90deg,#fffbe6,#fff)}#simpleModalOverlay .simple-modal.sm-variant-error .sm-header{background:linear-gradient(90deg,#ffecec,#fff)}#simpleModalOverlay .simple-modal.sm-variant-success .sm-header{background:linear-gradient(90deg,#e8f8ef,#fff)}#simpleModalOverlay .simple-modal .sm-header{position:relative}#simpleModalOverlay .simple-modal.sm-variant-warning .sm-header:before{content:"";position:absolute;inset:0; border-top:3px solid #f59e0b}#simpleModalOverlay .simple-modal.sm-variant-error .sm-header:before{content:"";position:absolute;inset:0; border-top:3px solid #dc3545}#simpleModalOverlay .simple-modal.sm-variant-success .sm-header:before{content:"";position:absolute;inset:0; border-top:3px solid #198754}`;
      const st=document.createElement('style'); st.textContent=css; document.head.appendChild(st);
    }
    function ensure(){
      if(overlay) return;
      injectStyles();
      overlay = document.createElement('div');
      overlay.id = 'simpleModalOverlay';
      overlay.style.cssText = 'position:fixed;inset:0;display:none;align-items:center;justify-content:center;background:rgba(15,23,42,.55);z-index:1080;padding:1rem;';
      overlay.innerHTML = '<div class="simple-modal" role="dialog" aria-modal="true" style="max-width:480px;width:100%;background:#fff;border-radius:12px;box-shadow:0 10px 40px -5px rgba(0,0,0,.3);display:flex;flex-direction:column;overflow:hidden;font-family:system-ui,Segoe UI,Roboto,Helvetica,Arial,sans-serif">\n'+
        '<div class="sm-header" style="padding:1rem 1.25rem;border-bottom:1px solid #eef2f7;display:flex;align-items:center;gap:.75rem;">\n'+
          '<div class="sm-icon" style="flex:0 0 auto;font-size:1.25rem;display:none"><i class="fas fa-info-circle"></i></div>\n'+
          '<h5 class="sm-title" style="margin:0;font-size:1rem;font-weight:600;flex:1 1 auto;">Title</h5>\n'+
          '<button type="button" class="sm-close" aria-label="Close" style="border:none;background:transparent;font-size:1.1rem;line-height:1;color:#64748b;cursor:pointer">&times;</button>\n'+
        '</div>\n'+
        '<div class="sm-body" style="padding:1.25rem;line-height:1.45;font-size:.93rem;color:#334155;"></div>\n'+
        '<div class="sm-footer" style="padding:.75rem 1.25rem;border-top:1px solid #eef2f7;display:flex;justify-content:flex-end;gap:.5rem;"></div>\n'+
      '</div>';
      document.body.appendChild(overlay);
      box = overlay.querySelector('.simple-modal');
      titleEl = overlay.querySelector('.sm-title');
      bodyEl = overlay.querySelector('.sm-body');
      footerEl = overlay.querySelector('.sm-footer');
      focusTrapStart = document.createElement('div'); focusTrapStart.tabIndex=0; focusTrapEnd = document.createElement('div'); focusTrapEnd.tabIndex=0; box.prepend(focusTrapStart); box.appendChild(focusTrapEnd);
      overlay.addEventListener('click', e=>{ if(e.target===overlay){ if(current && current.backdropClose) hide(); }});
      overlay.querySelector('.sm-close').addEventListener('click', ()=> hide('close'));
      focusTrapStart.addEventListener('focus', ()=>{ const btns = box.querySelectorAll('button,a[href],input,select,textarea'); if(btns.length) btns[btns.length-1].focus(); });
      focusTrapEnd.addEventListener('focus', ()=>{ const btns = box.querySelectorAll('button,a[href],input,select,textarea'); if(btns.length) btns[0].focus(); });
      document.addEventListener('keydown', e=>{ if(!overlay || overlay.style.display==='none') return; if(e.key==='Escape'){ if(current && current.escClose) hide('esc'); }});
    }
    let current = null;
    function show(opts){
      ensure();
      current = Object.assign({ title:'', message:'', buttons:[], escClose:true, backdropClose:false, variant:'info', icon:null }, opts||{});
      titleEl.textContent = current.title || '';
      bodyEl.innerHTML = typeof current.message==='string'? current.message: '';
      footerEl.innerHTML = '';
      if(!current.buttons.length){ current.buttons = [{ text:'OK', primary:true, value:'ok' }]; }
      // Variant styling
      box.classList.remove('sm-variant-info','sm-variant-success','sm-variant-error','sm-variant-warning');
      const variants = ['info','success','error','warning'];
      const v = variants.includes(current.variant) ? current.variant : 'info';
      box.classList.add('sm-variant-'+v);
      const iconMap = { info:'fa-info-circle', success:'fa-check-circle', error:'fa-times-circle', warning:'fa-exclamation-triangle' };
      const iconWrap = box.querySelector('.sm-icon');
      if(iconWrap){ iconWrap.innerHTML = '<i class="fas '+(current.icon||iconMap[v])+'"></i>'; }
      current.buttons.forEach(btnCfg=>{
        const b = document.createElement('button');
        b.type='button';
        b.className = 'sm-btn ' + (btnCfg.primary? 'btn-primary':'btn-light');
        b.style.cssText = 'font-size:.85rem;font-weight:600;border-radius:6px;padding:.55rem 1rem;border:1px solid '+(btnCfg.primary?'#0d6efd':'#cbd5e1')+';background:'+(btnCfg.primary?'#0d6efd':'#f8fafc')+';color:'+(btnCfg.primary?'#fff':'#0f172a')+';cursor:pointer;';
        b.textContent = btnCfg.text || 'OK';
        b.addEventListener('click', ()=>{ if(btnCfg.onClick){ try{ btnCfg.onClick(btnCfg.value); }catch(e){} } hide(btnCfg.value); });
        footerEl.appendChild(b);
      });
  // enable pointer events so the overlay captures clicks while visible
  overlay.style.pointerEvents = 'auto';
  overlay.style.display='flex';
  requestAnimationFrame(()=> overlay.classList.add('show'));
  // prevent body scroll while modal visible
  document.body.style.overflow='hidden';
  setTimeout(()=>{ const firstBtn = footerEl.querySelector('button'); if(firstBtn) firstBtn.focus(); },30);
      return new Promise(resolve=>{ current._resolver = resolve; });
    }
    function hide(reason){
      if(!overlay) return;
      // start hide transition
      overlay.classList.remove('show');
      // immediately allow pointer events to pass through so underlying UI is clickable
      try{ overlay.style.pointerEvents = 'none'; }catch(e){}
      // restore body scroll immediately
      try{ document.body.style.overflow = ''; }catch(e){}
      setTimeout(()=>{
        // hide overlay after transition completes
        overlay.style.display='none';
        if(current && current._resolver){ try{ current._resolver(reason); }catch(e){} }
        current=null;
      },130);
    }
    function alert(message, title, variant){ return show({ title: title||'Notice', message: Utils.escapeHtml(message), variant: variant||'info', buttons:[{text:'OK', primary:true, value:'ok'}] }); }
    function confirm(message, title, variant){ return show({ title: title||'Confirm', message: Utils.escapeHtml(message), variant: variant||'info', buttons:[{text:'Cancel', primary:false, value:'cancel'},{text:'OK', primary:true, value:'ok'}] }).then(res=> res==='ok'); }
    // Helper to use in inline onclick handlers: confirm and submit the enclosing form when confirmed.
    function confirmAndSubmit(event, message, title){
      try {
        if (!event) return false;
        event.preventDefault();
        const el = event.target || event.srcElement;
        const form = el.closest ? el.closest('form') : null;
        if (window.SimpleModal) {
            window.SimpleModal.confirm(message, title||'Confirm', 'warning').then(function(ok){
              if (ok) {
                if (form) form.submit(); else { if (el && el.click) el.click(); }
              }
            });
          return false;
        }
      } catch (e) { /* fallthrough to default */ }
      // Fallback to native confirm when SimpleModal unavailable
        return window.confirm(message);
    }
    // expose helper globally from inside the closure where confirmAndSubmit is defined
    try { window.confirmAndSubmit = window.confirmAndSubmit || confirmAndSubmit; } catch (e) { /* ignore if no window */ }
    return { show, hide, alert, confirm };
  })();
  window.SimpleModal = window.SimpleModal || SimpleModal;
  // Auto-bind any forms with data-confirm attributes to use SimpleModal
    function autoBindConfirmForms(scope){
      const ctx = scope || document;
      Array.from(ctx.querySelectorAll('form[data-confirm]')).forEach(form => {
        // avoid double-binding
        if (form.__confirmBound) return; form.__confirmBound = true;
        form.addEventListener('submit', function(e){
          e.preventDefault();
          const msg = form.getAttribute('data-confirm') || 'Are you sure?';
          const title = form.getAttribute('data-confirm-title') || 'Confirm';
          const variant = form.getAttribute('data-confirm-variant') || 'warning';
          if (window.SimpleModal) {
            window.SimpleModal.confirm(msg, title, variant).then(ok => { if (ok) form.submit(); });
          } else {
            if (window.confirm(msg)) form.submit();
          }
        });
      });
    }
    // Run at load time
    if (document.readyState !== 'loading') { autoBindConfirmForms(); } else { document.addEventListener('DOMContentLoaded', ()=> autoBindConfirmForms()); }
})();
