<?php
require_once(__DIR__ . '/../../config/paths.php');
require_once(__DIR__ . '/../../controlador/AuthController.php');
require_once(__DIR__ . '/../../controlador/EstudianteController.php');

$auth = new AuthController();
$auth->checkRole(3); // Solo estudiantes

$estudianteController = new EstudianteController();
$cursosDisponibles = $estudianteController->getCursosDisponibles();
$misCursos = $estudianteController->getMisCursos($_SESSION['user_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['curso_id'])) {
    $estudianteController->matricularCurso($_SESSION['user_id'], $_POST['curso_id']);
    header('Refresh:0');
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Matrícula de Cursos</title>
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
            <h1>Matrícula de Cursos</h1>
            <p>Período de matrícula: 01/11/2023 - 15/11/2023</p>
            
            <h2>Cursos Disponibles</h2>
            <table>
                <thead>
                    <tr>
                        <th>Curso</th>
                        <th>Profesor</th>
                        <th>Descripción</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cursosDisponibles as $curso): ?>
                    <tr>
                        <td><?= htmlspecialchars($curso['nombre']) ?></td>
                        <td><?= htmlspecialchars($curso['profesor_nombre'] . ' ' . $curso['profesor_apellido']) ?></td>
                        <td><?= htmlspecialchars($curso['descripcion']) ?></td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="curso_id" value="<?= $curso['id'] ?>">
                                <button type="submit" class="btn small">Matricular</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <h2>Mis Cursos Matriculados</h2>
            <table>
                <thead>
                    <tr>
                        <th>Curso</th>
                        <th>Profesor</th>
                        <th>Descripción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($misCursos as $curso): ?>
                    <tr>
                        <td><?= htmlspecialchars($curso['nombre']) ?></td>
                        <td><?= htmlspecialchars($curso['profesor_nombre'] . ' ' . $curso['profesor_apellido']) ?></td>
                        <td><?= htmlspecialchars($curso['descripcion']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>

    <!-- Footer -->
    <?php include(__DIR__ . '/../partials/footer.html'); ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>
</html>