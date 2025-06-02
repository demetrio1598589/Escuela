<?php
require_once(__DIR__ . '/../../config/paths.php');
require_once(__DIR__ . '/../../controlador/AuthController.php');
require_once(__DIR__ . '/../../controlador/EstudianteController.php');
require_once(__DIR__ . '/../../config/no_cache.php');

$auth = new AuthController();
$auth->checkRole(3); // Solo estudiantes

$estudianteController = new EstudianteController();
$misCursos = $estudianteController->getMisCursos($_SESSION['user_id']);
$puedeMatricular = count($misCursos) < 4;

if (!isset($_SESSION['first_login']) || !$_SESSION['first_login'] || !$puedeMatricular) {
    header('Location: ' . BASE_URL . 'pagina/estudiante/cursosestudiante.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenido</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>pagina/css/styles.css">
</head>
<body>
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
            <div class="welcome-container">
                <h1>¡Bienvenido, <?= htmlspecialchars($_SESSION['nombre']) ?>!</h1>
                <p>Nos alegra tenerte en nuestra plataforma educativa.</p>
                <p>Como es tu primera vez, necesitas matricularte en tus cursos.</p>
                <p>Puedes matricularte en un máximo de 5 cursos.</p>
                
                <div class="welcome-actions">
                    <a href="matriculaestudiante.php" class="btn btn-primary">Ir a Matrículas</a>
                </div>
            </div>
        </div>
    </main>

    <?php include(__DIR__ . '/../partials/footer.html'); ?>
</body>
</html>