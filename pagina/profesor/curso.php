<?php
require_once(__DIR__ . '/../../config/paths.php');
require_once(__DIR__ . '/../../controlador/AuthController.php');
require_once(__DIR__ . '/../../controlador/ProfesorController.php');

$auth = new AuthController();
$auth->checkRole(2); // Solo profesores

$profesorController = new ProfesorController();
$misCursos = $profesorController->getMisCursos($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Cursos</title>
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
            <h1>Mis Cursos</h1>
            
            <div class="course-list">
                <?php foreach ($misCursos as $curso): ?>
                <div class="course-card">
                    <h3><?= htmlspecialchars($curso['nombre']) ?></h3>
                    <p><strong>Descripción:</strong> <?= htmlspecialchars($curso['descripcion']) ?></p>
                    <a href="alumnos.php?curso_id=<?= $curso['id'] ?>" class="btn">Gestionar Curso</a>
                </div>
                <?php endforeach; ?>
            </div>
            
            <h2>Agregar Nuevo Curso</h2>
            <form id="addCourseForm">
                <div class="form-group">
                    <label for="courseName">Nombre del Curso:</label>
                    <input type="text" id="courseName" required>
                </div>
                <div class="form-group">
                    <label for="courseDescription">Descripción:</label>
                    <textarea id="courseDescription" rows="3" required></textarea>
                </div>
                <button type="submit" class="btn">Agregar Curso</button>
            </form>
        </div>
    </main>

    <!-- Footer -->
    <?php include(__DIR__ . '/../partials/footer.html'); ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $("#addCourseForm").submit(function(e){
            e.preventDefault();
            alert("Curso agregado (simulado)");
            $("#courseName, #courseDescription").val("");
        });
    </script>
</body>
</html>