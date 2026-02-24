document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ profil.js chargé');
    
    // Upload d'avatar
    const avatarInput = document.getElementById('avatarInput');
    if (avatarInput) {
        avatarInput.addEventListener('change', function(e) {
            if (e.target.files.length) {
                uploadAvatar(e.target.files[0]);
            }
        });
    }
});

function uploadAvatar(file) {
    const formData = new FormData();
    formData.append('avatar', file);
    
    // Afficher un aperçu immédiat
    const reader = new FileReader();
    reader.onload = function(e) {
        const avatarImg = document.getElementById('profileAvatar');
        const avatarContainer = document.querySelector('.avatar-container');
        
        if (avatarImg) {
            avatarImg.src = e.target.result;
        } else {
            // Remplacer les initiales par l'image
            avatarContainer.innerHTML = `<img src="${e.target.result}" alt="Avatar" id="profileAvatar">`;
            // Ajouter le bouton d'upload
            avatarContainer.innerHTML += `<button class="avatar-upload-btn" onclick="document.getElementById('avatarInput').click()"><i class="bi bi-camera"></i></button>`;
        }
    };
    reader.readAsDataURL(file);
    
    // Upload vers le serveur
    fetch('/ShopXPao/public/profil/upload-avatar', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showNotification('success', 'Avatar mis à jour avec succès');
        } else {
            showNotification('error', data.error || 'Erreur lors de l\'upload');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showNotification('error', 'Erreur de communication');
    });
}

function showNotification(type, message) {
    // Créer l'élément de notification
    const notification = document.createElement('div');
    notification.className = `alert alert-${type}`;
    notification.innerHTML = `
        <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}-fill"></i>
        ${message}
    `;
    
    // Insérer en haut de la page
    const container = document.querySelector('.profil-container');
    container.insertBefore(notification, container.firstChild);
    
    // Auto-disparition après 3 secondes
    setTimeout(() => {
        notification.style.transition = 'opacity 0.5s';
        notification.style.opacity = '0';
        setTimeout(() => notification.remove(), 500);
    }, 3000);
}