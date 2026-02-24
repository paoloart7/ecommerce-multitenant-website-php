document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ categories.js chargé');
    
    // Animation stats cards
    document.querySelectorAll('.stat-card').forEach((card, i) => {
        card.style.animationDelay = `${i * 0.1}s`;
    });
    
    // ===== GESTION SUPPRESSION AVEC SWEETALERT =====
    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            
            const form = this.closest('.delete-form');
            
            // Vérifier que le formulaire existe
            if (!form) {
                console.error('Formulaire non trouvé');
                return;
            }
            
            const categoryName = form.dataset.name || 'cette catégorie';
            
            Swal.fire({
                title: 'Confirmer la suppression',
                html: `Êtes-vous sûr de vouloir supprimer <strong>${categoryName}</strong> ?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6366f1',
                confirmButtonText: 'Oui, supprimer',
                cancelButtonText: 'Annuler',
                reverseButtons: true,
                showLoaderOnConfirm: true,
                preConfirm: () => {
                    form.submit();
                }
            });
        });
    });
    
    // ===== GESTION DU BOUTON "VOIR SOUS-CATÉGORIES" =====
    document.querySelectorAll('.view-subcategories').forEach(btn => {
        btn.addEventListener('click', function(e) {
            // Le lien fonctionne normalement, pas besoin d'ajouter de JS
            console.log('Redirection vers sous-catégories');
        });
    });
    
    // ===== AUTO-HIDE ALERTS =====
    setTimeout(() => {
        document.querySelectorAll('.alert').forEach(alert => {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.remove();
                }
            }, 500);
        });
    }, 3000);
    
    console.log('✅ Tous les événements sont attachés');
});