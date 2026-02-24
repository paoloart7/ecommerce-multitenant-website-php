/**
 * tenant-details.js - Premium Animations
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // 1. ANIMATION DES COMPTEURS (FIX NaN)
    const counters = document.querySelectorAll('.stat-info .value');
    
    counters.forEach(counter => {
        // On récupère le texte brut et on enlève tout ce qui n'est pas chiffre/point
        const rawValue = counter.innerText.replace(/[^\d.-]/g, '');
        const target = parseFloat(rawValue) || 0; // Si vide ou NaN, on met 0
        
        counter.setAttribute('data-target', target);
        counter.innerText = '0';

        const updateCount = () => {
            const count = +counter.innerText;
            const inc = target / 100; // Vitesse de l'animation

            if (count < target) {
                counter.innerText = Math.ceil(count + inc);
                setTimeout(updateCount, 15);
            } else {
                // Formatage final propre selon le type
                if(counter.classList.contains('currency')) {
                    counter.innerText = target.toLocaleString() + ' G';
                } else {
                    counter.innerText = target;
                }
            }
        };
        updateCount();
    });

    // 2. ANIMATIONS D'ENTRÉE DES CARTES
    const cards = document.querySelectorAll('.info-card, .stat-card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        setTimeout(() => {
            card.style.transition = 'all 0.5s ease-out';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, 100 * index);
    });
});