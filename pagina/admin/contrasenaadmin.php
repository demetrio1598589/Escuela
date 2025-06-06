<?php
require_once(__DIR__ . '/../../config/paths.php');
require_once(__DIR__ . '/../../controlador/AuthController.php');
require_once(__DIR__ . '/../../controlador/AdminController.php');
require_once(__DIR__ . '/../../config/no_cache.php');

$auth = new AuthController();
if (!$auth->isLoggedIn()) {
    header('Location: ' . BASE_URL . 'pagina/login.php');
    exit();
}
$auth->checkRole(1); // Solo admin

$error = '';
$success = '';
$tokenMode = false;

// Verificar si viene con token (para recuperación de contraseña)
if (isset($_GET['token'])) {
    $token = $_GET['token'];
    $user = $auth->getUserByToken($token);
    
    if ($user && $user['id'] == $_SESSION['user_id']) {
        $tokenMode = true;
    } else {
        $error = "Token inválido o expirado";
    }
}

// Procesar cambio de contraseña
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    try {        
        // Verificar contraseña actual (solo si no es modo token)
        if (!$tokenMode) {
            $user = $auth->getUserById($_SESSION['user_id']);
            $hashedCurrent = hash('sha256', $currentPassword);
            
            if ($hashedCurrent !== $user['contrasena']) {
                throw new Exception("La contraseña actual es incorrecta");
            }
        }
        
        // Actualizar contraseña
        $hashedNew = hash('sha256', $newPassword);
        $database = new Database();
        $db = $database->connect();
        $userModel = new User($db);
        
        if ($userModel->updatePassword($_SESSION['user_id'], $hashedNew)) {
            // Si estaba en modo token, eliminarlo
            if ($tokenMode) {
                $auth->clearToken($_SESSION['user_id']);
            }
            
            $success = "Contraseña actualizada correctamente";
            $_SESSION['first_login'] = false; // Ya no es primer login
        } else {
            throw new Exception("Error al actualizar la contraseña");
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambiar Contraseña</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>pagina/css/styles.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>pagina/css/login.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>pagina/css/validarclave.css">
    <script src="<?= BASE_URL ?>pagina/js/validarclave.js"></script>
</head>
<body>
    <!-- Header con sesión -->
    <?php include(__DIR__ . '/../partials/headersesion.php'); ?>

    <main class="dashboard">
        <?php include(__DIR__ . '/../partials/menuadmin.php'); ?>

        <div class="content">
            <h1><?= $tokenMode ? 'Restablecer Contraseña' : 'Cambiar Contraseña' ?></h1>
            
            <?php if ($error): ?>
                <div class="alert error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <?php if (!$tokenMode): ?>
                <div class="form-group">
                    <label for="current_password">Contraseña Actual:</label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>
                <?php endif; ?>

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
                
                <div class="form-group">
                    <label for="new_password">Nueva Contraseña:</label>
                    <input type="password" id="new_password" name="new_password" required minlength="6">
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirmar Nueva Contraseña:</label>
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                </div>
                
                <button type="submit" class="btn">Guardar Cambios</button>
                
                <?php if ($tokenMode): ?>
                    <input type="hidden" name="token" value="<?= htmlspecialchars($_GET['token']) ?>">
                <?php endif; ?>
            </form>

            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const newPassword = document.getElementById('new_password');
                    const confirmPassword = document.getElementById('confirm_password');
                    
                    if (newPassword && confirmPassword) {
                        newPassword.addEventListener('input', function() {
                            validatePasswords();
                        });
                        
                        confirmPassword.addEventListener('input', function() {
                            validatePasswords();
                        });
                    }
                    
                    // Adaptar la función validatePasswords para este formulario
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
                    
                    // Validar inicialmente si es modo token (sin contraseña actual)
                    <?php if ($tokenMode): ?>
                        validatePasswords();
                    <?php endif; ?>
                });
            </script>
        </div>
    </main>

    <!-- Footer -->
    <?php include(__DIR__ . '/../partials/footer.html'); ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>
</html>