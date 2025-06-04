<?php
require_once(__DIR__ . '/../config/paths.php');
require_once(__DIR__ . '/../controlador/AuthController.php');
require_once(__DIR__ . '/../config/no_cache.php');

//librerias
require_once __DIR__.'/../libraries/PHPMailer/src/PHPMailer.php';
require_once __DIR__.'/../libraries/PHPMailer/src/SMTP.php';
require_once __DIR__.'/../libraries/PHPMailer/src/Exception.php';

$error = '';
$success = '';
$formData = [
    'nombre' => '',
    'apellido' => '',
    'usuario' => '',
    'email' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
        'nombre' => trim($_POST['nombre'] ?? ''),
        'apellido' => trim($_POST['apellido'] ?? ''),
        'usuario' => trim($_POST['usuario'] ?? ''),
        'email' => trim($_POST['email'] ?? '')
    ];

    try {
        $auth = new AuthController();
        
        // Generar token único
        $token = bin2hex(random_bytes(32));
        
        // Crear usuario temporal (sin contraseña)
        $userId = $auth->createTempUser(
            $formData['nombre'],
            $formData['apellido'],
            $formData['usuario'],
            $formData['email'],
            $token
        );
        
        // Enviar correo con enlace para completar registro
        $completeLink = BASE_URL . 'pagina/completarregistro.php?token=' . urlencode($token);
        
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'demetrio7000@gmail.com';
            $mail->Password = 'weln ldhi bwwn daoh';
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;
            $mail->CharSet = 'UTF-8';

            $mail->setFrom('no-reply@escuela.com', 'Escuela');
            $mail->addAddress($formData['email'], $formData['nombre'] . ' ' . $formData['apellido']);

            $mail->isHTML(true);
            $mail->Subject = 'Completa tu registro';
            
            $mail->Body = "
            <html>
            <head>
                <title>Completa tu registro</title>
            </head>
            <body>
                <h2>¡Ya casi terminas!</h2>
                <p>Hola {$formData['nombre']} {$formData['apellido']},</p>
                <p>Gracias por registrarte. Por favor, haz clic en el siguiente enlace para completar tu registro:</p>
                <p><a href='{$completeLink}'>{$completeLink}</a></p>
                <p>Datos ingresados:</p>
                <ul>
                    <li>Usuario: {$formData['usuario']}</li>
                    <li>Email: {$formData['email']}</li>
                </ul>
                <p>Si no solicitaste este registro, por favor ignora este correo.</p>
            </body>
            </html>
            ";

            $mail->send();
            $success = "Se ha enviado un correo a {$formData['email']} con instrucciones para completar tu registro.";
        } catch (Exception $e) {
            throw new Exception("Error al enviar el correo de confirmación. Por favor intente más tarde.");
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
    <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Registro de Estudiante</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>pagina/css/login_styles.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>pagina/css/styles.css">
</head>
<body>
    <!-- Header -->
    <?php include(__DIR__ . '/partials/header.php'); ?>

    <main class="login-container">
        <h1>Registro de Estudiante</h1>
        
        <?php if ($error): ?>
            <div class="alert error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="nombre">Nombre:</label>
                <input type="text" id="nombre" name="nombre" required
                       value="<?= htmlspecialchars($formData['nombre']) ?>">
            </div>
            
            <div class="form-group">
                <label for="apellido">Apellido:</label>
                <input type="text" id="apellido" name="apellido" required
                       value="<?= htmlspecialchars($formData['apellido']) ?>">
            </div>
            
            <div class="form-group">
                <label for="email">Correo Electrónico:</label>
                <input type="email" id="email" name="email" required
                       value="<?= htmlspecialchars($formData['email']) ?>">
            </div>
            
            <div class="form-group">
                <label for="usuario">Usuario:</label>
                <input type="text" id="usuario" name="usuario" required
                       value="<?= htmlspecialchars($formData['usuario']) ?>">
            </div>
            
            <button type="submit" class="btn">Registrarse</button>
        </form>
        
        <p>¿Ya tienes cuenta? <a href="<?= BASE_URL ?>pagina/login.php">Inicia sesión aquí</a></p>
    </main>

    <!-- Footer -->
    <?php include(__DIR__ . '/partials/footer.html'); ?>
</body>
</html>