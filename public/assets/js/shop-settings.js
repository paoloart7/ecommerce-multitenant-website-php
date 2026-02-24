document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ shop-settings.js chargé');
    
    // ===== UPLOAD LOGO =====
    const logoArea = document.getElementById('logoUploadArea');
    const logoInput = document.getElementById('logoInput');
    const logoPreview = document.getElementById('logoPreview');
    
    if (logoArea && logoInput) {
        logoArea.addEventListener('click', () => logoInput.click());
        
        logoArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            logoArea.classList.add('dragover');
        });
        
        logoArea.addEventListener('dragleave', () => {
            logoArea.classList.remove('dragover');
        });
        
        logoArea.addEventListener('drop', (e) => {
            e.preventDefault();
            logoArea.classList.remove('dragover');
            if (e.dataTransfer.files.length) {
                uploadLogo(e.dataTransfer.files[0]);
            }
        });
        
        logoInput.addEventListener('change', (e) => {
            if (e.target.files.length) {
                uploadLogo(e.target.files[0]);
            }
        });
    }
    
    // ===== UPLOAD BANNIÈRE =====
    const bannerArea = document.getElementById('bannerUploadArea');
    const bannerInput = document.getElementById('bannerInput');
    const bannerPreview = document.getElementById('bannerPreview');
    
    if (bannerArea && bannerInput) {
        bannerArea.addEventListener('click', () => bannerInput.click());
        
        bannerArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            bannerArea.classList.add('dragover');
        });
        
        bannerArea.addEventListener('dragleave', () => {
            bannerArea.classList.remove('dragover');
        });
        
        bannerArea.addEventListener('drop', (e) => {
            e.preventDefault();
            bannerArea.classList.remove('dragover');
            if (e.dataTransfer.files.length) {
                uploadBanner(e.dataTransfer.files[0]);
            }
        });
        
        bannerInput.addEventListener('change', (e) => {
            if (e.target.files.length) {
                uploadBanner(e.target.files[0]);
            }
        });
    }
    
    // ===== FONCTION UPLOAD LOGO =====
    function uploadLogo(file) {
        if (!file.type.match('image.*')) {
            showNotification('error', 'Veuillez sélectionner une image');
            return;
        }
        
        const formData = new FormData();
        formData.append('logo', file);
        
        // Afficher un aperçu immédiat
        const reader = new FileReader();
        reader.onload = (e) => {
            logoPreview.innerHTML = `<img src="${e.target.result}" alt="Logo" class="img-fluid">`;
        };
        reader.readAsDataURL(file);
        
        // Upload AJAX
        fetch('/ShopXPao/public/vendeur/parametres/upload-logo', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('success', 'Logo mis à jour avec succès');
            } else {
                showNotification('error', data.error || 'Erreur lors de l\'upload');
                // Recharger l'aperçu original en cas d'erreur
                location.reload();
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            showNotification('error', 'Erreur de communication');
        });
    }
    
    // ===== FONCTION UPLOAD BANNIÈRE =====
    function uploadBanner(file) {
        if (!file.type.match('image.*')) {
            showNotification('error', 'Veuillez sélectionner une image');
            return;
        }
        
        const formData = new FormData();
        formData.append('banner', file);
        
        // Afficher un aperçu immédiat
        const reader = new FileReader();
        reader.onload = (e) => {
            bannerPreview.innerHTML = `<img src="${e.target.result}" alt="Bannière" class="img-fluid">`;
        };
        reader.readAsDataURL(file);
        
        // Upload AJAX
        fetch('/ShopXPao/public/vendeur/parametres/upload-banner', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('success', 'Bannière mise à jour avec succès');
            } else {
                showNotification('error', data.error || 'Erreur lors de l\'upload');
                location.reload();
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            showNotification('error', 'Erreur de communication');
        });
    }
    
    // ===== NOTIFICATIONS =====
    function showNotification(type, message) {
        // Créer l'élément de notification
        const notification = document.createElement('div');
        notification.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-4`;
        notification.style.zIndex = '9999';
        notification.style.minWidth = '300px';
        notification.innerHTML = `
            <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}-fill me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(notification);
        
        // Auto-disparition après 3 secondes
        setTimeout(() => {
            notification.style.transition = 'opacity 0.5s';
            notification.style.opacity = '0';
            setTimeout(() => notification.remove(), 500);
        }, 3000);
    }
    
    // ===== AUTO-HIDE FLASH MESSAGES =====
    setTimeout(() => {
        document.querySelectorAll('.alert:not(.position-fixed)').forEach(alert => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        });
    }, 3000);
    
    // ===== GARDE L'ONGLET ACTIF APRÈS RECHARGE =====
    const activeTab = new URLSearchParams(window.location.search).get('tab');
    if (activeTab) {
        const tab = document.querySelector(`[data-bs-target="#${activeTab}"]`);
        if (tab) {
            new bootstrap.Tab(tab).show();
        }
    }
});