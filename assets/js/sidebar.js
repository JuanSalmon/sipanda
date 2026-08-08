document.addEventListener('DOMContentLoaded', () => {
    const sidebar     = document.getElementById('sidebar');
    const backdrop     = document.getElementById('sidebarBackdrop');
    const toggleBtn     = document.getElementById('sidebarToggle');
    const collapseBtn     = document.getElementById('sidebarCollapse');

    // Force closed drawer state on load — mobile CSS shows sidebar only
    // when .is-open is present, but a stale class left over from a prior
    // session could leave it stuck open. Always start closed.
    sidebar.classList.remove('is-open');
    backdrop.classList.remove('is-open');
    toggleBtn.setAttribute('aria-expanded', 'false');

    function openDrawer() {
        sidebar.classList.add('is-open');
        backdrop.classList.add('is-open');
        toggleBtn.setAttribute('aria-expanded', 'true');
    }
    function closeDrawer() {
        sidebar.classList.remove('is-open');
        backdrop.classList.remove('is-open');
        toggleBtn.setAttribute('aria-expanded', 'false');
    }

    toggleBtn.addEventListener('click', () => {
        sidebar.classList.contains('is-open') ? closeDrawer() : openDrawer();
    });
    backdrop.addEventListener('click', closeDrawer);

    // Close drawer whenever a nav link is tapped (mobile UX)
    sidebar.querySelectorAll('.sidebar-link, .sidebar-submenu a').forEach(a => {
        a.addEventListener('click', () => {
            if (window.innerWidth <= 900) closeDrawer();
        });
    });

    // Desktop icon-rail collapse (persisted)
    const COLLAPSE_KEY = 'sipanda_sidebar_collapsed';
    if (localStorage.getItem(COLLAPSE_KEY) === '1' && window.innerWidth > 900) {
        sidebar.classList.add('is-collapsed');
        collapseBtn.setAttribute('aria-expanded', 'false');
    }
    collapseBtn.addEventListener('click', () => {
        const collapsed = sidebar.classList.toggle('is-collapsed');
        collapseBtn.setAttribute('aria-expanded', String(!collapsed));
        localStorage.setItem(COLLAPSE_KEY, collapsed ? '1' : '0');
    });

    // Resize guard: leaving mobile width should drop any open-drawer state
    window.addEventListener('resize', () => {
        if (window.innerWidth > 900) closeDrawer();
    });

    // Collapsible groups (Monitoring / Analisis / Data PTM)
    document.querySelectorAll('.sidebar-group-toggle').forEach(btn => {
        btn.addEventListener('click', () => {
            const group = btn.closest('.sidebar-group');
            const expanded = group.classList.toggle('is-expanded');
            btn.setAttribute('aria-expanded', String(expanded));
        });
    });
});