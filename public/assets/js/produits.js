/**
 * produits.js - Premium Interactions
 */
document.addEventListener('DOMContentLoaded', function() {
    
    // 1. ANIMATION DES LIGNES (Cascade)
    const rows = document.querySelectorAll('.table-premium tbody tr');
    rows.forEach((row, index) => {
        row.style.opacity = '0';
        row.style.transform = 'translateY(10px)';
        setTimeout(() => {
            row.style.transition = 'all 0.4s ease-out';
            row.style.opacity = '1';
            row.style.transform = 'translateY(0)';
        }, index * 40);
    });

    // 2. EFFET SUR LES BOUTONS VEDETTE
    const starBtns = document.querySelectorAll('.btn-star');
    starBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const icon = this.querySelector('i');
            if(this.classList.contains('is-featured')) {
                icon.className = 'bi bi-star';
            } else {
                icon.className = 'bi bi-star-fill';
                // Petit effet de confettis ou flash ici si tu veux
            }
        });
    });

function viewProduct(id) {
    // On récupère le baseUrl dynamiquement
    const baseUrl = window.location.origin + "/ShopXPao/public";
    
    fetch(`${baseUrl}/admin/product/details-json?id=${id}`)
        .then(response => {
            if (!response.ok) throw new Error('Erreur 404 : Route non trouvée');
            return response.json();
        })
        .then(p => {
            // Remplissage de la modale
            document.getElementById('m-title').innerText = p.nomProduit;
            document.getElementById('m-cat').innerText = p.nomCategorie || 'Général';
            document.getElementById('m-shop').innerText = '@' + p.nomBoutique;
            document.getElementById('m-price').innerText = new Intl.NumberFormat().format(p.prix) + ' G';
            document.getElementById('m-desc').innerText = p.descriptionComplete || p.descriptionCourte || 'Pas de description.';
            document.getElementById('m-date').innerText = new Date(p.dateAjout).toLocaleDateString();
            document.getElementById('m-stock').innerText = p.stock + ' unités';
            document.getElementById('m-sku').innerText = p.sku || 'N/A';
            document.getElementById('m-featured').innerText = p.misEnAvant == 1 ? 'OUI' : 'NON';

            const mainImg = document.getElementById('m-img');
            // On nettoie l'erreur précédente au cas où
            mainImg.onerror = null; 

            const imgPath = p.image_principale ? `${baseUrl}/${p.image_principale}` : `${baseUrl}/assets/images/default-product.png`;

            mainImg.onerror = function() {
                this.onerror = null; // Stoppe la boucle si l'image par défaut échoue aussi
                this.src = baseUrl + '/assets/images/default-product.png';
            };

            mainImg.src = imgPath;

            // Statut
            const statusBadge = document.getElementById('m-status');
            statusBadge.innerText = p.statutProduit.toUpperCase();
            statusBadge.className = 'badge ' + (p.statutProduit === 'disponible' ? 'bg-success' : 'bg-danger');

            // Ouvrir la modale
            const modalEl = document.getElementById('modalViewProduct');
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
        })
        .catch(err => {
            console.error(err);
            alert("Erreur lors du chargement des détails : " + err.message);
        });
}


});