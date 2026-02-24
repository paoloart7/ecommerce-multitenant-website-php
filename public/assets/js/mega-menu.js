/**
 * Mega Menu - Gestion des catégories et produits
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ mega-menu.js chargé');
    
    // Éléments du DOM
    const btnAllCategories = document.getElementById('btnAllCategories');
    const megaMenu = document.getElementById('sxMegaMenu');
    const overlay = document.getElementById('sxMegaOverlay');
    
    // ===== OUVERTURE/FERMETURE DU MENU =====
    if (btnAllCategories && megaMenu && overlay) {
        btnAllCategories.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            megaMenu.classList.toggle('show');
            overlay.classList.toggle('show');
            
            // Changer la direction de la flèche
            const chevron = this.querySelector('.sx-chevron');
            if (chevron) {
                chevron.style.transform = megaMenu.classList.contains('show') ? 'rotate(180deg)' : '';
            }
        });
        
        // Fermer avec l'overlay
        overlay.addEventListener('click', function() {
            megaMenu.classList.remove('show');
            overlay.classList.remove('show');
            const chevron = btnAllCategories.querySelector('.sx-chevron');
            if (chevron) chevron.style.transform = '';
        });
        
        // Fermer avec la touche Echap
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && megaMenu.classList.contains('show')) {
                megaMenu.classList.remove('show');
                overlay.classList.remove('show');
                const chevron = btnAllCategories.querySelector('.sx-chevron');
                if (chevron) chevron.style.transform = '';
            }
        });
    }
    
    // ===== GESTION DES CATÉGORIES PRINCIPALES =====
    const catItems = document.querySelectorAll('#col-principale .sx-menu-item');
    const subList = document.getElementById('col-secondaire');
    const productsContainer = document.getElementById('col-tertiaire');
    
    if (catItems.length > 0) {
        catItems.forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const catId = this.dataset.id;
                const catName = this.querySelector('span:not(.sx-category-dot)')?.textContent || '';
                
                console.log('Catégorie sélectionnée:', catId, catName);
                
                // Enlever la classe active de tous les items
                catItems.forEach(i => i.classList.remove('active'));
                
                // Ajouter la classe active à l'item cliqué
                this.classList.add('active');
                
                // Afficher le chargement
                if (subList) {
                    subList.innerHTML = '<li class="sx-menu-empty sx-loading"><i class="bi bi-arrow-repeat me-2"></i>Chargement...</li>';
                }
                if (productsContainer) {
                    productsContainer.innerHTML = '<div class="sx-products-empty sx-loading"><i class="bi bi-arrow-repeat me-2"></i>Chargement des produits...</div>';
                }
                
                // Charger les sous-catégories via AJAX
                loadSubCategories(catId);
            });
        });
    }
    
    // ===== FONCTION POUR CHARGER LES SOUS-CATÉGORIES =====
    function loadSubCategories(catId) {
        // Construire l'URL avec le bon paramètre (parentId)
 //     const url = `<?= App::baseUrl() ?>/api/subcategories?parentId=${catId}`;
        const url = `${BASE_URL}/api/subcategories?parentId=${catId}`;
        fetch(url)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erreur réseau');
                }
                return response.json();
            })
            .then(data => {
                console.log('Sous-catégories chargées:', data);
                
                if (data && data.length > 0) {
                    let html = '';
                    data.forEach(sub => {
                        // Générer une couleur basée sur le nom
                        const color = stringToColor(sub.nomCategorie);
                        html += `
                            <li class="sx-menu-item sub-item" data-id="${sub.idCategorie}" data-parent="${catId}">
                                <span class="sx-category-dot" style="background: ${color};"></span>
                                <span>${escapeHtml(sub.nomCategorie)}</span>
                                <i class="bi bi-chevron-right"></i>
                            </li>
                        `;
                    });
                    if (subList) subList.innerHTML = html;
                    
                    // Attacher les événements aux sous-catégories
                    attachSubCategoryEvents();
                } else {
                    if (subList) {
                        subList.innerHTML = '<li class="sx-menu-empty"><i class="bi bi-info-circle me-2"></i>Aucune sous-catégorie</li>';
                    }
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                if (subList) {
                    subList.innerHTML = '<li class="sx-menu-empty"><i class="bi bi-exclamation-circle me-2"></i>Erreur de chargement</li>';
                }
            });
    }
    
    // ===== FONCTION POUR ATTACHER LES ÉVÉNEMENTS AUX SOUS-CATÉGORIES =====
    function attachSubCategoryEvents() {
        const subItems = document.querySelectorAll('#col-secondaire .sub-item');
        
        subItems.forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const subId = this.dataset.id;
                const catId = this.dataset.parent;
                const subName = this.querySelector('span:not(.sx-category-dot)')?.textContent || '';
                
                console.log('Sous-catégorie sélectionnée:', subId, subName);
                
                // Enlever la classe active de tous les sous-items
                subItems.forEach(i => i.classList.remove('active'));
                
                // Ajouter la classe active
                this.classList.add('active');
                
                // Afficher le chargement
                if (productsContainer) {
                    productsContainer.innerHTML = '<div class="sx-products-empty sx-loading"><i class="bi bi-arrow-repeat me-2"></i>Chargement des produits...</div>';
                }
                
                // Charger les produits via AJAX
                loadProducts(subId);
            });
        });
    }
    
    // ===== FONCTION POUR CHARGER LES PRODUITS =====

function loadProducts(subId) {
    const url = `${BASE_URL}/api/products-by-category?categoryId=${subId}`;
    
    fetch(url)
        .then(response => {
            if (!response.ok) {
                throw new Error('Erreur réseau');
            }
            return response.json();
        })
        .then(products => {
            console.log('Produits chargés:', products);
            
            if (products && products.length > 0) {
                let html = '<div class="sx-products-grid">';
                products.forEach(prod => {
                    // ✅ CORRIGÉ
                    const productUrl = `${BASE_URL}/boutique/${prod.slugBoutique}/produit/${prod.idProduit}`;
                    
                    // ✅ CORRIGÉ
                    const imageHtml = prod.image 
                        ? `<img src="${BASE_URL}${prod.image}" class="sx-product-mini-img" alt="${escapeHtml(prod.nomProduit)}">`
                        : `<div class="sx-product-mini-placeholder"><i class="bi bi-image"></i></div>`;
                    
                    const priceHtml = prod.prixPromo 
                        ? `<span class="sx-product-mini-promo">${formatNumber(prod.prixPromo)} G</span>
                           <span class="sx-product-mini-old">${formatNumber(prod.prix)} G</span>`
                        : `<span class="sx-product-mini-price">${formatNumber(prod.prix)} G</span>`;
                    
                    html += `
                        <a href="${productUrl}" class="sx-product-mini-card">
                            <div class="sx-product-mini-img-wrap">
                                ${imageHtml}
                            </div>
                            <div class="sx-product-mini-info">
                                <div class="sx-product-mini-name">${escapeHtml(prod.nomProduit)}</div>
                                <div class="sx-product-mini-prices">
                                    ${priceHtml}
                                </div>
                            </div>
                        </a>
                    `;
                });
                html += '</div>';
                if (productsContainer) productsContainer.innerHTML = html;
            } else {
                if (productsContainer) {
                    productsContainer.innerHTML = '<div class="sx-products-empty"><i class="bi bi-box-seam me-2"></i>Aucun produit dans cette catégorie</div>';
                }
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            if (productsContainer) {
                productsContainer.innerHTML = '<div class="sx-products-empty"><i class="bi bi-exclamation-circle me-2"></i>Erreur de chargement</div>';
            }
        });
}
    
    // ===== FONCTIONS UTILITAIRES =====
    function stringToColor(str) {
        let hash = 0;
        for (let i = 0; i < str.length; i++) {
            hash = str.charCodeAt(i) + ((hash << 5) - hash);
        }
        const color = Math.floor(Math.abs(Math.sin(hash) * 16777215) % 16777215).toString(16);
        return '#' + '0'.repeat(6 - color.length) + color;
    }
    
    function escapeHtml(unsafe) {
        if (!unsafe) return '';
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
    
    function formatNumber(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
    }
    
    // ===== FERMER LE MENU QUAND ON CLIQUE AILLEURS =====
    document.addEventListener('click', function(e) {
        if (megaMenu && megaMenu.classList.contains('show')) {
            if (!megaMenu.contains(e.target) && !btnAllCategories.contains(e.target)) {
                megaMenu.classList.remove('show');
                if (overlay) overlay.classList.remove('show');
                const chevron = btnAllCategories?.querySelector('.sx-chevron');
                if (chevron) chevron.style.transform = '';
            }
        }
    });
});