<?php
require_once(__DIR__ . '/../config/paths.php');
require_once(__DIR__ . '/../controlador/AuthController.php');
require_once(__DIR__ . '/../config/no_cache.php');

$auth = new AuthController();

if ($auth->isLoggedIn()) {
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
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plataforma Educativa</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>pagina/css/styles.css">
</head>
<body>
    <!-- Header -->
    <?php include(__DIR__ . '/partials/header.php'); ?>

    <main>
        <h1>Bienvenido a la Plataforma Educativa</h1>
        
        <div class="welcome-section">
            <p>Nuestra plataforma educativa ofrece herramientas innovadoras para estudiantes y profesores. Conéctate con tu comunidad educativa, accede a materiales de estudio y participa en clases virtuales interactivas.</p>
            
            <div class="features">
                <h2>¿Qué puedes hacer en nuestra plataforma?</h2>
                <ul>
                    <li>Acceder a cursos en línea de diversas materias</li>
                    <li>Participar en foros de discusión con compañeros</li>
                    <li>Entregar tareas y recibir retroalimentación</li>
                    <li>Visualizar tu progreso académico</li>
                    <li>Conectar con tutores y profesores</li>
                </ul>
            </div>            
        </div>
        <p>Por favor inicia sesión para acceder a tu cuenta</p>
        
        <div class="action-buttons">
            <a href="<?= BASE_URL ?>pagina/login.php" class="btn-index">Iniciar Sesión</a> 
            <a href="<?= BASE_URL ?>pagina/registrar.php" class="btn-index">Registrarse</a>
        </div>        
    </main>

    <!-- Footer -->
    <?php include(__DIR__ . '/partials/footer.html'); ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>
</html>