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
$suggestedUsernames = [];

// Verificar disponibilidad de usuario via AJAX
if (isset($_GET['check_username']) && isset($_GET['username'])) {
    header('Content-Type: application/json');
    $auth = new AuthController();
    $username = trim($_GET['username']);
    $response = [
        'available' => false,
        'suggestions' => []
    ];
    
    if (!$auth->checkUsernameExists($username)) {
        $response['available'] = true;
    } else {
        // Generar sugerencias
        $nombre = isset($_GET['nombre']) ? trim($_GET['nombre']) : '';
        $apellido = isset($_GET['apellido']) ? trim($_GET['apellido']) : '';
        $response['suggestions'] = $auth->generateUsernameSuggestions($nombre, $apellido);
    }
    
    echo json_encode($response);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
        'nombre' => trim($_POST['nombre'] ?? ''),
        'apellido' => trim($_POST['apellido'] ?? ''),
        'usuario' => trim($_POST['usuario'] ?? ''),
        'email' => trim($_POST['email'] ?? '')
    ];

    try {
        $auth = new AuthController();
        
        // Verificar si el usuario ya existe
        if ($auth->checkUsernameExists($formData['usuario'])) {
            // Generar sugerencias
            $suggestedUsernames = $auth->generateUsernameSuggestions($formData['nombre'], $formData['apellido']);
            throw new Exception("El nombre de usuario ya está en uso. Prueba con: " . implode(", ", $suggestedUsernames));
        }
        
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
            
            // Limpiar los datos del formulario después de un registro exitoso
            $formData = [
                'nombre' => '',
                'apellido' => '',
                'usuario' => '',
                'email' => ''
            ];
        } catch (Exception $e) {
            throw new Exception("Error al enviar el correo de confirmación. Por favor intente más tarde.");
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
        // Mantener los datos del formulario para que el usuario pueda corregirlos
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
    <script src="<?= BASE_URL ?>pagina/js/registrar.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
        
        <form method="POST" action="" id="registerForm">
            <div class="form-group">
                <label for="nombre">Nombre:</label>
                <input type="text" id="nombre" name="nombre" placeholder="Nombre" required
                       value="<?= htmlspecialchars($formData['nombre']) ?>">
            </div>
            
            <div class="form-group">
                <label for="apellido">Apellido:</label>
                <input type="text" id="apellido" name="apellido" placeholder="Apellido" required
                       value="<?= htmlspecialchars($formData['apellido']) ?>">
            </div>
            
            <div class="form-group">
                <label for="email">Correo Electrónico:</label>
                <input type="email" id="email" name="email" placeholder="correo@mail.com" required
                       value="<?= htmlspecialchars($formData['email']) ?>">
            </div>
            
            <div class="form-group">
                <label for="usuario">Usuario:</label>
                <input type="text" id="usuario" name="usuario" required
                       value="<?= htmlspecialchars($formData['usuario']) ?>">
                <div id="sugerencias" style="font-size: 0.9em; color: gray;"></div>
            </div>
            
            <button type="submit" class="btn">Registrarse</button>
        </form>
        <script>
        document.addEventListener('DOMContentLoaded', () => {
            const nombre = document.getElementById('nombre');
            const apellido = document.getElementById('apellido');
            const usuario = document.getElementById('usuario');
            const sugerenciasDiv = document.getElementById('sugerencias');

            function generarUsuario(nombre, apellido) {
                return (nombre + apellido).toLowerCase().replace(/\s+/g, '');
            }

            function verificar(username, nombreVal, apellidoVal) {
                fetch(`registrar.php?check_username=1&username=${encodeURIComponent(username)}&nombre=${encodeURIComponent(nombreVal)}&apellido=${encodeURIComponent(apellidoVal)}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.available) {
                            sugerenciasDiv.innerHTML = `✅ Usuario sugerido disponible: <strong>${username}</strong>`;
                        } else {
                            const sugerencias = data.suggestions.map(s => `<li>${s}</li>`).join('');
                            sugerenciasDiv.innerHTML = `❌ Usuario en uso. Prueba con:<ul>${sugerencias}</ul>`;
                        }
                    });
            }

            function actualizar() {
                const nombreVal = nombre.value.trim();
                const apellidoVal = apellido.value.trim();
                if (nombreVal && apellidoVal) {
                    const sugerido = generarUsuario(nombreVal, apellidoVal);
                    usuario.value = sugerido;
                    verificar(sugerido, nombreVal, apellidoVal);
                }
            }

            nombre.addEventListener('blur', actualizar);
            apellido.addEventListener('blur', actualizar);
        });
        </script>
        
        <p>¿Ya tienes cuenta? <a href="<?= BASE_URL ?>pagina/login.php">Inicia sesión aquí</a></p>
    </main>

    <!-- Footer -->
    <?php include(__DIR__ . '/partials/footer.html'); ?>
</body>
</html>