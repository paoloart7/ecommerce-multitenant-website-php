document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ recherche.js chargé');
    
    // Filtrage des résultats
    const filterBtns = document.querySelectorAll('.filter-btn');
    const produitsSection = document.querySelector('.produits-section');
    const boutiquesSection = document.querySelector('.boutiques-section');
    
    filterBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            filterBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            const filter = this.dataset.filter;
            
            if (produitsSection && boutiquesSection) {
                switch(filter) {
                    case 'all':
                        produitsSection.style.display = 'block';
                        boutiquesSection.style.display = 'block';
                        break;
                    case 'produits':
                        produitsSection.style.display = 'block';
                        boutiquesSection.style.display = 'none';
                        break;
                    case 'boutiques':
                        produitsSection.style.display = 'none';
                        boutiquesSection.style.display = 'block';
                        break;
                }
            }
        });
    });
    
    // ✅ FONCTION AJOUTER AU PANIER
    window.addToCart = function(productId, productName) {
        const btn = event.currentTarget;
        
        // Ajouter la classe loading
        btn.classList.add('loading');
        btn.innerHTML = '<i class="bi bi-arrow-repeat"></i>';
        
        // Simuler un appel AJAX (à remplacer par vrai appel)
        setTimeout(() => {
            // Enlever la classe loading
            btn.classList.remove('loading');
            btn.innerHTML = '<i class="bi bi-cart-plus"></i><span class="btn-text">Ajouter</span>';
            
            // Afficher la notification
            showNotification(productName);
        }, 500);
    };
    
    // ✅ FONCTION NOTIFICATION
    function showNotification(productName) {
        // Supprimer les anciennes notifications
        const oldNotifications = document.querySelectorAll('.cart-notification');
        oldNotifications.forEach(n => n.remove());
        
        // Créer la notification
        const notification = document.createElement('div');
        notification.className = 'cart-notification success';
        notification.innerHTML = `
            <i class="bi bi-check-circle-fill"></i>
            <div class="cart-notification-content">
                <div class="cart-notification-title">Produit ajouté !</div>
                <div class="cart-notification-text">${productName} a été ajouté à votre panier</div>
            </div>
            <button class="cart-notification-close" onclick="this.parentElement.remove()">
                <i class="bi bi-x"></i>
            </button>
        `;
        
        document.body.appendChild(notification);
        
        // Auto-disparition après 3 secondes
        setTimeout(() => {
            notification.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }
});