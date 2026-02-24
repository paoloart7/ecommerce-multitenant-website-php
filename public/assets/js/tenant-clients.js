document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ tenant-clients.js chargé');
    
    // Animation des stats cards
    document.querySelectorAll('.stats-card').forEach((card, i) => {
        card.style.animation = `fadeInUp 0.5s ease ${i * 0.1}s forwards`;
    });
    
    // Animation des lignes
    document.querySelectorAll('.client-row').forEach((row, i) => {
        row.style.animation = `fadeIn 0.3s ease ${i * 0.05}s forwards`;
    });
    
    // Auto-hide alerts
    setTimeout(() => {
        document.querySelectorAll('.alert').forEach(alert => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        });
    }, 3000);
});

// Animations CSS
const style = document.createElement('style');
style.innerHTML = `
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
`;
document.head.appendChild(style);