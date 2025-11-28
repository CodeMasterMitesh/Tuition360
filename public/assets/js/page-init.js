// page-init.js — generic page initializer to standardize DataTable setup and selection handling
(function(){
    function init() {
        try {
            // Find first data-table on the page
            const table = document.querySelector('.table.data-table');
            if (!table) return;
            const id = table.id || null;
            // Use shared initAdvancedTable helper
            try { initAdvancedTable('#' + id); } catch(e) { console.error('initAdvancedTable failed', e); }

            // Show container
            const container = document.querySelector('.dashboard-container'); if (container) container.classList.add('show');

            // Selection handling
            const selectAll = document.querySelector('#select-all-' + (id ? id.replace('-table','') : ''));
            function updateSelectionUI() {
                const any = !!document.querySelectorAll('table.data-table tbody .row-select:checked').length;
                const headerBtn = document.getElementById('delete-selected-' + (id ? id.replace('-table','') + 's-header' : ''));
                const topBtn = document.getElementById('delete-selected-' + (id ? id.replace('-table','') + 's' : ''));
                if (headerBtn) headerBtn.style.display = any ? '' : 'none';
                if (topBtn) topBtn.style.display = any ? '' : 'none';
                if (selectAll) {
                    const total = document.querySelectorAll('table.data-table tbody .row-select').length;
                    const checked = document.querySelectorAll('table.data-table tbody .row-select:checked').length;
                    selectAll.checked = total > 0 && checked === total;
                }
            }
            if (selectAll) {
                selectAll.addEventListener('change', function(){ const checked = !!this.checked; document.querySelectorAll('table.data-table tbody .row-select').forEach(cb=>cb.checked=checked); updateSelectionUI(); });
            }
            document.addEventListener('change', function(e){ if (e.target && e.target.classList && e.target.classList.contains('row-select')) updateSelectionUI(); });
            updateSelectionUI();

            // Declarative keyboard shortcuts
            document.addEventListener('keydown', function(e) {
                if (e.ctrlKey && e.key === 'f') {
                    e.preventDefault();
                    const input = document.querySelector('table.data-table thead tr.filters input'); if (input) { input.focus(); input.select && input.select(); }
                }
                if (e.ctrlKey && e.key === 'n') {
                    e.preventDefault();
                    // Find add button via data-modal-target or button text
                    const addBtn = document.querySelector('[data-modal-target], .btn-action');
                    if (addBtn) addBtn.click();
                }
            });

        } catch(e) { console.error('page-init failed', e); }
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
})();
