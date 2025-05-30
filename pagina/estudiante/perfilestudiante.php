<?php
require_once(__DIR__ . '/../../config/paths.php');
require_once(__DIR__ . '/../../controlador/AuthController.php');

$auth = new AuthController();
$auth->checkRole(3); // Solo estudiantes
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil de Estudiante</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>pagina/css/styles.css">
</head>
<body>
    <!-- Header con sesión -->
    <?php include(__DIR__ . '/../partials/headersesion.php'); ?>

    <main class="dashboard">
        <div class="sidebar">
            <h2>Menú Estudiante</h2>
            <ul>
                <li><a href="perfilestudiante.php">Perfil</a></li>
                <li><a href="cursosestudiante.php">Mis Cursos</a></li>
                <li><a href="matriculaestudiante.php">Matrícula</a></li>
                <li><a href="<?= BASE_URL ?>controlador/AuthController.php?action=logout">Cerrar Sesión</a></li>
            </ul>
        </div>

        <div class="content">
            <h1>Perfil de Estudiante</h1>
            <p>Bienvenido, <?= htmlspecialchars($_SESSION['nombre']) ?>. Aquí puedes ver y gestionar tu perfil.</p>
            
            <h2>Información Personal</h2>
            <p><strong>Nombre:</strong> <?= htmlspecialchars($_SESSION['nombre'] . ' ' . $_SESSION['apellido']) ?></p>
            <p><strong>Correo:</strong> <?= htmlspecialchars($_SESSION['correo']) ?></p>
            <p><strong>Rol:</strong> Estudiante</p>
            
            <a href="#" class="btn">Editar Perfil</a>
            <a href="#" class="btn">Cambiar Contraseña</a>
        </div>
    </main>

    <!-- Footer -->
    <?php include(__DIR__ . '/../partials/footer.html'); ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>
</html>