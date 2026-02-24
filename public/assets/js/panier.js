// Mettre à jour la quantité
window.updateQuantite = function(id, delta) {
    const qteSpan = document.getElementById('qte-' + id);
    const itemDiv = document.querySelector(`.panier-item[data-id="${id}"]`);
    let nouvelleQte = parseInt(qteSpan.textContent) + delta;
    
    if (nouvelleQte < 1) return;
    
    // Animation de chargement
    itemDiv.style.opacity = '0.6';
    
    fetch('/ShopXPao/public/panier/update', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'id=' + id + '&quantite=' + nouvelleQte
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            // Mettre à jour l'affichage
            qteSpan.textContent = nouvelleQte;
            
            // Mettre à jour le total de l'article
            const prix = parseFloat(itemDiv.querySelector('.item-prix').textContent.replace(/[^0-9]/g, ''));
            const itemTotal = itemDiv.querySelector('.item-total');
            itemTotal.textContent = formatPrice(prix * nouvelleQte);
            
            // Mettre à jour les totaux globaux
            updateTotaux(data.total, data.taxe, data.remise, data.livraison);
            
            // Mettre à jour le compteur du navbar
            updateCartCounter(data.count);
        }
        itemDiv.style.opacity = '1';
    })
    .catch(error => {
        console.error('Erreur:', error);
        itemDiv.style.opacity = '1';
    });
};

// Supprimer un article
window.removeItem = function(id) {
    if (!confirm('Supprimer cet article du panier ?')) return;
    
    const item = document.querySelector(`.panier-item[data-id="${id}"]`);
    item.style.opacity = '0.6';
    
    fetch('/ShopXPao/public/panier/remove', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'id=' + id
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            item.remove();
            updateTotaux(data.total, data.taxe, data.remise, data.livraison);
            updateCartCounter(data.count);
            
            // Si plus d'articles, recharger pour afficher le panier vide
            if (data.count === 0) {
                setTimeout(() => location.reload(), 500);
            }
        }
    });
};

// Vider le panier
window.viderPanier = function() {
    if (!confirm('Vider tout le panier ?')) return;
    
    fetch('/ShopXPao/public/panier/clear', {
        method: 'POST'
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    });
};

// Mettre à jour les totaux avec taxes et remises
function updateTotaux(sousTotal, taxe = 10, remise = 0, livraison = 0) {
    // Formater les nombres
    const sousTotalNum = parseFloat(sousTotal) || 0;
    const taxeNum = parseFloat(taxe) || 0;
    const remiseNum = parseFloat(remise) || 0;
    const livraisonNum = parseFloat(livraison) || 0;
    
    // Calculs
    const montantTaxe = sousTotalNum * (taxeNum / 100);
    const total = sousTotalNum + montantTaxe + livraisonNum - remiseNum;
    
    // Mettre à jour l'affichage
    document.getElementById('sous-total').textContent = formatPrice(sousTotalNum);
    document.getElementById('montant-taxe').textContent = formatPrice(montantTaxe);
    document.getElementById('remise').textContent = formatPrice(remiseNum);
    document.getElementById('livraison').textContent = formatPrice(livraisonNum);
    document.getElementById('total-general').textContent = formatPrice(total);
}

// Formater le prix
function formatPrice(price) {
    return Math.round(price).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' G';
}

// Passer la commande
window.checkout = function() {
    window.location.href = '/ShopXPao/public/paiement/choix';
};

// Mettre à jour le compteur du navbar
function updateCartCounter(count) {
    const counter = document.querySelector('.cart-counter');
    if (counter) {
        if (count > 0) {
            counter.textContent = count;
            counter.style.display = 'flex';
        } else {
            counter.style.display = 'none';
        }
    }
}