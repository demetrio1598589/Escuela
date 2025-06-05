<?php
require_once(__DIR__ . '/../config/paths.php');
require_once(__DIR__ . '/../controlador/AuthController.php');
require_once(__DIR__ . '/../config/no_cache.php');

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
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
        // Get token expiration time
        $database = new Database();
        $db = $database->connect();
        $query = "SELECT UNIX_TIMESTAMP(fecha_token) + (58 * 60) as expiration_time 
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
            // Validaciones
            if (empty($newPassword)) {
                throw new Exception("La nueva contraseña no puede estar vacía");
            }
            
            if ($newPassword !== $confirmPassword) {
                throw new Exception("Las contraseñas no coinciden");
            }
            
            if (strlen($newPassword) < 6) {
                throw new Exception("La contraseña debe tener al menos 6 caracteres");
            }
            
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
                <div id="countdown" class="countdown">
                    Tiempo restante: <span id="time"></span>
                </div>
                
                <div class="user-info">
                    <p>Estás restableciendo la contraseña para:</p>
                    <h3><?= htmlspecialchars($userData['nombre'] . ' ' . $userData['apellido']) ?></h3>
                    <p class="text-muted">Usuario: <?= htmlspecialchars($userData['usuario']) ?></p>
                </div>
                
                <div class="password-rules">
                    <p><strong>Requisitos de contraseña:</strong></p>
                    <ul>
                        <li>Mínimo 6 caracteres</li>
                        <li>No usar contraseñas obvias</li>
                    </ul>
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
            <?php else: ?>
                <div class="alert warning">
                    <?= $error ?: 'Token no válido' ?>
                </div>
                <p class="text-center">
                    <a href="<?= BASE_URL ?>pagina/solicitartoken.php">Solicitar nuevo enlace de recuperación</a>
                </p>
            <?php endif; ?>
        <script>
            const expirationTime = <?= $expirationTime ?>;
            
            function updateCountdown() {
                const now = Math.floor(Date.now() / 1000);
                const remaining = expirationTime - now;
                
                if (remaining <= 0) {
                    document.getElementById('time').textContent = '0:00';
                    document.getElementById('countdown').classList.add('expired');
                    document.getElementById('countdown').textContent = 'El token ha expirado';
                    
                    // Disable form inputs
                    const inputs = document.querySelectorAll('input');
                    inputs.forEach(input => {
                        input.disabled = true;
                    });
                    
                    // Change button text
                    const button = document.querySelector('button[type="submit"]');
                    if (button) {
                        button.disabled = true;
                        button.textContent = 'Token expirado';
                    }
                    
                    return;
                }
                
                const minutes = Math.floor(remaining / 60);
                const seconds = remaining % 60;
                document.getElementById('time').textContent = 
                    `${minutes}:${seconds.toString().padStart(2, '0')}`;
            }
            
            // Update every second
            updateCountdown();
            setInterval(updateCountdown, 1000);
        </script>    
        </div>
    </main>

    <!-- Footer -->
    <?php include(__DIR__ . '/partials/footer.html'); ?>
</body>
</html>