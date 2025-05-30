<?php
require_once(__DIR__ . '/../../config/paths.php');
require_once(__DIR__ . '/../../controlador/AuthController.php');
require_once(__DIR__ . '/../../controlador/ProfesorController.php');

$auth = new AuthController();
$auth->checkRole(2); // Solo profesores

$profesorController = new ProfesorController();
$misCursos = $profesorController->getMisCursos($_SESSION['user_id']);

$cursoSeleccionado = null;
$alumnos = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['curso_id'])) {
    $cursoSeleccionado = $_POST['curso_id'];
    $alumnos = $profesorController->getAlumnosPorCurso($cursoSeleccionado);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Alumnos</title>
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
            <h1>Mis Alumnos</h1>
            
            <form method="POST">
                <div class="form-group">
                    <label for="courseSelect">Seleccionar Curso:</label>
                    <select id="courseSelect" name="curso_id" required>
                        <?php foreach ($misCursos as $curso): ?>
                        <option value="<?= $curso['id'] ?>" <?= ($cursoSeleccionado == $curso['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($curso['nombre']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn">Mostrar Alumnos</button>
            </form>
            
            <?php if ($cursoSeleccionado && !empty($alumnos)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Apellido</th>
                        <th>Correo</th>
                        <th>Nota</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($alumnos as $alumno): ?>
                    <tr>
                        <td><?= htmlspecialchars($alumno['nombre']) ?></td>
                        <td><?= htmlspecialchars($alumno['apellido']) ?></td>
                        <td><?= htmlspecialchars($alumno['correo']) ?></td>
                        <td><?= $alumno['nota'] ?? 'Sin calificar' ?></td>
                        <td>
                            <a href="#" class="btn small">Editar Calificación</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </main>

    <!-- Footer -->
    <?php include(__DIR__ . '/../partials/footer.html'); ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>
</html>