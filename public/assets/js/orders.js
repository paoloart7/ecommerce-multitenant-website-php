document.addEventListener('DOMContentLoaded', function() {
    // Animation simple d'apparition des lignes
    const rows = document.querySelectorAll('tbody tr');
    rows.forEach((row, i) => {
        row.style.opacity = 0;
        row.style.transform = 'translateY(10px)';
        setTimeout(() => {
            row.style.transition = 'all 0.3s ease';
            row.style.opacity = 1;
            row.style.transform = 'translateY(0)';
        }, i * 50);
    });
});