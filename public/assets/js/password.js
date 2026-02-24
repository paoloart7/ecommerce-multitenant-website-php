document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ password.js chargé');
    
    const nouveauInput = document.getElementById('nouveau_mot_de_passe');
    const confirmationInput = document.getElementById('confirmation');
    const submitBtn = document.getElementById('submitBtn');
    const strengthBar = document.querySelector('.strength-bar');
    
    // Éléments des exigences
    const reqLength = document.getElementById('req-length');
    const reqUppercase = document.getElementById('req-uppercase');
    const reqLowercase = document.getElementById('req-lowercase');
    const reqNumber = document.getElementById('req-number');
    const reqMatch = document.getElementById('req-match');
    
    function checkPassword() {
        const password = nouveauInput.value;
        const confirmation = confirmationInput.value;
        
        // Vérifications
        const hasLength = password.length >= 8;
        const hasUppercase = /[A-Z]/.test(password);
        const hasLowercase = /[a-z]/.test(password);
        const hasNumber = /[0-9]/.test(password);
        const match = password === confirmation && password.length > 0;
        
        // Mettre à jour les icônes
        updateRequirement(reqLength, hasLength);
        updateRequirement(reqUppercase, hasUppercase);
        updateRequirement(reqLowercase, hasLowercase);
        updateRequirement(reqNumber, hasNumber);
        updateRequirement(reqMatch, match && password.length > 0);
        
        // Calculer la force
        let strength = 0;
        if (hasLength) strength++;
        if (hasUppercase) strength++;
        if (hasLowercase) strength++;
        if (hasNumber) strength++;
        
        // Mettre à jour la barre
        updateStrengthBar(strength);
        
        // Activer/désactiver le bouton
        const allValid = hasLength && hasUppercase && hasLowercase && hasNumber && match;
        submitBtn.disabled = !allValid;
    }
    
    function updateRequirement(element, isValid) {
        const icon = element.querySelector('i');
        if (isValid) {
            element.classList.remove('unmet');
            element.classList.add('met');
            icon.className = 'bi bi-check-circle-fill';
        } else {
            element.classList.remove('met');
            element.classList.add('unmet');
            icon.className = 'bi bi-x-circle';
        }
    }
    
    function updateStrengthBar(strength) {
        strengthBar.className = 'strength-bar';
        
        if (strength <= 1) {
            strengthBar.classList.add('strength-weak');
        } else if (strength <= 2) {
            strengthBar.classList.add('strength-medium');
        } else {
            strengthBar.classList.add('strength-strong');
        }
    }
    
    // Événements
    nouveauInput.addEventListener('input', checkPassword);
    confirmationInput.addEventListener('input', checkPassword);
    
    // Vérification initiale
    checkPassword();
    
    // Empêcher la soumission si le bouton est désactivé
    document.getElementById('passwordForm').addEventListener('submit', function(e) {
        if (submitBtn.disabled) {
            e.preventDefault();
        }
    });
});