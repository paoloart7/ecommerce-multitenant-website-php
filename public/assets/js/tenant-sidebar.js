document.addEventListener('DOMContentLoaded', function () {

    // ═══ 1. TOGGLE SIDEBAR MOBILE ═══
    const sidebar = document.getElementById('tenantSidebar');
    const toggleBtn = document.getElementById('tenantSidebarToggle');

    // Créer l'overlay
    let overlay = document.querySelector('.tenant-sidebar-overlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.className = 'tenant-sidebar-overlay';
        document.body.appendChild(overlay);
    }

    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', function () {
            sidebar.classList.toggle('open');
            overlay.classList.toggle('active');
        });
    }

    // Fermer au clic sur l'overlay
    overlay.addEventListener('click', function () {
        sidebar.classList.remove('open');
        overlay.classList.remove('active');
    });

    // Fermer avec Escape
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && sidebar.classList.contains('open')) {
            sidebar.classList.remove('open');
            overlay.classList.remove('active');
        }
    });

    // ═══ 2. FERMER SIDEBAR AU CLIC SUR LIEN (mobile) ═══
    if (window.innerWidth <= 991) {
        document.querySelectorAll('.tenant-nav-link:not(.has-children), .tenant-sub-link').forEach(function (link) {
            link.addEventListener('click', function () {
                setTimeout(function () {
                    sidebar.classList.remove('open');
                    overlay.classList.remove('active');
                }, 150);
            });
        });
    }

    // ═══ 3. ACTIVE LINK INDICATOR ═══
    // Petit effet visuel au chargement sur le lien actif
    const activeLink = document.querySelector('.tenant-nav-link.active');
    if (activeLink) {
        activeLink.style.transition = 'none';
        activeLink.style.opacity = '0';
        requestAnimationFrame(function () {
            requestAnimationFrame(function () {
                activeLink.style.transition = 'opacity 0.4s ease, background 0.25s ease';
                activeLink.style.opacity = '1';
            });
        });
    }

    // ═══ 4. SOUS-MENU ACTIVE STATE ═══
    const activeSub = document.querySelector('.tenant-sub-link.active');
    if (activeSub) {
        activeSub.style.transition = 'none';
        activeSub.style.opacity = '0';
        requestAnimationFrame(function () {
            requestAnimationFrame(function () {
                activeSub.style.transition = 'opacity 0.5s ease';
                activeSub.style.opacity = '1';
            });
        });
    }
});