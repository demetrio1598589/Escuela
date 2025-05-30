<?php
require_once(__DIR__ . '/../../config/paths.php');
require_once(__DIR__ . '/../../controlador/AuthController.php');

$auth = new AuthController();
$auth->checkRole(2); // Solo profesores
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil de Profesor</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>pagina/css/styles.css">
</head>
<body>
    <!-- Header con sesión -->
    <?php include(__DIR__ . '/../partials/headersesion.php'); ?>

    <main class="dashboard">
        <div class="sidebar">
            <h2>Menú Profesor</h2>
            <ul>
                <li><a href="perfilprofesor.php">Perfil</a></li>
                <li><a href="alumnos.php">Mis Alumnos</a></li>
                <li><a href="curso.php">Mis Cursos</a></li>
                <li><a href="<?= BASE_URL ?>controlador/AuthController.php?action=logout">Cerrar Sesión</a></li>
            </ul>
        </div>

        <div class="content">
            <h1>Perfil de Profesor</h1>
            <p>Bienvenido, <?= htmlspecialchars($_SESSION['nombre']) ?>. Aquí puedes ver y gestionar tu perfil.</p>
            
            <h2>Información Personal</h2>
            <p><strong>Nombre:</strong> <?= htmlspecialchars($_SESSION['nombre'] . ' ' . $_SESSION['apellido']) ?></p>
            <p><strong>Correo:</strong> <?= htmlspecialchars($_SESSION['correo']) ?></p>
            <p><strong>Rol:</strong> Profesor</p>
            
            <a href="#" class="btn">Editar Perfil</a>
            <a href="#" class="btn">Cambiar Contraseña</a>
        </div>
    </main>

    <!-- Footer -->
    <?php include(__DIR__ . '/../partials/footer.html'); ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>
</html>