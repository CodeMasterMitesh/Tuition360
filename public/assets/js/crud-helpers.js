// Shared CRUD helpers
function resolveCsrfToken() {
    try {
        if (window.__csrfToken) return window.__csrfToken;
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta && meta.content ? meta.content : null;
    } catch (err) {
        return null;
    }
}
window.getCsrfToken = resolveCsrfToken;

window.CRUD = {
    get: async function(url, extraOpts = {}) {
        const opts = Object.assign({ credentials: 'same-origin' }, extraOpts || {});
        opts.headers = Object.assign({ 'X-Requested-With': 'XMLHttpRequest' }, opts.headers || {});
        const res = await fetch(url, opts);
        return res.json();
    },
    post: async function(url, body, extraOpts = {}) {
        const opts = Object.assign({ method: 'POST', credentials: 'same-origin' }, extraOpts || {});
        opts.headers = Object.assign({ 'X-Requested-With': 'XMLHttpRequest' }, opts.headers || {});
        const token = resolveCsrfToken();
        if (token) {
            opts.headers['X-CSRF-Token'] = token;
        }

        if (body instanceof FormData || body instanceof URLSearchParams) {
            opts.body = body;
        } else {
            opts.headers['Content-Type'] = 'application/x-www-form-urlencoded; charset=UTF-8';
            opts.body = new URLSearchParams(body);
        }
        const res = await fetch(url, opts);
        return res.json();
    },
    formToParams: function(form) {
        return new FormData(form);
    },
    showLoading: function(containerId) {
        const container = containerId ? document.getElementById(containerId) : document.querySelector('.table-responsive');
        if (!container) return;
        const overlay = document.createElement('div');
        overlay.className = 'loading-overlay';
        overlay.innerHTML = `<div class="spinner-border text-primary spinner" role="status"><span class="visually-hidden">Loading...</span></div>`;
        container.style.position = 'relative';
        container.appendChild(overlay);
    },
    hideLoading: function() {
        const overlay = document.querySelector('.loading-overlay');
        if (overlay) overlay.remove();
    }
};

// Toast container (Bootstrap toasts)
(function(){
    const containerId = 'crud-toast-container';
    function ensureContainer(){
        let c = document.getElementById(containerId);
        if (!c) {
            c = document.createElement('div');
            c.id = containerId;
            c.style.position = 'fixed';
            c.style.top = '1rem';
            c.style.right = '1rem';
            c.style.zIndex = 1080;
            document.body.appendChild(c);
        }
        return c;
    }

    window.CRUD.toast = function(message, title='Notice', type='info', timeout=4000){
        const c = ensureContainer();
        const id = 'toast-' + Date.now();
        const bg = type === 'success' ? 'bg-success text-white' : (type === 'error' ? 'bg-danger text-white' : 'bg-light');
        const toast = document.createElement('div');
        toast.className = `toast ${bg}`;
        toast.id = id;
        toast.role = 'status';
        toast.style.minWidth = '230px';
        toast.style.marginBottom = '0.5rem';
        toast.innerHTML = `
            <div class="toast-header ${type==='success' || type==='error' ? 'text-white' : ''}">
                <strong class="me-auto">${title}</strong>
                <small></small>
                <button type="button" class="btn-close ms-2 mb-1" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">${message}</div>
        `;
        c.appendChild(toast);
        const btoast = new bootstrap.Toast(toast, { delay: timeout });
        btoast.show();
        // remove after hidden
        toast.addEventListener('hidden.bs.toast', () => toast.remove());
        return btoast;
    };

    window.CRUD.toastSuccess = function(msg){ return window.CRUD.toast(msg,'Success','success',3000); };
    window.CRUD.toastError = function(msg){ return window.CRUD.toast(msg,'Error','error',6000); };

    // Override alert -> toast
    window._nativeAlert = window.alert;
    window.alert = function(msg){ try{ window.CRUD.toast(msg,'Notice','info',4000); } catch(e){ window._nativeAlert(msg); } };

    // Modal loading indicator helpers
    window.CRUD.modalLoadingStart = function(modalEl){
        if (!modalEl) return;
        let overlay = modalEl.querySelector('.modal-loading-overlay');
        if (!overlay){
            overlay = document.createElement('div');
            overlay.className = 'modal-loading-overlay';
            overlay.style.position = 'absolute';
            overlay.style.inset = '0';
            overlay.style.background = 'rgba(255,255,255,0.6)';
            overlay.style.display = 'flex';
            overlay.style.alignItems = 'center';
            overlay.style.justifyContent = 'center';
            overlay.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>';
            overlay.style.zIndex = 1051;
            modalEl.querySelector('.modal-dialog').style.position = 'relative';
            modalEl.querySelector('.modal-content').appendChild(overlay);
        }
        overlay.style.display = 'flex';
    };
    window.CRUD.modalLoadingStop = function(modalEl){
        if (!modalEl) return;
        const overlay = modalEl.querySelector('.modal-loading-overlay');
        if (overlay) overlay.style.display = 'none';
    };

    // Simple inline validation attach: finds forms inside modals and prevents submission if invalid
    function attachInlineValidation(){
        document.querySelectorAll('form[id]').forEach(form => {
            // avoid attaching twice
            if (form.dataset.crudValidationAttached) return;
            form.dataset.crudValidationAttached = '1';
            const modal = form.closest('.modal');
            if (!modal) return;
            form.addEventListener('submit', function(ev){
                if (!form.checkValidity()){
                    ev.preventDefault();
                    ev.stopPropagation();
                    Array.from(form.elements).forEach(el => {
                        if (el.willValidate){
                            el.classList.toggle('is-invalid', !el.checkValidity());
                        }
                    });
                    window.CRUD.toastError('Please fix the highlighted fields.');
                }
            });
            // remove invalid class on input
            Array.from(form.elements).forEach(el => {
                el.addEventListener('input', () => el.classList.remove('is-invalid'));
            });
        });
    }

    // Expose initializer so nav-ajax or callers can re-run validation attachment
    window.initCRUDHelpers = function(){ try { attachInlineValidation(); } catch(e) { console.error('initCRUDHelpers failed', e); } };

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', attachInlineValidation); else attachInlineValidation();
})();
