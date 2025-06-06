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
$expirationTime = null;

// Verificar si hay un token en la URL
if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    // Obtener información del usuario temporal
    $database = new Database();
    $db = $database->connect();
    $query = "SELECT id, nombre, apellido, usuario, correo, UNIX_TIMESTAMP(fecha_creacion) + 120 as expiration_time 
              FROM usuarios_temp 
              WHERE token = :token AND fecha_creacion >= DATE_SUB(NOW(), INTERVAL 3600 SECOND)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':token', $token);
    $stmt->execute();
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($userData) {
        $expirationTime = $userData['expiration_time'];
    } else {
        $error = "El enlace de registro no es válido o ha expirado";
    }

    // Procesar el formulario de completar registro
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $userData) {
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirmPassword'] ?? '';
        
        try {
            // Validaciones
            if (empty($password)) {
                throw new Exception("La contraseña no puede estar vacía");
            }
            
            if ($password !== $confirmPassword) {
                throw new Exception("Las contraseñas no coinciden");
            }
            
            if (strlen($password) < 8) {
                throw new Exception("La contraseña debe tener al menos 8 caracteres");
            }
            
            // Completar el registro
            $userId = $auth->completeRegistration(
                $userData['id'],
                $password
            );
            
            if (!$userId) {
                throw new Exception("Error al completar el registro");
            }
            
            // Iniciar nueva sesión
            session_regenerate_id(true);
            $newSessionId = session_id();

            // Actualizar session_id en la base de datos
            $query = "UPDATE usuarios SET 
                    session_id = :session_id 
                    WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':session_id', $newSessionId);
            $stmt->bindParam(':id', $userId);
            $stmt->execute();

            // Establecer variables de sesión
            $_SESSION['user_id'] = $userId;
            $_SESSION['username'] = $userData['usuario'];
            $_SESSION['nombre'] = $userData['nombre'];
            $_SESSION['apellido'] = $userData['apellido'];
            $_SESSION['rol'] = 3; // Rol de estudiante
            $_SESSION['correo'] = $userData['correo'];
            $_SESSION['first_login'] = true;
            $_SESSION['current_session_id'] = $newSessionId;
            
            // Redirigir a bienvenida
            header('Location: ' . BASE_URL . 'pagina/estudiante/bienvenida.php');
            exit();
            
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

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Completar Registro</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>pagina/css/styles.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>pagina/css/countdown.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>pagina/css/validarclave.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>pagina/css/login.css">
    <link rel="icon" href="<?= BASE_URL ?>pagina/img/favicon.ico" type="image/x-icon">
    <script src="<?= BASE_URL ?>pagina/js/validarclave.js"></script>
</head>
<body>
    <!-- Header -->
    <?php include(__DIR__ . '/partials/header.php'); ?>

    <main class="login-container">
        <div class="complete-container">
            <h1>Completar Registro</h1>
            
            <?php if ($error): ?>
                <div class="alert error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <?php if ($userData): ?>
                <div class="user-info">
                    <p>Estás completando el registro para:</p>
                    <h3><?= htmlspecialchars($userData['nombre'] . ' ' . $userData['apellido']) ?></h3>
                    <p class="text-muted">Usuario: <?= htmlspecialchars($userData['usuario']) ?></p>
                    <p class="text-muted">Email: <?= htmlspecialchars($userData['correo']) ?></p>
                    <?php if ($userData && $expirationTime): ?>
                        <div id="countdown-container" class="countdown">
                            Tiempo restante: <span id="countdown-time"></span>
                        </div>
                        
                        <script src="<?= BASE_URL ?>pagina/js/countdown.js"></script>
                        <script>
                            setupCountdown(<?= $expirationTime ?>, 'countdown-container', 'countdown-time');
                        </script>
                    <?php endif; ?> 
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
                        <label for="password">Contraseña:</label>
                        <input type="password" id="password" name="password" required 
                            oninput="validatePasswords()">
                    </div>

                    <div class="form-group">
                        <label for="confirmPassword">Confirmar Contraseña:</label>
                        <input type="password" id="confirmPassword" name="confirmPassword" required 
                            oninput="validatePasswords()">
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-block">Completar Registro</button>
                    </div>
                </form>
            <?php else: ?>
                <div class="alert warning">
                    <?= $error ?: 'Token no válido' ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Footer -->
    <?php include(__DIR__ . '/partials/footer.html'); ?>
</body>
</html>