function checkPasswordStrength(password) {
    let strength = 0;
    const requirements = {
        minLength: password.length >= 8,
        hasUpper: /[A-Z]/.test(password) && (password.match(/[A-Z]/g) || []).length >= 2,
        hasLower: /[a-z]/.test(password) && (password.match(/[a-z]/g) || []).length >= 2,
        hasNumber: /[0-9]/.test(password) && (password.match(/[0-9]/g) || []).length >= 2,
        hasSpecial: /[^A-Za-z0-9]/.test(password) && (password.match(/[^A-Za-z0-9]/g) || []).length >= 2
    };

    // Actualizar indicadores visuales
    Object.keys(requirements).forEach(key => {
        const element = document.getElementById(`req-${key}`);
        if (element) {
            element.classList.toggle('fulfilled', requirements[key]);
            // Mostrar mensaje específico de lo que falta
            if (!requirements[key]) {
                switch(key) {
                    case 'minLength':
                        element.title = "La contraseña debe tener al menos 8 caracteres";
                        break;
                    case 'hasUpper':
                        element.title = "Faltan letras mayúsculas (necesitas al menos 2)";
                        break;
                    case 'hasLower':
                        element.title = "Faltan letras minúsculas (necesitas al menos 2)";
                        break;
                    case 'hasNumber':
                        element.title = "Faltan números (necesitas al menos 2)";
                        break;
                    case 'hasSpecial':
                        element.title = "Faltan caracteres especiales (necesitas al menos 2)";
                        break;
                }
            } else {
                element.title = "";
            }
        }
    });

    // Calcular fortaleza
    if (requirements.minLength) strength++;
    if (requirements.hasUpper) strength++;
    if (requirements.hasLower) strength++;
    if (requirements.hasNumber) strength++;
    if (requirements.hasSpecial) strength++;

    // Mostrar fortaleza con mensaje de lo que falta
    const strengthElement = document.getElementById('password-strength');
    if (strengthElement) {
        let missingRequirements = [];
        
        if (!requirements.minLength) missingRequirements.push("8 caracteres mínimo");
        if (!requirements.hasUpper) missingRequirements.push("2 mayúsculas");
        if (!requirements.hasLower) missingRequirements.push("2 minúsculas");
        if (!requirements.hasNumber) missingRequirements.push("2 números");
        if (!requirements.hasSpecial) missingRequirements.push("2 caracteres especiales");
        
        if (strength <= 2) {
            strengthElement.textContent = 'Débil - Faltan: ' + missingRequirements.join(', ');
            strengthElement.className = 'password-strength weak';
        } else if (strength <= 4) {
            strengthElement.textContent = 'Media - Faltan: ' + missingRequirements.join(', ');
            strengthElement.className = 'password-strength medium';
        } else {
            strengthElement.textContent = 'Fuerte - ¡Cumple todos los requisitos!';
            strengthElement.className = 'password-strength strong';
        }
    }

    return strength === 5; // Todos los requisitos cumplidos
}

function validatePasswords() {
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    const submitButton = document.querySelector('button[type="submit"]');

    // Verificar fortaleza de contraseña
    const isStrong = checkPasswordStrength(password);

    // Verificar coincidencia
    const matchElement = document.getElementById('password-match');
    if (password && confirmPassword) {
        if (password === confirmPassword) {
            matchElement.textContent = 'Las contraseñas coinciden';
            matchElement.className = 'password-strength strong';
        } else {
            matchElement.textContent = 'Las contraseñas no coinciden';
            matchElement.className = 'password-strength weak';
        }
    } else {
        matchElement.textContent = '';
    }

    // Habilitar/deshabilitar botón
    submitButton.disabled = !(isStrong && password && confirmPassword && password === confirmPassword);
}

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('password').addEventListener('input', function() {
        validatePasswords();
    });

    document.getElementById('confirmPassword').addEventListener('input', function() {
        validatePasswords();
    });
    
    // Validar inicialmente
    validatePasswords();
});