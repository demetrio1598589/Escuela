<?php
require_once(__DIR__ . '/../config/paths.php');
require_once(__DIR__ . '/../controlador/AuthController.php');

$auth = new AuthController();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = $_POST['username'] ?? '';
    $contraseña = $_POST['password'] ?? '';
    
    if ($auth->login($usuario, $contraseña)) {
        switch ($_SESSION['rol']) {
            case 1: // Admin
                header('Location: ' . BASE_URL . 'pagina/admin/perfiladmin.php');
                break;
            case 2: // Profesor
                header('Location: ' . BASE_URL . 'pagina/profesor/perfilprofesor.php');
                break;
            case 3: // Estudiante
                header('Location: ' . BASE_URL . 'pagina/estudiante/perfilestudiante.php');
                break;
        }
        exit();
    } else {
        $error = 'Credenciales incorrectas. Intente nuevamente.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>pagina/css/login_styles.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>pagina/css/styles.css">
</head>
<body>
    <!-- Header -->
    <?php include(__DIR__ . '/partials/header.php'); ?>

    <main class="login-container">
        <h1>Iniciar Sesión</h1>
        <?php if ($error): ?>
            <div class="alert error"><?= $error ?></div>
        <?php endif; ?>
        <form id="loginForm" method="POST" action="">
            <div class="form-group">
                <label for="username">Usuario:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Contraseña:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn">Ingresar</button>
        </form>
        <p>¿No tienes cuenta? <a href="<?= BASE_URL ?>pagina/registrar.php">Regístrate aquí</a></p>
    </main>

    <!-- Footer -->
    <?php include(__DIR__ . '/partials/footer.html'); ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>
</html>