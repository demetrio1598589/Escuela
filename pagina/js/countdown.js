// countdown.js
function setupCountdown(expirationTime, containerId, timeElementId) {
    const container = document.getElementById(containerId);
    const timeElement = document.getElementById(timeElementId);
    
    if (!container || !timeElement) return;

    function updateCountdown() {
        const now = Math.floor(Date.now() / 1000);
        const remaining = expirationTime - now;
        
        if (remaining <= 0) {
            timeElement.textContent = '00:00';
            container.className = 'countdown expired';
            
            // Deshabilitar inputs y botón
            const inputs = document.querySelectorAll('input');
            inputs.forEach(input => {
                input.disabled = true;
            });
            
            const button = document.querySelector('button[type="submit"]');
            if (button) {
                button.disabled = true;
                button.textContent = 'Token expirado';
            }
            
            return;
        }
        
        const minutes = Math.floor(remaining / 60);
        const seconds = remaining % 60;
        timeElement.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
        
        // Cambiar colores según porcentaje restante
        const totalTime = expirationTime - (now - remaining);
        const percentage = (remaining / totalTime) * 100;
        
        if (percentage > 50) {
            container.className = 'countdown high';
        } else if (percentage > 20) {
            container.className = 'countdown medium';
        } else {
            container.className = 'countdown low';
        }
    }
    
    // Actualizar cada segundo
    updateCountdown();
    setInterval(updateCountdown, 1000);
}