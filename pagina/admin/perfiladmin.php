<?php
require_once(__DIR__ . '/../../config/paths.php');
require_once(__DIR__ . '/../../controlador/AuthController.php');
require_once(__DIR__ . '/../../controlador/AdminController.php');

$auth = new AuthController();
$auth->checkRole(1); // Solo admin

$adminController = new AdminController();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil de Administrador</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>pagina/css/styles.css">
</head>
<body>
    <!-- Header con sesión -->
    <?php include(__DIR__ . '/../partials/headersesion.php'); ?>

    <main class="dashboard">
        <div class="sidebar">
            <h2>Menú Admin</h2>
            <ul>
                <li><a href="perfiladmin.php">Perfil</a></li>
                <li><a href="crud_curso.php">Gestión de Cursos</a></li>
                <li><a href="alumnosadmin.php">Alumnos</a></li>
                <li><a href="reset_password.php">Resetear Contraseñas</a></li>
                <li><a href="temp_password.php">Contraseñas Temporales</a></li>
                <li><a href="<?= BASE_URL ?>controlador/AuthController.php?action=logout">Cerrar Sesión</a></li>
            </ul>
        </div>

        <div class="content">
            <h1>Perfil de Administrador</h1>
            <p>Bienvenido, <?= htmlspecialchars($_SESSION['nombre']) ?>. Aquí puedes gestionar toda la plataforma.</p>
            
            <h2>Información del Perfil</h2>
            <p><strong>Nombre:</strong> <?= htmlspecialchars($_SESSION['nombre'] . ' ' . $_SESSION['apellido']) ?></p>
            <p><strong>Correo:</strong> <?= htmlspecialchars($_SESSION['correo']) ?></p>
            <p><strong>Rol:</strong> Administrador</p>
            
            <a href="#" class="btn">Editar Perfil</a>
        </div>
    </main>

    <!-- Footer -->
    <?php include(__DIR__ . '/../partials/footer.html'); ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>
</html>