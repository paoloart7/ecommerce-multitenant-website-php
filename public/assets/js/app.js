document.addEventListener('DOMContentLoaded', function () {
    updateCartCounter();
    // --- ÉLÉMENTS ---
    const btnAllCategories = document.getElementById('btnAllCategories');
    const megaMenu         = document.getElementById('sxMegaMenu');
    const megaOverlay      = document.getElementById('sxMegaOverlay');
    const colPrincipale    = document.getElementById('col-principale');
    const colSecondaire    = document.getElementById('col-secondaire');
    const colTertiaire     = document.getElementById('col-tertiaire');

    if (!btnAllCategories || !megaMenu) return;

    // --- 1. OUVRIR / FERMER ---
    btnAllCategories.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        const isOpen = megaMenu.classList.toggle('show');

        if (isOpen) {
            resetColumn(colSecondaire, 'Sélectionnez une catégorie');
            resetProductGrid(colTertiaire, 'Sélectionnez une sous-catégorie');
            colPrincipale.querySelectorAll('.sx-menu-item').forEach(el => el.classList.remove('active'));
        }
    });

    // --- 2. FERMER AU CLIC EXTÉRIEUR / OVERLAY ---
    document.addEventListener('click', (e) => {
        if (!megaMenu.contains(e.target) && e.target !== btnAllCategories && !btnAllCategories.contains(e.target)) {
            megaMenu.classList.remove('show');
        }
    });

    if (megaOverlay) {
        megaOverlay.addEventListener('click', () => {
            megaMenu.classList.remove('show');
        });
    }

    megaMenu.addEventListener('click', (e) => {
        e.stopPropagation();
    });

    // --- 3. FERMER AVEC ESCAPE ---
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            megaMenu.classList.remove('show');
        }
    });

    // --- FONCTIONS UTILITAIRES ---

    function resetColumn(container, message) {
        if (!container) return;
        container.innerHTML = `
            <li class="sx-menu-empty">
                <i class="bi bi-arrow-left-circle me-2"></i>${message}
            </li>`;
    }

    function resetProductGrid(container, message) {
        if (!container) return;
        container.innerHTML = `
            <div class="sx-products-empty">
                <i class="bi bi-arrow-left-circle me-2"></i>${message}
            </div>`;
    }

    function showLoader(container) {
        if (!container) return;
        container.innerHTML = `
            <li class="sx-menu-loader">
                <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                Chargement...
            </li>`;
    }

    function showGridLoader(container) {
        if (!container) return;
        container.innerHTML = `
            <div class="sx-products-empty">
                <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                Chargement des produits...
            </div>`;
    }
//new

    // --- 4. CHARGEMENT SOUS-CATÉGORIES (Colonne 2) ---
    async function loadSubCategories(parentId, targetContainer) {
        showLoader(targetContainer);
        resetProductGrid(colTertiaire, 'Sélectionnez une sous-catégorie');

        try {
            const response = await fetch(`api/subcategories?parentId=${parentId}`);
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            const data = await response.json();

            targetContainer.innerHTML = '';

            if (!data || data.length === 0) {
                targetContainer.innerHTML = '<li class="sx-menu-empty">Aucune sous-catégorie</li>';
                return;
            }

            data.forEach(cat => {
                const li = document.createElement('li');
                li.className = 'sx-menu-item';
                li.dataset.id = cat.idCategorie;
                
                // ✅ AJOUT DE LA RONDE COLORÉE
                const color = stringToColor(cat.nomCategorie);
                
                li.innerHTML = `
                    <span class="sx-category-dot" style="background: ${color};"></span>
                    <span>${escapeHtml(cat.nomCategorie)}</span>
                    <i class="bi bi-chevron-right"></i>
                `;

                li.addEventListener('click', () => {
                    targetContainer.querySelectorAll('.sx-menu-item').forEach(el => el.classList.remove('active'));
                    li.classList.add('active');
                    loadProducts(cat.idCategorie, colTertiaire);
                });

                targetContainer.appendChild(li);
            });

        } catch (error) {
            console.error('Erreur API subcategories:', error);
            targetContainer.innerHTML = `
                <li class="sx-menu-empty" style="color:#e74c3c">
                    <i class="bi bi-exclamation-triangle me-2"></i>Erreur de chargement
                </li>`;
        }
    }

    // --- 5. CHARGEMENT PRODUITS EN GRILLE (Colonne 3) ---
    async function loadProducts(categoryId, targetContainer) {
        showGridLoader(targetContainer);

        try {
            const response = await fetch(`api/products-by-category?categoryId=${categoryId}`);
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            const products = await response.json();

            targetContainer.innerHTML = '';

            if (!products || products.length === 0) {
                targetContainer.innerHTML = `
                    <div class="sx-products-empty">
                        <i class="bi bi-box-seam me-2"></i>Aucun produit dans cette catégorie
                    </div>`;
                return;
            }

            // Créer la grille
            const grid = document.createElement('div');
            grid.className = 'sx-products-grid';

            products.forEach(product => {
                const card = document.createElement('a');
                card.className = 'sx-product-mini-card';
                card.href = `${product.slugBoutique}/produit/${product.slugProduit}`;

                // Image
                let imageHtml;
                if (product.image) {
                    imageHtml = `<img src="${product.image}" alt="${product.nomProduit}" class="sx-product-mini-img">`;
                } else {
                    imageHtml = `
                        <div class="sx-product-mini-placeholder">
                            <i class="bi bi-image"></i>
                        </div>`;
                }

                // Prix
                let prixHtml;
                if (product.prixPromo && parseFloat(product.prixPromo) > 0) {
                    prixHtml = `
                        <span class="sx-product-mini-promo">${formatPrice(product.prixPromo)}</span>
                        <span class="sx-product-mini-old">${formatPrice(product.prix)}</span>`;
                } else {
                    prixHtml = `<span class="sx-product-mini-price">${formatPrice(product.prix)}</span>`;
                }

                card.innerHTML = `
                    <div class="sx-product-mini-img-wrap">
                        ${imageHtml}
                    </div>
                    <div class="sx-product-mini-info">
                        <div class="sx-product-mini-name">${product.nomProduit}</div>
                        <div class="sx-product-mini-prices">${prixHtml}</div>
                    </div>
                `;

                grid.appendChild(card);
            });

            targetContainer.appendChild(grid);

        } catch (error) {
            console.error('Erreur API products:', error);
            targetContainer.innerHTML = `
                <div class="sx-products-empty" style="color:#e74c3c">
                    <i class="bi bi-exclamation-triangle me-2"></i>Erreur de chargement
                </div>`;
        }
    }

    // Formater le prix
    function formatPrice(price) {
        return parseFloat(price).toLocaleString('fr-FR', {
            style: 'currency',
            currency: 'XOF',
            minimumFractionDigits: 0
        });
    }

    // --- 6. CLIC COLONNE 1 ---
    if (colPrincipale) {
        colPrincipale.addEventListener('click', (e) => {
            const item = e.target.closest('.sx-menu-item');
            if (!item) return;

            colPrincipale.querySelectorAll('.sx-menu-item').forEach(el => el.classList.remove('active'));
            item.classList.add('active');

            const parentId = item.dataset.id;
            loadSubCategories(parentId, colSecondaire);
        });
    }

