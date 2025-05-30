<?php
require_once(__DIR__ . '/../config/paths.php');
require_once(__DIR__ . '/../controlador/AuthController.php');

$auth = new AuthController();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'] ?? '';
    $apellido = $_POST['apellido'] ?? '';
    $usuario = $_POST['usuario'] ?? '';
    $contraseña = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';
    $correo = $_POST['email'] ?? '';
    $rol = $_POST['role'] ?? 3; // Por defecto estudiante
    
    // Validaciones básicas
    if ($contraseña !== $confirmPassword) {
        $error = 'Las contraseñas no coinciden';
    } elseif (strlen($contraseña) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres';
    } else {
        if ($auth->register($nombre, $apellido, $usuario, $contraseña, $correo, $rol)) {
            $success = 'Registro exitoso. Ahora puedes iniciar sesión.';
            header('Refresh: 3; url=' . BASE_URL . 'pagina/login.php');
        } else {
            $error = 'El usuario ya existe o hubo un error en el registro';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrarse</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>pagina/css/login_styles.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>pagina/css/styles.css">
</head>
<body>
    <!-- Header -->
    <?php include(__DIR__ . '/partials/header.php'); ?>

    <main class="login-container">
        <h1>Registrarse</h1>
        <?php if ($error): ?>
            <div class="alert error"><?= $error ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert success"><?= $success ?></div>
        <?php endif; ?>
        <form id="registerForm" method="POST" action="">
            <div class="form-group">
                <label for="nombre">Nombre:</label>
                <input type="text" id="nombre" name="nombre" required>
            </div>
            <div class="form-group">
                <label for="apellido">Apellido:</label>
                <input type="text" id="apellido" name="apellido" required>
            </div>
            <div class="form-group">
                <label for="email">Correo Electrónico:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="usuario">Usuario:</label>
                <input type="text" id="usuario" name="usuario" required>
            </div>
            <div class="form-group">
                <label for="password">Contraseña:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="confirmPassword">Confirmar Contraseña:</label>
                <input type="password" id="confirmPassword" name="confirmPassword" required>
            </div>
            <div class="form-group">
                <label for="role">Rol:</label>
                <select id="role" name="role">
                    <option value="3">Estudiante</option>
                    <option value="2">Profesor</option>
                </select>
            </div>
            <button type="submit" class="btn">Registrarse</button>
        </form>
        <p>¿Ya tienes cuenta? <a href="<?= BASE_URL ?>pagina/login.php">Inicia sesión aquí</a></p>
    </main>

    <!-- Footer -->
    <?php include(__DIR__ . '/partials/footer.html'); ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>
</html>