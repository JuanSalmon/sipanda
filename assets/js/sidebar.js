// SIPANDA PTM - Sidebar navigation
(function () {
    const sidebar = document.getElementById('sidebar');
    const backdrop = document.getElementById('sidebarBackdrop');
    const toggleBtn = document.getElementById('sidebarToggle');
    const collapseBtn = document.getElementById('sidebarCollapse');
    const navSearch = document.getElementById('sidebarNavSearch');
    if (!sidebar) return;

    const isDesktop = () => !window.matchMedia('(max-width: 900px)').matches;

    function setCollapsed(state) {
        sidebar.classList.toggle('is-collapsed', state);
        collapseBtn?.setAttribute('aria-expanded', String(!state));
        try { localStorage.setItem('sipanda-sidebar-collapsed', state ? '1' : '0'); } catch (e) {}
    }

    collapseBtn?.addEventListener('click', () => {
        setCollapsed(!sidebar.classList.contains('is-collapsed'));
    });

    try {
        if (isDesktop() && localStorage.getItem('sipanda-sidebar-collapsed') === '1') {
            setCollapsed(true);
        }
    } catch (e) {}

    function openSidebar() {
        sidebar.classList.add('is-open');
        backdrop?.classList.add('is-open');
        toggleBtn?.setAttribute('aria-expanded', 'true');
    }

    function closeSidebar() {
        sidebar.classList.remove('is-open');
        backdrop?.classList.remove('is-open');
        toggleBtn?.setAttribute('aria-expanded', 'false');
    }

    toggleBtn?.addEventListener('click', () => {
        sidebar.classList.contains('is-open') ? closeSidebar() : openSidebar();
    });
    backdrop?.addEventListener('click', closeSidebar);

    // Collapsible submenu groups (Monitoring, Analisis, Data PTM)
    sidebar.querySelectorAll('.sidebar-group-toggle').forEach((btn) => {
        btn.addEventListener('click', () => {
            const group = btn.closest('.sidebar-group');
            const isOpen = group.classList.toggle('is-expanded');
            btn.setAttribute('aria-expanded', String(isOpen));
        });
    });

    // Clicking any in-page link: mark active, close drawer on mobile
    sidebar.querySelectorAll('a[data-target]').forEach((link) => {
        link.addEventListener('click', () => {
            sidebar.querySelectorAll('a[data-target]').forEach((l) => l.classList.remove('is-active'));
            link.classList.add('is-active');
            if (window.matchMedia('(max-width: 900px)').matches) closeSidebar();
        });
    });

    // Any other sidebar link (admin, disabled) just closes the drawer on mobile
    sidebar.querySelectorAll('a:not([data-target])').forEach((link) => {
        link.addEventListener('click', () => {
            if (window.matchMedia('(max-width: 900px)').matches) closeSidebar();
        });
    });

    // Expand a collapsed sidebar when the search icon is used
    document.getElementById('sidebarNavSearchIcon')?.addEventListener('click', () => {
        if (sidebar.classList.contains('is-collapsed')) {
            setCollapsed(false);
            navSearch?.focus();
        }
    });

    // Filter nav items (top-level links + submenu links) by typed text
    navSearch?.addEventListener('input', () => {
        const q = navSearch.value.trim().toLowerCase();

        sidebar.querySelectorAll('.sidebar-nav > a[data-target], .sidebar-nav > a.sidebar-link--disabled').forEach((link) => {
            const text = link.textContent.toLowerCase();
            link.hidden = q.length > 0 && !text.includes(q);
        });

        sidebar.querySelectorAll('.sidebar-group').forEach((group) => {
            const subLinks = Array.from(group.querySelectorAll('.sidebar-submenu a'));
            const groupLabel = group.querySelector('.sidebar-group-toggle .sidebar-label')?.textContent.toLowerCase() || '';
            let anySubMatch = false;

            subLinks.forEach((sub) => {
                const match = q.length === 0 || sub.textContent.toLowerCase().includes(q);
                sub.hidden = !match;
                if (match) anySubMatch = true;
            });

            const groupMatch = q.length === 0 || groupLabel.includes(q) || anySubMatch;
            group.hidden = !groupMatch;
            if (q.length > 0 && anySubMatch) group.classList.add('is-expanded');
        });
    });
})();