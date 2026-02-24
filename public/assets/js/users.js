// ==============================================
// users.js — Redesign Premium ShopXPao
// ==============================================

(function () {
    'use strict';

    // ── VARIABLES ──
    let userModalInstance = null;
    let deleteModalInstance = null;

    // ── MODAL GETTERS (lazy init) ──
    function getUserModal() {
        if (!userModalInstance) {
            const el = document.getElementById('userModal');
            if (el) userModalInstance = new bootstrap.Modal(el);
        }
        return userModalInstance;
    }

    function getDeleteModal() {
        if (!deleteModalInstance) {
            const el = document.getElementById('deleteModal');
            if (el) deleteModalInstance = new bootstrap.Modal(el);
        }
        return deleteModalInstance;
    }

    // ── RESET FORM (Nouvel utilisateur) ──
    window.resetForm = function () {
        const fields = {
            'modalTitle': { prop: 'innerText', value: 'Nouvel Utilisateur' },
            'userId': { prop: 'value', value: '' },
            'userNom': { prop: 'value', value: '' },
            'userPrenom': { prop: 'value', value: '' },
            'userEmail': { prop: 'value', value: '' },
            'userRole': { prop: 'value', value: 'client' },
            'userStatut': { prop: 'value', value: 'actif' },
            'pwdHelp': { prop: 'innerText', value: '(Requis pour création)' }
        };

        Object.entries(fields).forEach(([id, config]) => {
            const el = document.getElementById(id);
            if (el) el[config.prop] = config.value;
        });

        const pwdInput = document.querySelector('input[name="password"]');
        if (pwdInput) {
            pwdInput.required = true;
            pwdInput.value = '';
        }

        // Animation du titre
        const title = document.getElementById('modalTitle');
        if (title) {
            title.style.opacity = '0';
            title.style.transform = 'translateY(-5px)';
            requestAnimationFrame(() => {
                title.style.transition = 'all 0.3s ease';
                title.style.opacity = '1';
                title.style.transform = 'translateY(0)';
            });
        }
    };

 
// ── EDIT USER (Modifier) ──
window.editUser = function (user) {
    // Parse si string
    if (typeof user === 'string') {
        try {
            user = JSON.parse(user);
        } catch (e) {
            console.error('Erreur parsing JSON user:', e);
            showToast('Erreur lors de la lecture des données', 'danger');
            return;
        }
    }

    // ✅ Titre de la modal
    const titleEl = document.getElementById('modalTitle');
    if (titleEl) titleEl.innerText = 'Modifier Utilisateur';

    // ✅ ID caché
    const idEl = document.getElementById('userId');
    if (idEl) idEl.value = user.idUtilisateur;

    // ✅ Champs en lecture SEULE (admin ne peut pas modifier)
    const nomEl = document.getElementById('userNom');
    if (nomEl) {
        nomEl.value = user.nomUtilisateur;
        nomEl.disabled = true;
        nomEl.readOnly = true;
        nomEl.classList.add('bg-light');
    }

    const prenomEl = document.getElementById('userPrenom');
    if (prenomEl) {
        prenomEl.value = user.prenomUtilisateur;
        prenomEl.disabled = true;
        prenomEl.readOnly = true;
        prenomEl.classList.add('bg-light');
    }

    const emailEl = document.getElementById('userEmail');
    if (emailEl) {
        emailEl.value = user.emailUtilisateur;
        emailEl.disabled = true;
        emailEl.readOnly = true;
        emailEl.classList.add('bg-light');
    }

    // ✅ Champs modifiables (rôle et statut)
    const roleEl = document.getElementById('userRole');
    if (roleEl) {
        roleEl.value = user.role;
        roleEl.disabled = false;
        roleEl.classList.remove('bg-light');
    }

    const statutEl = document.getElementById('userStatut');
    if (statutEl) {
        statutEl.value = user.statut;
        statutEl.disabled = false;
        statutEl.classList.remove('bg-light');
    }

    // ✅ Gestion du mot de passe
    const pwdHelp = document.getElementById('pwdHelp');
    if (pwdHelp) pwdHelp.innerText = '(Laisser vide pour ne pas changer)';

    const pwdInput = document.querySelector('input[name="password"]');
    if (pwdInput) {
        pwdInput.required = false;
        pwdInput.value = '';
        pwdInput.disabled = false;
        pwdInput.classList.remove('bg-light');
    }

    // ✅ Champ caché pour mot de passe généré
    const motDePasseHidden = document.getElementById('motDePasse');
    if (motDePasseHidden) motDePasseHidden.value = '';

    // ✅ Champ de visualisation du mot de passe temporaire
    const newPasswordInput = document.getElementById('newPassword');
    if (newPasswordInput) {
        newPasswordInput.value = '';
        newPasswordInput.readOnly = true;
        newPasswordInput.classList.add('bg-light');
    }

    // ✅ Ouvrir la modal
    const modal = getUserModal();
    if (modal) modal.show();
};

    // ── CONFIRM DELETE ──
    window.confirmDelete = function (id) {
        const deleteIdEl = document.getElementById('deleteId');
        if (deleteIdEl) deleteIdEl.value = id;

        const modal = getDeleteModal();
        if (modal) modal.show();

        // Animation icône shake
        const icon = document.querySelector('.users-delete-icon');
        if (icon) {
            icon.style.animation = 'none';
            requestAnimationFrame(() => {
                icon.style.animation = 'usersShake 0.5s ease-in-out';
            });
        }
    };

    // ── TOAST NOTIFICATION ──
    function showToast(message, type = 'success') {
        // Supprime un toast existant
        const existing = document.querySelector('.users-toast');
        if (existing) existing.remove();

        const toast = document.createElement('div');
        toast.className = `users-toast users-toast-${type}`;
        toast.innerHTML = `
            <div class="toast-icon">
                <i class="bi bi-${type === 'success' ? 'check-circle-fill' : 'exclamation-circle-fill'}"></i>
            </div>
            <span>${message}</span>
            <button class="toast-close" onclick="this.parentElement.remove()">
                <i class="bi bi-x"></i>
            </button>
        `;

        // Styles inline pour le toast
        Object.assign(toast.style, {
            position: 'fixed',
            top: '1.5rem',
            right: '1.5rem',
            zIndex: '9999',
            display: 'flex',
            alignItems: 'center',
            gap: '0.75rem',
            padding: '0.85rem 1.25rem',
            borderRadius: '12px',
            fontSize: '0.875rem',
            fontWeight: '500',
            color: type === 'success' ? '#065f46' : '#991b1b',
            background: type === 'success' ? '#ecfdf5' : '#fef2f2',
            border: `1px solid ${type === 'success' ? '#a7f3d0' : '#fecaca'}`,
            boxShadow: '0 10px 25px rgba(0,0,0,0.1)',
            animation: 'usersSlideDown 0.4s ease-out',
            maxWidth: '400px'
        });

        document.body.appendChild(toast);

        // Auto-remove
        setTimeout(() => {
            toast.style.transition = 'all 0.3s ease';
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(-10px)';
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    }

    // ── PASSWORD TOGGLE ──
    function initPasswordToggle() {
        const wrapper = document.querySelector('.password-toggle-wrapper');
        if (!wrapper) return;

        const input = wrapper.querySelector('input[type="password"], input[type="text"]');
        const btn = wrapper.querySelector('.password-toggle-btn');
        if (!input || !btn) return;

        btn.addEventListener('click', function () {
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            const icon = btn.querySelector('i');
            if (icon) {
                icon.className = isPassword ? 'bi bi-eye-slash' : 'bi bi-eye';
            }
        });
    }

    // ── ENHANCED SEARCH (recherche avec highlight) ──
    function initSearchEnhancement() {
        const searchInput = document.querySelector('.users-filter-card input[name="q"]');
        if (!searchInput) return;

        // Icône de chargement pendant la saisie
        let typingTimer;
        searchInput.addEventListener('input', function () {
            const icon = searchInput.closest('.input-group')?.querySelector('.input-group-text i');
            if (icon) {
                icon.className = 'bi bi-arrow-repeat text-primary';
                icon.style.animation = 'spin 1s linear infinite';
            }

            clearTimeout(typingTimer);
            typingTimer = setTimeout(() => {
                if (icon) {
                    icon.className = 'bi bi-search text-muted';
                    icon.style.animation = '';
                }
            }, 500);
        });

        // Raccourci clavier Ctrl+K pour focus
        document.addEventListener('keydown', function (e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                searchInput.focus();
                searchInput.select();
            }
        });
    }

    // ── TABLE ROW CLICK HIGHLIGHT ──
    function initTableInteractions() {
        const rows = document.querySelectorAll('.users-table tbody tr');
        rows.forEach(row => {
            // Double-click pour modifier
            row.addEventListener('dblclick', function () {
                const editBtn = row.querySelector('.users-action-btn.edit');
                if (editBtn) editBtn.click();
            });

            // Curseur pointer sur hover
            row.style.cursor = 'default';
        });
    }

    // ── COUNTER ANIMATION ──
    function animateCounters() {
        const counters = document.querySelectorAll('.users-stat-text .stat-value');
        counters.forEach(counter => {
            const target = parseInt(counter.textContent) || 0;
            if (target === 0) return;

            let current = 0;
            const increment = Math.ceil(target / 30);
            const duration = 800;
            const stepTime = duration / (target / increment);

            counter.textContent = '0';

            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    counter.textContent = target;
                    clearInterval(timer);
                } else {
                    counter.textContent = current;
                }
            }, stepTime);
        });
    }

    // ── AUTO-DISMISS ALERTS ──
    function initAlertAutoDismiss() {
        const alerts = document.querySelectorAll('.users-alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.transition = 'all 0.5s ease';
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-10px)';
                setTimeout(() => alert.remove(), 500);
            }, 5000);
        });
    }

    // ── FORM VALIDATION FEEDBACK ──
    function initFormValidation() {
        const form = document.querySelector('#userModal form');
        if (!form) return;

        const inputs = form.querySelectorAll('input[required], select[required]');
        inputs.forEach(input => {
            input.addEventListener('blur', function () {
                if (!this.value.trim()) {
                    this.style.borderColor = '#ef4444';
                    this.style.boxShadow = '0 0 0 3px rgba(239,68,68,0.1)';
                } else {
                    this.style.borderColor = '#10b981';
                    this.style.boxShadow = '0 0 0 3px rgba(16,185,129,0.1)';
                }
            });

            input.addEventListener('focus', function () {
                this.style.borderColor = '';
                this.style.boxShadow = '';
            });
        });

        // Email validation en temps réel
        const emailInput = document.getElementById('userEmail');
        if (emailInput) {
            emailInput.addEventListener('input', function () {
                const isValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.value);
                if (this.value.length > 3) {
                    this.style.borderColor = isValid ? '#10b981' : '#ef4444';
                    this.style.boxShadow = isValid
                        ? '0 0 0 3px rgba(16,185,129,0.1)'
                        : '0 0 0 3px rgba(239,68,68,0.1)';
                }
            });
        }
    }

    // ── KEYBOARD SHORTCUTS ──
    function initKeyboardShortcuts() {
        document.addEventListener('keydown', function (e) {
            // Escape ferme les modals
            if (e.key === 'Escape') {
                if (userModalInstance) userModalInstance.hide();
                if (deleteModalInstance) deleteModalInstance.hide();
            }

            // N pour Nouveau (quand pas dans un input)
            if (e.key === 'n' && !isTyping(e)) {
                e.preventDefault();
                resetForm();
                const modal = getUserModal();
                if (modal) modal.show();
            }
        });
    }

    function isTyping(e) {
        const tag = e.target.tagName.toLowerCase();
        return tag === 'input' || tag === 'textarea' || tag === 'select' || e.target.isContentEditable;
    }

    // ── SMOOTH SCROLL TO TABLE ON FILTER ──
    function initSmoothFilter() {
        const filterForm = document.querySelector('.users-filter-card form');
        if (!filterForm) return;

        // Vérifie si on arrive depuis un filtre (URL avec params)
        const params = new URLSearchParams(window.location.search);
        if (params.get('q') || params.get('role') || params.get('status')) {
            const tableCard = document.querySelector('.users-table-card');
            if (tableCard) {
                setTimeout(() => {
                    tableCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }, 300);
            }
        }
    }

    // ── ADD SPIN KEYFRAME ──
    function addSpinKeyframe() {
        if (document.getElementById('users-spin-style')) return;
        const style = document.createElement('style');
        style.id = 'users-spin-style';
        style.textContent = '@keyframes spin { to { transform: rotate(360deg); } }';
        document.head.appendChild(style);
    }

    // ── INIT ──
    function init() {
        addSpinKeyframe();
        initPasswordToggle();
        initSearchEnhancement();
        initTableInteractions();
        animateCounters();
        initAlertAutoDismiss();
        initFormValidation();
        initKeyboardShortcuts();
        initSmoothFilter();

        // Afficher toast si success/error dans URL
        const params = new URLSearchParams(window.location.search);
        if (params.has('success')) {
            showToast('✅ Action effectuée avec succès !', 'success');
        }
        if (params.has('error')) {
            showToast('❌ ' + (params.get('error') || 'Une erreur est survenue'), 'danger');
        }
    }

    // Lancement
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();