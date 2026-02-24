/**
 * My Orders - JavaScript Premium
 */
document.addEventListener('DOMContentLoaded', function() {
    
    // Animation des stats cards
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
    });

    // Confirmation avant action (pour plus tard)
    const actionButtons = document.querySelectorAll('.action-confirm');
    actionButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            if (!confirm('Êtes-vous sûr de vouloir effectuer cette action ?')) {
                e.preventDefault();
            }
        });
    });

    // Highlight de la ligne au survol
    const orderRows = document.querySelectorAll('.order-row');
    orderRows.forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.backgroundColor = '#f8fafc';
        });
        row.addEventListener('mouseleave', function() {
            this.style.backgroundColor = '';
        });
    });

    // Animation des badges
    const badges = document.querySelectorAll('.badge');
    badges.forEach(badge => {
        badge.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.05)';
            this.style.transition = 'transform 0.2s ease';
        });
        badge.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
        });
    });

    // Filtres : désactiver date fin si date début vide
    const dateDebut = document.querySelector('input[name="date_debut"]');
    const dateFin = document.querySelector('input[name="date_fin"]');
    
    if (dateDebut && dateFin) {
        dateDebut.addEventListener('change', function() {
            if (this.value) {
                dateFin.min = this.value;
            } else {
                dateFin.removeAttribute('min');
            }
        });
    }

    // Reset des filtres
    const resetBtn = document.querySelector('.btn-outline-secondary');
    if (resetBtn) {
        resetBtn.addEventListener('click', function(e) {
            // Pas besoin de faire grand chose, le lien href fait le travail
            // Mais on peut ajouter une petite animation
            this.innerHTML = '<i class="bi bi-arrow-repeat"></i> Reset';
        });
    }

    console.log('✅ My Orders JS chargé avec succès');
});