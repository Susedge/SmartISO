// Notification utility module
export function notify(message, type = 'info', options = {}) {
  const duration = options.duration || 3000;
  const position = options.position || 'right';
  const gravity = options.gravity || 'top';
  const colors = { success: '#198754', error: '#dc3545', info: '#0d6efd', warning: '#ff9f43' };
  const background = colors[type] || colors.info;
  try {
    if (window.Toastify) {
      Toastify({ text: String(message), duration, gravity, position, close: true, stopOnFocus: true, style: { background } }).showToast();
      return;
    }
  } catch (e) { console.warn('Toastify failed', e); }
  try {
    let container = document.getElementById('globalToastsContainer');
    if (!container) {
      container = document.createElement('div');
      container.id = 'globalToastsContainer';
      Object.assign(container.style, { position: 'fixed', zIndex: 1080, top: '1rem', right: '1rem', width: '320px' });
      document.body.appendChild(container);
    }
    const toastId = 'toast_' + Date.now() + '_' + Math.floor(Math.random()*1000);
    const bg = (type === 'success') ? 'bg-success text-white' : (type === 'error') ? 'bg-danger text-white' : (type === 'warning') ? 'bg-warning text-dark' : 'bg-info text-white';
    const temp = document.createElement('div');
    temp.innerHTML = `<div id="${toastId}" class="toast ${bg}" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="true"><div class="toast-body small" style="word-break:break-word;">${String(message)}</div></div>`;
    const toastEl = temp.firstElementChild;
    container.appendChild(toastEl);
    if (window.bootstrap && window.bootstrap.Toast) {
      const bsToast = new bootstrap.Toast(toastEl, { delay: duration });
      bsToast.show();
      toastEl.addEventListener('hidden.bs.toast', () => { try { toastEl.remove(); } catch(_){} });
    } else {
      setTimeout(() => { try { toastEl.remove(); } catch(_){} }, duration);
    }
  } catch (e) {
    try { alert(String(message)); } catch(_) { console.log(message); }
  }
}

// Attach to window for backward compatibility
if (!window.notify) window.notify = notify;
