<?php
require_once(__DIR__ . '/../../config/paths.php');
require_once(__DIR__ . '/../../controlador/AuthController.php');
require_once(__DIR__ . '/../../controlador/EstudianteController.php');
require_once(__DIR__ . '/../../config/no_cache.php');

$auth = new AuthController();
$auth->checkRole(3); // Solo estudiantes

$estudianteController = new EstudianteController();
$cursos = $estudianteController->getMisCursos($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Cursos</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>pagina/css/styles.css">
    <style>
        .no-courses {
            text-align: center;
            padding: 40px;
            background-color: #f8f9fa;
            border-radius: 8px;
            margin: 20px 0;
        }
        .no-courses h2 {
            color: #6c757d;
            margin-bottom: 20px;
        }
        .no-courses p {
            color: #6c757d;
            margin-bottom: 30px;
        }
        .btn-large {
            padding: 12px 24px;
            font-size: 16px;
        }
    </style>
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
            <h1>Mis Cursos</h1>
            
            <?php if (empty($cursos)): ?>
                <div class="no-courses">
                    <h2>Aún no estás matriculado en ningún curso</h2>
                    <p>Puedes matricularte en los cursos disponibles para comenzar tu aprendizaje</p>
                    <a href="matriculaestudiante.php" class="btn btn-large">Ir a Matrícula</a>
                </div>
            <?php else: ?>
                <div class="course-list">
                    <?php foreach ($cursos as $curso): ?>
                    <div class="course-card">
                        <div class="course-info">
                            <h3><?= htmlspecialchars($curso['nombre']) ?></h3>
                            <p><strong>Profesor:</strong> <?= htmlspecialchars($curso['profesor_nombre'] . ' ' . $curso['profesor_apellido']) ?></p>
                            <p><strong>Descripción:</strong> <?= htmlspecialchars($curso['descripcion']) ?></p>
                            <p><strong>Nota:</strong> <?= $curso['nota'] ?? 'En progreso' ?></p>
                        </div>
                        <div class="course-actions">
                            <a href="#" class="btn small">Ver Detalles</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Footer -->
    <?php include(__DIR__ . '/../partials/footer.html'); ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>
</html>