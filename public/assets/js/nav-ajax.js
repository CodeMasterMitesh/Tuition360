// nav-ajax.js
// Intercept internal navigation links that include ?page=... and load content via AJAX
(function(){
    function getPageParamFromUrl(url) {
        try {
            const u = new URL(url, window.location.origin);
            return u.searchParams.get('page');
        } catch(e) {
            return null;
        }
    }

    function findMainContainer(doc) {
        // Try common selectors used by the app views
        return doc.querySelector('.dashboard-container') || doc.querySelector('.container-fluid') || doc.querySelector('main') || doc.body;
    }

    function replaceContent(newDoc) {
        const newMain = findMainContainer(newDoc);
        const curMain = document.querySelector('.dashboard-container') || document.querySelector('.container-fluid') || document.querySelector('main') || document.body;
        if (!newMain || !curMain) return;
        // Remove scripts from the fragment before inserting into DOM to avoid
        // inline scripts executing or existing in the DOM before their
        // dependencies (like jQuery/DataTables) have loaded.
        const fragClone = newMain.cloneNode(true);
        const scriptsInFrag = Array.from(fragClone.querySelectorAll('script'));
        scriptsInFrag.forEach(s => s.parentNode && s.parentNode.removeChild(s));
        // Replace current main content with cleaned HTML (no script tags)
        curMain.innerHTML = fragClone.innerHTML;
        // Move any modals/offcanvas/toast elements from the fetched document
        // into the current document so page-level UI like modals are present.
        try {
            const extraSelectors = ['.modal', '.offcanvas', '.toast'];
            extraSelectors.forEach(sel => {
                Array.from(newDoc.querySelectorAll(sel)).forEach(el => {
                    // clone the element
                    const clone = el.cloneNode(true);
                    const id = clone.id;
                    if (id && document.getElementById(id)) {
                        // replace existing
                        const existing = document.getElementById(id);
                        existing.parentNode.replaceChild(clone, existing);
                    } else {
                        document.body.appendChild(clone);
                    }
                });
            });
        } catch (e) { console.error('nav-ajax: failed to migrate modals/offcanvas/toasts', e); }

        // Remove temporary hide-class if present (prevents flash)
        try { document.documentElement.classList.remove('tuition-wait-lastpage'); } catch(e) {}
        // Update document title if available
        const newTitle = newDoc.querySelector('title');
        if (newTitle) document.title = newTitle.textContent;
        // Execute any scripts from the new doc.
        // Load external scripts first (in order) and wait for them to finish
        // before running inline scripts and page init. This ensures libraries
        // like jQuery/DataTables are available when page init runs.
        // collect scripts from the entire fetched document (so footer or page-level
        // scripts that live outside the main container are detected)
        const scripts = Array.from(newDoc.querySelectorAll('script'));
        const external = scripts.filter(s => s.src).map(s => s.src);
        // filter out scripts already present on the page to avoid re-loading globals
        const existingScripts = new Set(Array.from(document.querySelectorAll('script[src]')).map(s => {
            try { return (new URL(s.src, window.location.href)).href; } catch(e) { return s.src; }
        }));
        const filteredExternal = external.filter(src => {
            try { return !existingScripts.has((new URL(src, window.location.href)).href); } catch(e) { return true; }
        });
        const inline = scripts.filter(s => !s.src).map(s => s.textContent);

        function loadScriptSequential(srcList) {
            return srcList.reduce((p, src) => {
                return p.then(() => new Promise((resolve, reject) => {
                    try {
                        const s = document.createElement('script');
                        s.src = src;
                        s.async = false;
                        s.onload = () => resolve();
                        s.onerror = () => {
                            console.error('Failed to load script:', src);
                            // resolve anyway to avoid blocking navigation
                            resolve();
                        };
                        document.body.appendChild(s);
                    } catch (e) { console.error(e); resolve(); }
                }));
            }, Promise.resolve());
        }

        // Debug: log what will be loaded/executed
        try { console.debug('nav-ajax: external scripts to load:', filteredExternal); console.debug('nav-ajax: inline scripts count:', inline.length); } catch(e) {}

        const loadedNowSet = new Set(filteredExternal);
        loadScriptSequential(filteredExternal).then(() => {
            // Append inline scripts after externals are ready
            inline.forEach(txt => {
                try {
                    const s = document.createElement('script');
                    s.textContent = txt;
                    document.body.appendChild(s);
                } catch (e) { console.error('Error injecting inline script', e); }
            });
            // If some external scripts were skipped because they are already
            // present on the page, attempt to call their init function if it
            // follows the `init<PascalName>` convention (e.g. students.js -> initStudents).
            try {
                const skipped = external.filter(src => !loadedNowSet.has(src));
                skipped.forEach(src => {
                    try {
                        const url = new URL(src, window.location.href);
                        const seg = url.pathname.split('/').pop() || '';
                        const base = seg.replace(/\.js$/, '').replace(/[^a-zA-Z0-9]+/g, ' ');
                        const parts = base.split(/\s+/).filter(Boolean).map(p => p.charAt(0).toUpperCase() + p.slice(1));
                        const initName = 'init' + parts.join('');
                        if (typeof window[initName] === 'function') {
                            try { window[initName](); } catch(e) { console.error('Error calling', initName, e); }
                        }
                    } catch(e) { /* ignore */ }
                });
            } catch(e) { console.error(e); }

            // Run any init functions if common.js exposes them (fallback)
            try { if (window.initPage) window.initPage(); } catch(e) { console.error(e); }
            // Ensure dashboard calendar resizes if present
            try { if (window._dashboardCalendar && typeof window._dashboardCalendar.updateSize === 'function') window._dashboardCalendar.updateSize(); } catch(e) {}
            // Initialize Bootstrap tooltips (if Bootstrap is loaded)
            try {
                if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
                    Array.from(document.querySelectorAll('[data-bs-toggle="tooltip"]')).forEach(el => {
                        try { bootstrap.Tooltip.getOrCreateInstance(el); } catch(e) {}
                    });
                }
            } catch(e) {}
            try { document.documentElement.classList.remove('tuition-wait-lastpage'); } catch(e) {}
        }).catch(err => {
            console.error('Error loading external scripts', err);
            // fallback: still try to append inline scripts and init
            inline.forEach(txt => { try { const s=document.createElement('script'); s.textContent=txt; document.body.appendChild(s);}catch(e){}
            });
            try {
                const skipped = external; // best-effort
                skipped.forEach(src => {
                    try {
                        const url = new URL(src, window.location.href);
                        const seg = url.pathname.split('/').pop() || '';
                        const base = seg.replace(/\.js$/, '').replace(/[^a-zA-Z0-9]+/g, ' ');
                        const parts = base.split(/\s+/).filter(Boolean).map(p => p.charAt(0).toUpperCase() + p.slice(1));
                        const initName = 'init' + parts.join('');
                        if (typeof window[initName] === 'function') {
                            try { window[initName](); } catch(e) { console.error('Error calling', initName, e); }
                        }
                    } catch(e) {}
                });
            } catch(e) {}
            try { if (window.initPage) window.initPage(); } catch(e) {}
            try { if (window._dashboardCalendar && typeof window._dashboardCalendar.updateSize === 'function') window._dashboardCalendar.updateSize(); } catch(e) {}
            try {
                if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
                    Array.from(document.querySelectorAll('[data-bs-toggle="tooltip"]')).forEach(el => {
                        try { bootstrap.Tooltip.getOrCreateInstance(el); } catch(e) {}
                    });
                }
            } catch(e) {}
            try { document.documentElement.classList.remove('tuition-wait-lastpage'); } catch(e) {}
        });
        // Scroll to top
        window.scrollTo(0,0);
    }

    function ajaxNavigate(href) {
        fetch(href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                replaceContent(doc);
                // keep the URL as /index.php (no querystring)
                const base = window.location.pathname.replace(/index\.php.*$/, 'index.php');
                history.replaceState(null, '', base);
                // Save last page key (if present in href query param `page`)
                try {
                    const u = new URL(href, window.location.origin);
                    const p = u.searchParams.get('page');
                    if (p) window.localStorage.setItem('lastPage', p);
                } catch (e) {}
                // Update active nav link
                const pageParam = getPageParamFromUrl(href);
                if (pageParam) {
                    document.querySelectorAll('.navbar-nav .nav-link').forEach(a => {
                        try {
                            const ap = new URL(a.href).searchParams.get('page');
                            if (ap === pageParam) a.classList.add('active'); else a.classList.remove('active');
                        } catch(e) {}
                    });
                }
            })
            .catch(err => {
                console.error('Ajax navigation failed', err);
                // fallback to full navigation
                try { document.documentElement.classList.remove('tuition-wait-lastpage'); } catch(e) {}
                window.location.href = href;
            });
    }

    document.addEventListener('click', function(e){
        const a = e.target.closest('a');
        if (!a || !a.href) return;
        // Only intercept same-origin links that have ?page= param
        if (a.target === '_blank' || a.hasAttribute('data-no-ajax')) return;
        const page = getPageParamFromUrl(a.href);
        if (page) {
            e.preventDefault();
            ajaxNavigate(a.href);
        }
    }, true);

    // On initial load, normalize the URL to index.php
    document.addEventListener('DOMContentLoaded', function(){
        // On load, if user had a last visited page key stored, load it via AJAX
        const last = window.localStorage.getItem('lastPage');
        if (last) {
            // load only if current page is login or dashboard (initial landing)
            const currentPage = (new URL(window.location.href)).searchParams.get('page');
            if (!currentPage || currentPage === 'login' || currentPage === 'dashboard') {
                ajaxNavigate('index.php?page=' + encodeURIComponent(last));
            }
        }
        const base = window.location.pathname.replace(/index\.php.*$/, 'index.php');
        history.replaceState(null, '', base);
        // Safety: if replacement did not happen (JS error or network), remove hide-class after timeout
        setTimeout(function(){ try { document.documentElement.classList.remove('tuition-wait-lastpage'); } catch(e) {} }, 5000);
    });
})();
