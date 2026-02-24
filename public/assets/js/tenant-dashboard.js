document.addEventListener('DOMContentLoaded', function() {
    const counters = document.querySelectorAll('.counter');
    counters.forEach(counter => {
        // Récupère la valeur en enlevant les espaces
        const rawValue = counter.innerText.replace(/\s/g, '').replace('G', '').trim();
        const target = parseFloat(rawValue) || 0;
        
        // Si c'est le chiffre d'affaires (contient un point), on garde les décimales
        if(counter.classList.contains('currency')) {
            counter.innerText = target.toFixed(2).replace('.', ',') + ' G';
        } else {
            counter.innerText = target.toString();
        }
        
        // Animation (optionnelle, tu peux la garder ou l'enlever)
        if(!counter.classList.contains('currency')) {
            counter.innerText = '0';
            const update = () => {
                const count = parseFloat(counter.innerText);
                const increment = Math.ceil(target / 50);
                
                if(count < target) {
                    counter.innerText = Math.min(count + increment, target);
                    setTimeout(update, 20);
                } else {
                    counter.innerText = target;
                }
            };
            update();
        }
    });
});