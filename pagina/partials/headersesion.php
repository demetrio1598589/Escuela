<?php
require_once(__DIR__ . '/../../config/paths.php');
require_once(__DIR__ . '/../../controlador/AuthController.php');

$auth = new AuthController();
if (!$auth->validateSession()) { 
    header('Location: ' . BASE_URL . 'pagina/login.php');
    exit();
}

// Obtener nombre del rol
$rolNombre = '';
switch ($_SESSION['rol']) {
    case 1: $rolNombre = 'Administrador'; break;
    case 2: $rolNombre = 'Profesor'; break;
    case 3: $rolNombre = 'Estudiante'; break;
}

// Obtener información completa del usuario incluyendo foto
$userInfo = $auth->getUserById($_SESSION['user_id']);
$fotoPerfil = '';

// Priorizar archivo binario sobre enlace externo
if (!empty($userInfo['foto'])) {
    $fotoPerfil = 'data:image/jpeg;base64,' . base64_encode($userInfo['foto']);
} elseif (!empty($userInfo['foto_perfil'])) {
    $fotoPerfil = $userInfo['foto_perfil'];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plataforma Educativa</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        /* Estilos para el modal */
        #sessionExpiredModal {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            z-index: 10000;
            width: 350px;
            max-width: 90%;
            text-align: center;
            border: 1px solid #e0e0e0;
            pointer-events: auto;
        }
        
        /* Fondo oscuro detrás del modal */
        #modalOverlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 9999;
        }
        
        /* Estilos para el botón */
        #reloadButton {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 12px 24px;
            margin-top: 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s;
            min-width: 120px;
        }
        
        #reloadButton:hover {
            background-color: #45a049;
            transform: translateY(-2px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        
        /* Estilo para el contador */
        #countdown {
            color: #f44336;
            font-size: 24px;
            font-weight: bold;
            display: inline-block;
            min-width: 30px;
        }
        
        /* Bloquear interacción con la página */
        .page-blocked {
            pointer-events: none;
            user-select: none;
            opacity: 0.7;
        }
        
        .modal-h2 {
            color: #d32f2f;
            margin-top: 0;
            margin-bottom: 15px;
        }
        
        .modal-p {
            color: #333;
            line-height: 1.5;
            margin-bottom: 10px;
        }
        
        .countdown-text {
            font-size: 18px;
        }
    </style>
    <script>
    $(document).ready(function() {
        // Verificar sesión cada 30 segundos
        function checkSession() {
            $.ajax({
                url: '<?= BASE_URL ?>controlador/check_session.php',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (!response.valid) {
                        showSessionExpiredModal();
                    }
                },
                error: function(xhr, status, error) {
                    console.error("Error verificando sesión:", error);
                }
            });
        }

        // Mostrar modal de sesión expirada
        function showSessionExpiredModal() {
            // Si ya está mostrado, no hacer nada
            if ($('#sessionExpiredModal').is(':visible')) {
                return;
            }
            
            $('body').addClass('page-blocked');
            $('#modalOverlay').show();
            $('#sessionExpiredModal').show();
            
            // Configurar cuenta atrás de 15 segundos
            var seconds = 15;
            var countdownElement = $('#countdown');
            countdownElement.text(seconds);
            
            var countdownInterval = setInterval(function() {
                seconds--;
                countdownElement.text(seconds);
                
                if (seconds <= 0) {
                    clearInterval(countdownInterval);
                    window.location.href = '<?= BASE_URL ?>pagina/login.php?reason=session_taken';
                }
            }, 1000);
        }

        // Ejecutar inmediatamente y luego cada 10 segundos
        checkSession();
        setInterval(checkSession, 10000);

        // Manejar clic en botón de modal
        $('#reloadButton').on('click', function() {
            window.location.href = '<?= BASE_URL ?>pagina/login.php?reason=session_taken';
        });
    });
    </script>
</head>
<body>
    <!-- Overlay oscuro -->
    <div id="modalOverlay"></div>
    
    <!-- Modal para sesión expirada -->
    <div id="sessionExpiredModal">
        <h2 class="modal-h2">¡Sesión Interrumpida!</h2>
        <p class="modal-p">Has iniciado sesión desde otro dispositivo o tu sesión ha expirado por seguridad.</p>
        <p class="countdown-text">Serás redirigido en <span id="countdown">15</span> segundos</p>
        <button id="reloadButton">Aceptar</button>
    </div>

    <header>
        <nav>
            <div class="logo">
                <h1>Plataforma Educativa</h1>
            </div>
            <ul class="nav-links">
                <li><?= htmlspecialchars($_SESSION['nombre'] . ' ' . $_SESSION['apellido']) ?></li>
                <li><?= $rolNombre ?></li>
                <?php if (!empty($fotoPerfil)): ?>
                    <li class="profile-pic">
                        <a href="perfilestudiante.php">
                            <img src="<?= htmlspecialchars($fotoPerfil) ?>" alt="Foto de perfil" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                        </a>
                    </li>
                <?php endif; ?>
                <li><a href="<?= BASE_URL ?>controlador/AuthController.php?action=logout" onclick="return confirm('¿Estás seguro de cerrar sesión?')">Cerrar sesión</a></li>
            </ul>
        </nav>
    </header>
</body>
</html>