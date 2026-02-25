import { router } from '@inertiajs/vue3';

export function useToast() {
  const show = (message, type = 'success') => {
    // Create toast element
    const toastContainer = document.getElementById('toast-container') || createToastContainer();
    
    const toastId = `toast-${Date.now()}`;
    const iconMap = {
      success: 'check-circle',
      error: 'exclamation-triangle',
      warning: 'exclamation-circle',
      info: 'info-circle'
    };
    
    const bgMap = {
      success: 'bg-success',
      error: 'bg-danger',
      warning: 'bg-warning',
      info: 'bg-info'
    };
    
    const toastHtml = `
      <div id="${toastId}" class="toast align-items-center text-white ${bgMap[type]} border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
          <div class="toast-body">
            <i class="bi bi-${iconMap[type]} me-2"></i>${message}
          </div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
      </div>
    `;
    
    toastContainer.insertAdjacentHTML('beforeend', toastHtml);
    
    const toastElement = document.getElementById(toastId);
    const toast = new window.bootstrap.Toast(toastElement, { delay: 3000 });
    
    toast.show();
    
    toastElement.addEventListener('hidden.bs.toast', () => {
      toastElement.remove();
    });
  };
  
  const createToastContainer = () => {
    const container = document.createElement('div');
    container.id = 'toast-container';
    container.className = 'toast-container position-fixed top-0 end-0 p-3';
    container.style.zIndex = '9999';
    document.body.appendChild(container);
    return container;
  };
  
  const success = (message) => show(message, 'success');
  const error = (message) => show(message, 'error');
  const warning = (message) => show(message, 'warning');
  const info = (message) => show(message, 'info');
  
  return {
    show,
    success,
    error,
    warning,
    info
  };
}