// ===== FONCTIONS POUR LES RONDES COLORÉES =====

/**
 * Génère une couleur à partir d'une chaîne de caractères
 */
function stringToColor(str) {
    let hash = 0;
    for (let i = 0; i < str.length; i++) {
        hash = str.charCodeAt(i) + ((hash << 5) - hash);
    }
    
    // Palette de couleurs harmonieuses
    const colors = [
        '#04BF9D', '#027373', '#5FCDD9', '#F29F05', '#F28705',
        '#D95B5B', '#4A6FA5', '#B85C5C', '#5D9B79', '#9B6B9B',
        '#E68A56', '#6A8D73', '#A67F68', '#5F9EA0', '#B0A18E'
    ];
    
    // Utiliser le hash pour choisir une couleur dans la palette
    const index = Math.abs(hash) % colors.length;
    return colors[index];
}

/**
 * Échappe les caractères HTML pour la sécurité
 */
function escapeHtml(unsafe) {
    if (!unsafe) return '';
    return unsafe
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

/**
 * Formate le prix (déjà existant mais amélioré)
 */
function formatPrice(price) {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'HTG',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(price).replace('HTG', '').trim() + ' G';
}

// ===== FONCTION AJOUTER AU PANIER =====
// ===== FONCTION AJOUTER AU PANIER =====
window.addToCart = function(id, nom, prix, image, boutique) {

        console.log('🟢 addToCart appelé avec:', {id, nom, prix, image, boutique});

    const btn = event.currentTarget;
    
    // Animation de chargement
    btn.classList.add('loading');
    btn.innerHTML = '<i class="bi bi-arrow-repeat"></i>';
    
    // ✅ Chemin ABSOLU qui a fonctionné dans la console
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
        console.log('Réponse:', data);
        
        if (data.success) {
            // Restaurer le bouton
            btn.classList.remove('loading');
            btn.innerHTML = '<i class="bi bi-cart-plus"></i><span class="btn-text">Ajouter</span>';
            
            // Mettre à jour le compteur
            updateCartCounter();
            
            // Notification
            showNotification(nom);
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        btn.classList.remove('loading');
        btn.innerHTML = '<i class="bi bi-cart-plus"></i><span class="btn-text">Ajouter</span>';
    });
};
// ===== NOTIFICATION =====
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

// ===== METTRE À JOUR LE COMPTEUR =====
function updateCartCounter() {
    fetch('/ShopXPao/public/api/cart/count')
        .then(res => res.json())
        .then(data => {
            const counter = document.querySelector('.cart-counter');
            if (counter) {
                if (data.count > 0) {
                    counter.textContent = data.count;
                    counter.style.display = 'flex';
                } else {
                    counter.style.display = 'none';
                }
            }
        });
}
});