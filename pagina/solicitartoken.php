<?php
require_once(__DIR__ . '/../config/paths.php');
require_once(__DIR__ . '/../controlador/AuthController.php');
require_once(__DIR__ . '/../config/no_cache.php');

//librerias
require_once __DIR__.'/../libraries/PHPMailer/src/PHPMailer.php';
require_once __DIR__.'/../libraries/PHPMailer/src/SMTP.php';
require_once __DIR__.'/../libraries/PHPMailer/src/Exception.php';

$auth = new AuthController();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = trim($_POST['email']);
    
    try {
        // Validar email
        if (empty($email)) {
            throw new Exception("El correo electrónico es requerido");
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("El formato del correo electrónico no es válido");
        }
        
        // Intentar enviar el correo de recuperación
        if ($auth->sendRecoveryEmail($email)) {
            $success = "Se ha enviado un enlace de restablecimiento a tu correo electrónico";
        } else {
            throw new Exception("No se pudo enviar el correo de recuperación. Intenta nuevamente más tarde.");
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
    <title>Solicitar Token de Recuperación</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>pagina/css/styles.css">
    <style>
        .recovery-box {
            max-width: 500px;
            margin: 50px auto;
            padding: 30px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            border-radius: 8px;
            background: white;
        }
        .recovery-box h1 {
            text-align: center;
            margin-bottom: 20px;
            color: #333;
        }
        .instructions {
            margin-bottom: 20px;
            color: #555;
            line-height: 1.5;
        }
    </style>
</head>
<body>
    <!-- Header sin sesión -->
    <?php include(__DIR__ . '/partials/header.php'); ?>

    <main class="login-container">
        <div class="recovery-box">
            <h1>Recuperar Contraseña</h1>
            
            <?php if ($error): ?>
                <div class="alert error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert success"><?= htmlspecialchars($success) ?></div>
                <p class="text-center">¿No recibiste el correo? <a href="<?= BASE_URL ?>pagina/solicitartoken.php">Intentar nuevamente</a></p>
            <?php else: ?>
                <div class="instructions">
                    <p>Ingresa tu dirección de correo electrónico asociada a tu cuenta. Te enviaremos un enlace para restablecer tu contraseña.</p>
                </div>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="email">Correo Electrónico:</label>
                        <input type="email" id="email" name="email" required placeholder="tu@correo.com">
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn">Enviar Enlace de Recuperación</button>
                    </div>
                </form>
                
                <div class="text-center" style="margin-top: 20px;">
                    <a href="<?= BASE_URL ?>pagina/login.php">Volver al inicio de sesión</a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Footer -->
    <?php include(__DIR__ . '/partials/footer.html'); ?>
</body>
</html>