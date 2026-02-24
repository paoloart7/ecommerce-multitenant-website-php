document.addEventListener('DOMContentLoaded', function () {
    
    // 1. ANIMATION DES CHIFFRES (COUNT UP)
    const counters = document.querySelectorAll('.admin-stat-value');
    const speed = 1000;

    counters.forEach(counter => {
        // ✅ IGNORER LA CARTE BOUTIQUES
        const parentCard = counter.closest('.admin-stat-card');
        const label = parentCard?.querySelector('.admin-stat-label')?.innerText;
        
        if (label === 'Boutiques') {
            return; // Ne pas animer les boutiques
        }
        
        const animate = () => {
            const value = +counter.innerText.replace(/[^0-9]/g, ''); 
            if(isNaN(value)) return; 

            let start = 0;
            const increment = Math.ceil(value / 50);

            const timer = setInterval(() => {
                start += increment;
                if (start >= value) {
                    counter.innerText = new Intl.NumberFormat('fr-FR').format(value);
                    clearInterval(timer);
                } else {
                    counter.innerText = start;
                }
            }, 20);
        };
        animate();
    });

    // 2. TOGGLE SIDEBAR (MOBILE)
    const sidebarToggle = document.getElementById('adminSidebarToggle');
    const sidebar = document.getElementById('adminSidebar');
    const contentOverlay = document.createElement('div');
    
    if (sidebarToggle && sidebar) {
        contentOverlay.className = 'sidebar-overlay';
        document.body.appendChild(contentOverlay);

        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('open');
            contentOverlay.classList.toggle('active');
        });

        contentOverlay.addEventListener('click', () => {
            sidebar.classList.remove('open');
            contentOverlay.classList.remove('active');
        });
    }
    
    // 3. TOGGLE SOUS-MENUS
    document.querySelectorAll('.admin-nav-link.has-children').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const id = this.getAttribute('data-submenu');
            const submenu = document.getElementById('adminSub-' + id);
            
            if (submenu) submenu.classList.toggle('open');
            
            const icon = this.querySelector('.bi-chevron-down');
            if (icon) icon.classList.toggle('rotated');
        });
    });

    // 4. ROTATION FLÈCHE CSS
    const style = document.createElement('style');
    style.innerHTML = `
        .bi-chevron-down.rotated { transform: rotate(180deg); transition: transform 0.2s; }
        .sidebar-overlay {
            position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
            background: rgba(0,0,0,0.5); z-index: 999; display: none;
        }
        .sidebar-overlay.active { display: block; }
    `;
    document.head.appendChild(style);
});