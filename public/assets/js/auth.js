document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('authContainer');
    if (!container) return;

    // --- BASCULEMENT LOGIN / REGISTER ---
    const goToSignUp = document.getElementById('goToSignUp');
    const goToSignIn = document.getElementById('goToSignIn');

    function openRegister(e) {
        if (e) e.preventDefault();
        container.classList.add('right-panel-active');
    }

    function openLogin(e) {
        if (e) e.preventDefault();
        container.classList.remove('right-panel-active');
    }

    if (goToSignUp) goToSignUp.addEventListener('click', openRegister);
    if (goToSignIn) goToSignIn.addEventListener('click', openLogin);

    // --- TOGGLE MOT DE PASSE (ŒIL) ---
    document.querySelectorAll('.toggle-password').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();

            const targetId = this.getAttribute('data-target');
            const input = document.getElementById(targetId);
            if (!input) return;

            const icon = this.querySelector('i');

            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            } else {
                input.type = 'password';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            }
        });
    });

    // --- ANIMATION FOCUS INPUTS ---
    document.querySelectorAll('.input-group-auth input, .auth-select').forEach(input => {
        input.addEventListener('focus', function () {
            this.closest('.input-group-auth')?.classList.add('focused');
        });
        input.addEventListener('blur', function () {
            this.closest('.input-group-auth')?.classList.remove('focused');
        });
    });
});