<?php
require_once(__DIR__ . '/../config/paths.php');
require_once(__DIR__ . '/../controlador/AuthController.php');
require_once(__DIR__ . '/../config/no_cache.php');

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
        'email' => trim($_POST['email'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'confirmPassword' => $_POST['confirmPassword'] ?? ''
    ];

    try {
        // Validación de confirmación de contraseña
        if ($formData['password'] !== $formData['confirmPassword']) {
            throw new Exception("Las contraseñas no coinciden");
        }

        $auth = new AuthController();
        
        $userId = $auth->register(
            $formData['nombre'],
            $formData['apellido'],
            $formData['usuario'],
            $formData['password'],
            $formData['email']
        );
        
        // Redirigir directamente a bienvenida.php después del registro exitoso
        header('Location: ' . BASE_URL . 'pagina/estudiante/bienvenida.php');
        exit();
        
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
            
            <div class="form-group">
                <label for="password">Contraseña:</label>
                <input type="password" id="password" name="password" required minlength="3">
            </div>
            
            <div class="form-group">
                <label for="confirmPassword">Confirmar Contraseña:</label>
                <input type="password" id="confirmPassword" name="confirmPassword" required minlength="3">
            </div>
            
            <button type="submit" class="btn">Registrarse</button>
        </form>
        
        <p>¿Ya tienes cuenta? <a href="<?= BASE_URL ?>pagina/login.php">Inicia sesión aquí</a></p>
    </main>

    <!-- Footer -->
    <?php include(__DIR__ . '/partials/footer.html'); ?>
</body>
</html>