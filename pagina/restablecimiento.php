<?php
require_once(__DIR__ . '/../config/paths.php');
require_once(__DIR__ . '/../controlador/AuthController.php');
require_once(__DIR__ . '/../config/no_cache.php');

// Iniciar sesión y cerrar cualquier sesión existente
if (isset($_SESSION['user_id'])) {
    session_unset();
    session_destroy();
    session_start(); // Reiniciar sesión limpia
}

$auth = new AuthController();
$error = '';
$success = '';
$userData = null;

// Verificar si hay un token en la URL
if (isset($_GET['token'])) {
    $token = $_GET['token'];
    $userData = $auth->getUserByToken($token);
    
    if (!$userData) {
        $error = "El enlace de recuperación no es válido o ha expirado";
    } else {
        // Obtener tiempo de expiración (segundos)
        $database = new Database();
        $db = $database->connect();
        $query = "SELECT UNIX_TIMESTAMP(fecha_token) + 120 as expiration_time 
                  FROM tokens 
                  WHERE token = :token";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);
        $expirationTime = $tokenData['expiration_time'];
    }

    // Procesar el formulario de restablecimiento
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $userData) {
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        try {            
            // Hashear la nueva contraseña
            $hashedPassword = hash('sha256', $newPassword);
            
            // Iniciar transacción para asegurar consistencia
            $db->beginTransaction();
            
            try {
                // 1. Actualizar contraseña en usuarios
                $query = "UPDATE usuarios SET 
                         contrasena = :password 
                         WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':password', $hashedPassword);
                $stmt->bindParam(':id', $userData['id']);
                
                if (!$stmt->execute()) {
                    throw new Exception("Error al actualizar la contraseña");
                }
                
                // 2. Marcar token como usado
                $query = "UPDATE tokens SET 
                         usado = TRUE 
                         WHERE token = :token AND usuario_id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':token', $token);
                $stmt->bindParam(':id', $userData['id']);
                
                if (!$stmt->execute()) {
                    throw new Exception("Error al actualizar el token");
                }
                
                // Confirmar transacción
                $db->commit();
                
                // Limpiar cualquier sesión existente
                session_unset();
                session_destroy();
                
                // Iniciar una nueva sesión
                session_start();
                
                // Establecer datos de sesión
                $_SESSION['user_id'] = $userData['id'];
                $_SESSION['username'] = $userData['usuario'];
                $_SESSION['nombre'] = $userData['nombre'];
                $_SESSION['apellido'] = $userData['apellido'];
                $_SESSION['rol'] = $userData['rol_id'];
                $_SESSION['correo'] = $userData['correo'];
                $_SESSION['current_session_id'] = session_id();
                $_SESSION['password_changed'] = true;
                
                // Actualizar session_id en la base de datos
                $query = "UPDATE usuarios SET 
                         session_id = :session_id 
                         WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':session_id', $_SESSION['current_session_id']);
                $stmt->bindParam(':id', $userData['id']);
                $stmt->execute();
                
                // Redirigir según el rol
                $redirectUrl = BASE_URL . 'pagina/';
                switch ($userData['rol_id']) {
                    case 1: $redirectUrl .= 'admin/perfiladmin.php'; break;
                    case 2: $redirectUrl .= 'profesor/perfilprofesor.php'; break;
                    case 3: $redirectUrl .= 'estudiante/perfilestudiante.php'; break;
                    default: $redirectUrl .= 'index.php'; break;
                }
                
                header("Location: $redirectUrl");
                exit();
                
            } catch (Exception $e) {
                $db->rollBack();
                throw $e;
            }
            
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
} else {
    // No hay token, redirigir a página de inicio
    header("Location: " . BASE_URL . "pagina/index.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>pagina/css/styles.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>pagina/css/countdown.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>pagina/css/login.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>pagina/css/validarclave.css">
    <script src="<?= BASE_URL ?>pagina/js/validarclave.js"></script>
    <script src="<?= BASE_URL ?>pagina/js/countdown.js"></script>
    <style>
        .reset-container {
            max-width: 500px;
            margin: 50px auto;
            padding: 30px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            border-radius: 8px;
            background: white;
        }
        .reset-container h1 {
            text-align: center;
            margin-bottom: 20px;
            color: #333;
        }
        .user-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        .password-rules {
            font-size: 0.9em;
            color: #666;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <!-- Header sin sesión -->
    <?php include(__DIR__ . '/partials/header.php'); ?>

    <main class="login-container">
        <div class="reset-container">
            <h1>Restablecer Contraseña</h1>
            
            <?php if ($error): ?>
                <div class="alert error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <?php if ($userData && $expirationTime): ?>
                <div id="countdown-container" class="countdown">
                    Tiempo restante: <span id="countdown-time"></span>
                </div>
                
                <div class="user-info">
                    <p>Estás restableciendo la contraseña para:</p>
                    <h3><?= htmlspecialchars($userData['nombre'] . ' ' . $userData['apellido']) ?></h3>
                    <p class="text-muted">Usuario: <?= htmlspecialchars($userData['usuario']) ?></p>
                </div>
                
                <div class="password-requirements">
                    <p><strong>Requisitos de contraseña:</strong></p>
                    <ul>
                        <li id="req-minLength" class="requirement">Mínimo 8 caracteres</li>
                        <li id="req-hasUpper" class="requirement">Al menos 2 letras mayúsculas</li>
                        <li id="req-hasLower" class="requirement">Al menos 2 letras minúsculas</li>
                        <li id="req-hasNumber" class="requirement">Al menos 2 números</li>
                        <li id="req-hasSpecial" class="requirement">Al menos 2 caracteres especiales</li>
                    </ul>
                    <div id="password-strength" class="password-strength"></div>
                    <div id="password-match" class="password-strength"></div>
                </div>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="new_password">Nueva Contraseña:</label>
                        <input type="password" id="new_password" name="new_password" required minlength="6">
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirmar Nueva Contraseña:</label>
                        <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-block">Guardar Nueva Contraseña</button>
                    </div>
                </form>

                <script>
                    // Configurar el contador regresivo
                    setupCountdown(<?= $expirationTime ?>, 'countdown-container', 'countdown-time');
                    
                    // Configurar la validación de contraseña
                    document.addEventListener('DOMContentLoaded', function() {
                        // Adaptar la validación para los IDs de este formulario
                        function validatePasswords() {
                            const password = document.getElementById('new_password').value;
                            const confirmPassword = document.getElementById('confirm_password').value;
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
                            if (submitButton) {
                                submitButton.disabled = !(isStrong && password && confirmPassword && password === confirmPassword);
                            }
                        }

                        document.getElementById('new_password').addEventListener('input', function() {
                            validatePasswords();
                        });
                        
                        document.getElementById('confirm_password').addEventListener('input', function() {
                            validatePasswords();
                        });
                        
                        // Validar inicialmente
                        validatePasswords();
                    });
                </script>
            <?php else: ?>
                <div class="alert warning">
                    <?= $error ?: 'Token no válido' ?>
                </div>
                <p class="text-center">
                    <a href="<?= BASE_URL ?>pagina/solicitartoken.php">Solicitar nuevo enlace de recuperación</a>
                </p>
            <?php endif; ?>
        </div>
    </main>

    <!-- Footer -->
    <?php include(__DIR__ . '/partials/footer.html'); ?>
</body>
</html>