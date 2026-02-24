/**
 * tenant-commandes.js - Gestion des actions sur les commandes
 */

function showMessage(type, message) {
    const notification = document.createElement('div');
    notification.className = `notification-premium notification-${type}`;
    notification.innerHTML = `
        <div class="notification-icon">
            <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}"></i>
        </div>
        <div class="notification-content">
            <div class="notification-title">${type === 'success' ? 'Succès' : 'Erreur'}</div>
            <div class="notification-message">${message}</div>
        </div>
        <button class="notification-close" onclick="this.parentElement.remove()">
            <i class="bi bi-x"></i>
        </button>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

function showLoading(btn) {
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-arrow-repeat"></i> Chargement...';
}

function hideLoading(btn, originalText) {
    btn.disabled = false;
    btn.innerHTML = originalText;
}

function changerStatut(nouveauStatut) {
    const urlParams = new URLSearchParams(window.location.search);
    const idCommande = urlParams.get('id');
    
    if (!idCommande) {
        showMessage('error', 'ID commande non trouvé');
        return;
    }
    
    const btn = event.currentTarget;
    const originalText = btn.innerHTML;
    
    // Notification de confirmation personnalisée
    showConfirmation(
        'Changer le statut',
        `Voulez-vous vraiment passer cette commande en "${nouveauStatut}" ?`,
        () => {
            showLoading(btn);
            
            fetch('/ShopXPao/public/vendeur/commande/update-statut', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'id=' + encodeURIComponent(idCommande) + '&statut=' + encodeURIComponent(nouveauStatut)
            })
            .then(async response => {
                if (!response.ok) {
                    const text = await response.text();
                    throw new Error(`HTTP ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                hideLoading(btn, originalText);
                
                if (data.success) {
                    showMessage('success', 'Statut mis à jour avec succès !');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showMessage('error', data.error || 'Erreur lors de la mise à jour');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                hideLoading(btn, originalText);
                showMessage('error', 'Erreur de communication avec le serveur');
            });
        }
    );
}

function annulerCommande() {
    const urlParams = new URLSearchParams(window.location.search);
    const idCommande = urlParams.get('id');
    
    if (!idCommande) {
        showMessage('error', 'ID commande non trouvé');
        return;
    }
    
    const btn = event.currentTarget;
    const originalText = btn.innerHTML;
    
    showConfirmation(
        'Annuler la commande',
        'Cette action restaurera le stock. Êtes-vous sûr ?',
        () => {
            showLoading(btn);
            
            fetch('/ShopXPao/public/vendeur/commande/annuler', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'id=' + encodeURIComponent(idCommande)
            })
            .then(async response => {
                if (!response.ok) {
                    const text = await response.text();
                    throw new Error(`HTTP ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                hideLoading(btn, originalText);
                
                if (data.success) {
                    showMessage('success', 'Commande annulée avec succès !');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showMessage('error', data.error || 'Erreur lors de l\'annulation');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                hideLoading(btn, originalText);
                showMessage('error', 'Erreur de communication avec le serveur');
            });
        }
    );
}

function showConfirmation(title, message, onConfirm) {
    // Créer l'overlay
    const overlay = document.createElement('div');
    overlay.className = 'confirm-overlay';
    
    // Créer la modal
    const modal = document.createElement('div');
    modal.className = 'confirm-modal';
    modal.innerHTML = `
        <div class="confirm-header">
            <i class="bi bi-question-circle"></i>
            <h3>${title}</h3>
        </div>
        <div class="confirm-body">
            <p>${message}</p>
        </div>
        <div class="confirm-footer">
            <button class="btn-confirm-cancel">Annuler</button>
            <button class="btn-confirm-ok">Confirmer</button>
        </div>
    `;
    
    overlay.appendChild(modal);
    document.body.appendChild(overlay);
    
    // Gestionnaires
    modal.querySelector('.btn-confirm-cancel').addEventListener('click', () => {
        overlay.remove();
    });
    
    modal.querySelector('.btn-confirm-ok').addEventListener('click', () => {
        overlay.remove();
        onConfirm();
    });
    
    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) {
            overlay.remove();
        }
    });
}

// CSS pour la confirmation
const style = document.createElement('style');
style.innerHTML = `
    .notification-premium {
        position: fixed;
        top: 20px;
        right: 20px;
        background: white;
        border-radius: 16px;
        padding: 1rem 1.2rem;
        box-shadow: 0 20px 35px -8px rgba(0,0,0,0.2);
        display: flex;
        align-items: center;
        gap: 1rem;
        z-index: 9999;
        animation: slideInRight 0.3s ease;
        border-left: 4px solid;
        min-width: 300px;
        max-width: 400px;
    }
    
    .notification-premium.notification-success {
        border-left-color: #04BF9D;
    }
    
    .notification-premium.notification-error {
        border-left-color: #ef4444;
    }
    
    .notification-icon i {
        font-size: 1.8rem;
    }
    
    .notification-success .notification-icon i {
        color: #04BF9D;
    }
    
    .notification-error .notification-icon i {
        color: #ef4444;
    }
    
    .notification-content {
        flex: 1;
    }
    
    .notification-title {
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 0.2rem;
    }
    
    .notification-message {
        font-size: 0.85rem;
        color: #64748b;
    }
    
    .notification-close {
        background: transparent;
        border: none;
        color: #94a3b8;
        cursor: pointer;
        padding: 0.25rem;
        transition: color 0.2s;
    }
    
    .notification-close:hover {
        color: #1e293b;
    }
    
    .confirm-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(4px);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10000;
        animation: fadeIn 0.2s ease;
    }
    
    .confirm-modal {
        background: white;
        border-radius: 24px;
        padding: 2rem;
        max-width: 400px;
        width: 90%;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        animation: slideUp 0.3s ease;
    }
    
    .confirm-header {
        text-align: center;
        margin-bottom: 1.5rem;
    }
    
    .confirm-header i {
        font-size: 3rem;
        color: #04BF9D;
        margin-bottom: 1rem;
    }
    
    .confirm-header h3 {
        font-size: 1.3rem;
        font-weight: 700;
        color: #1e293b;
        margin: 0;
    }
    
    .confirm-body {
        text-align: center;
        margin-bottom: 2rem;
    }
    
    .confirm-body p {
        color: #64748b;
        font-size: 1rem;
        line-height: 1.5;
    }
    
    .confirm-footer {
        display: flex;
        gap: 1rem;
    }
    
    .btn-confirm-cancel, .btn-confirm-ok {
        flex: 1;
        padding: 0.75rem;
        border-radius: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    .btn-confirm-cancel {
        background: #f1f5f9;
        border: 2px solid #e2e8f0;
        color: #64748b;
    }
    
    .btn-confirm-cancel:hover {
        background: #e2e8f0;
        color: #1e293b;
    }
    
    .btn-confirm-ok {
        background: linear-gradient(135deg, #04BF9D, #027373);
        border: none;
        color: white;
    }
    
    .btn-confirm-ok:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px -5px rgba(4, 191, 157, 0.4);
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);