document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ detail-produit.js chargé');
});

// Changer l'image principale
function changeMainImage(src, element) {
    const mainImage = document.getElementById('mainImage');
    if (mainImage) {
        mainImage.src = src;
    }
    
    // Retirer la classe active de toutes les miniatures
    document.querySelectorAll('.miniature').forEach(el => {
        el.classList.remove('active');
    });
    
    // Ajouter la classe active à la miniature cliquée
    if (element) {
        element.classList.add('active');
    }
}

// Changer la quantité
function changeQuantite(delta) {
    const input = document.getElementById('quantite');
    if (!input) return;
    
    let valeur = parseInt(input.value) || 1;
    const min = parseInt(input.min) || 1;
    const max = parseInt(input.max) || 999;
    
    valeur += delta;
    
    if (valeur < min) valeur = min;
    if (valeur > max) valeur = max;
    
    input.value = valeur;
}

// Ajouter au panier avec quantité
window.addToCart = function(id, nom, prix, image, boutique) {
    const quantite = document.getElementById('quantite')?.value || 1;
    const btn = event.currentTarget;
    
    // Animation de chargement
    btn.classList.add('loading');
    btn.innerHTML = '<i class="bi bi-arrow-repeat"></i> Ajout...';
    
    fetch('/ShopXPao/public/api/cart/add', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            id: id,
            quantite: parseInt(quantite),
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
            showNotification(nom + ' (' + quantite + ')');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        btn.classList.remove('loading');
        btn.innerHTML = '<i class="bi bi-cart-plus"></i> Ajouter au panier';
    });
};

// Notification
function showNotification(message) {
    const notification = document.createElement('div');
    notification.className = 'cart-notification';
    notification.innerHTML = `
        <i class="bi bi-check-circle-fill"></i>
        <div>
            <div style="font-weight:700; margin-bottom:4px;">Produit ajouté !</div>
            <div style="font-size:0.85rem; color:#64748b;">${message}</div>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}