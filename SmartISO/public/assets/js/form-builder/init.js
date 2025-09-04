import { FormBuilder } from './core.js';
import { notify } from './notify.js';

document.addEventListener('DOMContentLoaded', () => {
  if (document.querySelector('.form-builder-container')) {
    setTimeout(() => {
      const fb = new FormBuilder();
      fb.init();
      if (typeof fb.reorganizeFormLayout === 'function') {
        fb.reorganizeFormLayout();
      }
      window.formBuilder = fb; // preserve global reference
      notify('Form Builder (modular) initialized', 'info', { duration: 1500 });
    }, 50);
  }
});
