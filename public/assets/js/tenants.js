/**
 * tenants.js - Animation & Interactivity
 */

// Au tout début du fichier
console.log('🟢 tenants.js chargé');

document.addEventListener('DOMContentLoaded', function() {
    
    // Log des valeurs initiales
    console.log('Valeurs initiales:', {
        total: document.querySelector('.stat-info .value')?.innerText,
        actives: document.querySelectorAll('.stat-info .value')[1]?.innerText,
        enAttente: document.querySelectorAll('.stat-info .value')[2]?.innerText,
        suspendues: document.querySelectorAll('.stat-info .value')[3]?.innerText
    });
    
    // 1. ANIMATION DES COMPTEURS (Count Up) - OPTIMISÉ
    const counters = document.querySelectorAll('.stat-info .value');
    const speed = 30; // Plus rapide pour éviter le scintillement

    counters.forEach(counter => {
        // ✅ Sauvegarder la vraie valeur
        const trueValue = parseInt(counter.innerText) || 0;
        
        // Ne pas animer si la valeur est 0
        if (trueValue === 0) {
            return;
        }
        
        // Stocker la vraie valeur dans data-target
        counter.setAttribute('data-target', trueValue);
        counter.innerText = '0';

        const updateCount = () => {
            const target = +counter.getAttribute('data-target');
            const count = +counter.innerText;
            
            // Calcul dynamique de l'incrément
            const steps = 20; // Nombre d'étapes
            const inc = Math.ceil(target / steps);
            
            if (count < target) {
                const newValue = Math.min(count + inc, target);
                counter.innerText = newValue;
                setTimeout(updateCount, 15);
            } else {
                counter.innerText = target;
            }
        };
        
        // Petit délai avant de commencer l'animation
        setTimeout(updateCount, 100);
    });

    // 2. EFFET SUR LA RECHERCHE
    const searchInput = document.querySelector('.search-input');
    const searchIcon = document.querySelector('.search-icon i');

    if(searchInput) {
        searchInput.addEventListener('focus', () => {
            searchIcon.classList.remove('bi-search');
            searchIcon.classList.add('bi-search-heart-fill');
        });

        searchInput.addEventListener('blur', () => {
            searchIcon.classList.remove('bi-search-heart-fill');
            searchIcon.classList.add('bi-search');
        });
    }

    // 3. EFFET DE CLIC SUR LES LIGNES
    const rows = document.querySelectorAll('.clickable-row');
    rows.forEach(row => {
        row.addEventListener('click', function(e) {
            this.style.transform = 'scale(0.98)';
            this.style.transition = 'transform 0.1s';
            
            const href = this.getAttribute('data-href');
            setTimeout(() => {
                window.location.href = href;
            }, 150);
        });
    });
});