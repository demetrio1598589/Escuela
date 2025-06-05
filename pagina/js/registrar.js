document.addEventListener('DOMContentLoaded', () => {
    const nombre = document.getElementById('nombre');
    const apellido = document.getElementById('apellido');
    const usuario = document.getElementById('usuario');
    const sugerenciasDiv = document.getElementById('sugerencias');
    let usuarioEditadoManualmente = false;

    function generarUsuario(nombre, apellido) {
        return (nombre + apellido).toLowerCase().replace(/\s+/g, '');
    }

    function verificar(username, nombreVal, apellidoVal) {
        fetch(`registrar.php?check_username=1&username=${encodeURIComponent(username)}&nombre=${encodeURIComponent(nombreVal)}&apellido=${encodeURIComponent(apellidoVal)}`)
            .then(res => res.json())
            .then(data => {
                if (data.available) {
                    if (!usuarioEditadoManualmente) {
                        sugerenciasDiv.innerHTML = `✅ Usuario sugerido disponible: <strong>${username}</strong>`;
                    }
                } else {
                    const sugerencias = data.suggestions.map(s => `<li><a href="#" class="suggestion">${s}</a></li>`).join('');
                    sugerenciasDiv.innerHTML = `❌ Usuario en uso. Prueba con:<ul>${sugerencias}</ul>`;
                    
                    // Manejar clic en sugerencias
                    document.querySelectorAll('.suggestion').forEach(item => {
                        item.addEventListener('click', function(e) {
                            e.preventDefault();
                            usuario.value = this.textContent;
                            usuarioEditadoManualmente = true;
                            verificar(this.textContent, nombreVal, apellidoVal);
                        });
                    });
                }
            });
    }

    function actualizar() {
        const nombreVal = nombre.value.trim();
        const apellidoVal = apellido.value.trim();
        if (nombreVal && apellidoVal && !usuarioEditadoManualmente) {
            const sugerido = generarUsuario(nombreVal, apellidoVal);
            usuario.value = sugerido;
            verificar(sugerido, nombreVal, apellidoVal);
        }
    }

    // Marcar cuando el usuario edita manualmente el campo
    usuario.addEventListener('input', () => {
        if (usuario.value.trim() !== generarUsuario(nombre.value.trim(), apellido.value.trim())) {
            usuarioEditadoManualmente = true;
        }
    });

    nombre.addEventListener('blur', actualizar);
    apellido.addEventListener('blur', actualizar);
});
