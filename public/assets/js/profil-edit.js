document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ profil-edit.js chargé');
    
    // Prévisualisation avatar
    const avatarInput = document.getElementById('avatarInput');
    const avatarPreview = document.getElementById('avatarPreview');
    const avatarContainer = document.querySelector('.avatar-preview');
    
    if (avatarInput) {
        avatarInput.addEventListener('change', function(e) {
            if (e.target.files.length) {
                const file = e.target.files[0];
                
                // Vérifier la taille (max 2MB)
                if (file.size > 2 * 1024 * 1024) {
                    alert('L\'image ne doit pas dépasser 2MB');
                    return;
                }
                
                // Prévisualisation
                const reader = new FileReader();
                reader.onload = function(e) {
                    if (avatarPreview) {
                        avatarPreview.src = e.target.result;
                    } else {
                        avatarContainer.innerHTML = `<img src="${e.target.result}" alt="Avatar" id="avatarPreview">`;
                    }
                };
                reader.readAsDataURL(file);
                
                // Upload immédiat
                uploadAvatar(file);
            }
        });
    }
});

function uploadAvatar(file) {
    const formData = new FormData();
    formData.append('avatar', file);
    
    fetch('/ShopXPao/public/profil/upload-avatar', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showNotification('success', 'Avatar mis à jour');
        } else {
            showNotification('error', data.error || 'Erreur upload');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showNotification('error', 'Erreur de communication');
    });
}

function showNotification(type, message) {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type}`;
    notification.innerHTML = `
        <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}-fill"></i>
        ${message}
    `;
    
    const container = document.querySelector('.profil-container');
    container.insertBefore(notification, container.firstChild);
    
    setTimeout(() => {
        notification.style.transition = 'opacity 0.5s';
        notification.style.opacity = '0';
        setTimeout(() => notification.remove(), 500);
    }, 3000);
}