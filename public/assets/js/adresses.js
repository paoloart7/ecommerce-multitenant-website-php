/**
 * adresses.js - Gestion des adresses client
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ adresses.js chargé');
    
    // Initialiser la modal Bootstrap
    const modalElement = document.getElementById('addressModal');
    if (modalElement) {
        window.addressModal = new bootstrap.Modal(modalElement);
    }
});

// Ouvrir la modal pour ajouter une adresse
window.openAddressModal = function() {
    // Réinitialiser le formulaire
    document.getElementById('addressForm').reset();
    document.getElementById('addressId').value = '';
    document.getElementById('modalTitle').textContent = 'Ajouter une adresse';
    
    // Ouvrir la modal
    if (window.addressModal) {
        window.addressModal.show();
    }
};

// Éditer une adresse existante
window.editAddress = function(address) {
    // Remplir le formulaire
    document.getElementById('addressId').value = address.idAdresse || '';
    document.getElementById('nomDestinataire').value = address.nomDestinataire || '';
    document.getElementById('telephone').value = address.telephone || '';
    document.getElementById('rue').value = address.rue || '';
    document.getElementById('complement').value = address.complement || '';
    document.getElementById('quartier').value = address.quartier || '';
    document.getElementById('ville').value = address.ville || '';
    document.getElementById('codePostal').value = address.codePostal || '';
    document.getElementById('estDefaut').checked = address.estDefaut == 1;
    
    // Changer le titre
    document.getElementById('modalTitle').textContent = 'Modifier l\'adresse';
    
    // Ouvrir la modal
    if (window.addressModal) {
        window.addressModal.show();
    }
};

// Confirmer la suppression
window.confirmDelete = function(button) {
    Swal.fire({
        title: 'Confirmer la suppression',
        text: 'Cette action est irréversible.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Oui, supprimer',
        cancelButtonText: 'Annuler'
    }).then((result) => {
        if (result.isConfirmed) {
            // Soumettre le formulaire
            button.closest('form').submit();
        }
    });
};

// Validation du formulaire avant soumission
document.addEventListener('DOMContentLoaded', function() {
    const addressForm = document.getElementById('addressForm');
    if (addressForm) {
        addressForm.addEventListener('submit', function(e) {
            const rue = document.getElementById('rue').value.trim();
            const ville = document.getElementById('ville').value.trim();
            
            if (!rue || !ville) {
                e.preventDefault();
                Swal.fire({
                    title: 'Champs requis',
                    text: 'La rue et la ville sont obligatoires.',
                    icon: 'error',
                    confirmButtonColor: '#04BF9D'
                });
            }
        });
    }
});

// Fermer la modal avec Echap
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && window.addressModal) {
        window.addressModal.hide();
    }
});