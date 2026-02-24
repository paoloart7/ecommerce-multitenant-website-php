/**
 * boutique.js - Gestion des interactions sur les pages de boutiques
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ boutique.js chargé');
    
    // Animation des cartes au scroll
    animateCards();
    
    // Gestion des filtres de catégorie (pour mobile)
    setupCategoryFilters();
});

/**
 * Animation des cartes au scroll
 */
function animateCards() {
    const cards = document.querySelectorAll('.boutique-card, .produit-card');
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry, index) => {
            if (entry.isIntersecting) {
                // Animation avec délai progressif
                setTimeout(() => {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }, index * 100);
            }
        });
    }, { threshold: 0.1 });
    
    cards.forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(30px)';
        card.style.transition = 'all 0.5s ease';
        observer.observe(card);
    });
}

/**
 * Configuration des filtres de catégorie pour mobile
 */
function setupCategoryFilters() {
    const filterContainer = document.querySelector('.categories-filtres');
    
    if (!filterContainer) return;
    
    // Ajouter le défilement fluide pour mobile
    let isDown = false;
    let startX;
    let scrollLeft;
    
    filterContainer.addEventListener('mousedown', (e) => {
        isDown = true;
        filterContainer.classList.add('active');
        startX = e.pageX - filterContainer.offsetLeft;
        scrollLeft = filterContainer.scrollLeft;
    });
    
    filterContainer.addEventListener('mouseleave', () => {
        isDown = false;
        filterContainer.classList.remove('active');
    });
    
    filterContainer.addEventListener('mouseup', () => {
        isDown = false;
        filterContainer.classList.remove('active');
    });
    
    filterContainer.addEventListener('mousemove', (e) => {
        if (!isDown) return;
        e.preventDefault();
        const x = e.pageX - filterContainer.offsetLeft;
        const walk = (x - startX) * 2;
        filterContainer.scrollLeft = scrollLeft - walk;
    });
}

// ===== FONCTIONS GLOBALES (réutilisées) =====

/**
 * Ajouter un produit au panier
 */
window.addToCart = function(id, nom, prix, image, boutique) {
    const btn = event.currentTarget;
    
    // Animation de chargement
    btn.classList.add('loading');
    btn.innerHTML = '<i class="bi bi-arrow-repeat"></i> Ajout...';
    
    fetch('/ShopXPao/public/api/cart/add', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            id: id,
            quantite: 1,
            nom: nom,
            prix: prix,
            image: image,
            boutique: boutique
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            // Restaurer le bouton
            btn.classList.remove('loading');
            btn.innerHTML = '<i class="bi bi-cart-plus"></i> Ajouter au panier';
            
            // Mettre à jour le compteur
            if (typeof updateCartCounter === 'function') {
                updateCartCounter();
            }
            
            // Ouvrir le panier
            if (typeof openCart === 'function') {
                openCart();
            }
            
            // Notification
            showNotification(nom);
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        btn.classList.remove('loading');
        btn.innerHTML = '<i class="bi bi-cart-plus"></i> Ajouter au panier';
    });
};

/**
 * Afficher une notification
 */
function showNotification(productName) {
    const notification = document.createElement('div');
    notification.className = 'cart-notification';
    notification.innerHTML = `
        <i class="bi bi-check-circle-fill"></i>
        <div>
            <div style="font-weight:700; margin-bottom:4px;">Produit ajouté !</div>
            <div style="font-size:0.85rem; color:#64748b;">${productName}</div>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// ===== FILTRES EN TEMPS RÉEL (optionnel) =====

/**
 * Filtrer les produits par prix (à implémenter si besoin)
 */
function filterByPrice(min, max) {
    const produits = document.querySelectorAll('.produit-card');
    
    produits.forEach(produit => {
        const prixElement = produit.querySelector('.prix-normal, .prix-promo');
        if (!prixElement) return;
        
        const prix = parseInt(prixElement.textContent.replace(/[^0-9]/g, ''));
        
        if (prix >= min && prix <= max) {
            produit.style.display = 'flex';
        } else {
            produit.style.display = 'none';
        }
    });
}

// ===== CHARGEMENT INFINI (optionnel) =====

/**
 * Chargement infini des produits (si tu veux plus tard)
 */
let currentPage = 1;
let loading = false;

window.addEventListener('scroll', function() {
    const { scrollTop, scrollHeight, clientHeight } = document.documentElement;
    
    if (scrollTop + clientHeight >= scrollHeight - 200 && !loading) {
        // Charger plus de produits
        console.log('Charger plus de produits...');
    }
});