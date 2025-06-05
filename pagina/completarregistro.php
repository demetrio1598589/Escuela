<?php
require_once(__DIR__ . '/../config/paths.php');
require_once(__DIR__ . '/../controlador/AuthController.php');
require_once(__DIR__ . '/../config/no_cache.php');

$auth = new AuthController();
$error = '';
$success = '';
$userData = null;

// Verificar si hay un token en la URL
if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    // Obtener información del usuario temporal
    $database = new Database();
    $db = $database->connect();
    $query = "SELECT id, nombre, apellido, usuario, correo FROM usuarios_temp 
              WHERE token = :token AND fecha_creacion >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':token', $token);
    $stmt->execute();
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$userData) {
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
            
            if (strlen($password) < 6) {
                throw new Exception("La contraseña debe tener al menos 6 caracteres");
            }
            
            // Completar el registro
            $userId = $auth->completeRegistration(
                $userData['id'],
                $password
            );
            
            // Iniciar sesión automáticamente
            session_regenerate_id(true);
            $newSessionId = session_id();

            // Actualizar session_id en la base de datos
            $query = "UPDATE usuarios SET 
                        session_id = :session_id 
                        WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':session_id', $newSessionId);
            $stmt->bindParam(':id', $userData['id']);
            $stmt->execute();

            $_SESSION['user_id'] = $userId;
            $_SESSION['username'] = $userData['usuario'];
            $_SESSION['nombre'] = $userData['nombre'];
            $_SESSION['apellido'] = $userData['apellido'];
            $_SESSION['rol'] = 3; // Rol de estudiante
            $_SESSION['correo'] = $userData['correo'];
            $_SESSION['first_login'] = true;
            
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
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Completar Registro</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>pagina/css/styles.css">
    <style>
        .complete-container {
            max-width: 500px;
            margin: 50px auto;
            padding: 30px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            border-radius: 8px;
            background: white;
        }
        .complete-container h1 {
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
                        <label for="password">Contraseña:</label>
                        <input type="password" id="password" name="password" required minlength="6">
                    </div>
                    
                    <div class="form-group">
                        <label for="confirmPassword">Confirmar Contraseña:</label>
                        <input type="password" id="confirmPassword" name="confirmPassword" required minlength="6">
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