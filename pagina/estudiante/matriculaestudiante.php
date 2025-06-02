<?php
require_once(__DIR__ . '/../../config/paths.php');
require_once(__DIR__ . '/../../controlador/AuthController.php');
require_once(__DIR__ . '/../../controlador/EstudianteController.php');
require_once(__DIR__ . '/../../config/no_cache.php');

$auth = new AuthController();
$auth->checkRole(3); // Solo estudiantes

$estudianteController = new EstudianteController();
$cursosDisponibles = $estudianteController->getCursosDisponibles();
$misCursos = $estudianteController->getMisCursos($_SESSION['user_id']);
$puedeMatricular = count($misCursos) < 5;

// Filtrar cursos disponibles (quitar los ya matriculados)
$cursosDisponiblesFiltrados = array_filter($cursosDisponibles, function($cursoDisponible) use ($misCursos) {
    foreach ($misCursos as $cursoMatriculado) {
        if ($cursoMatriculado['id'] == $cursoDisponible['id']) {
            return false; // Excluir este curso
        }
    }
    return true; // Mantener este curso
});

// En matricula de estudiante
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['curso_id'])) {
    $cursoId = $_POST['curso_id'];
    
    // Verificar si ya está matriculado en este curso
    $yaMatriculado = false;
    foreach ($misCursos as $curso) {
        if ($curso['id'] == $cursoId) {
            $yaMatriculado = true;
            break;
        }
    }
    
    if ($yaMatriculado) {
        $error = "Ya estás matriculado en este curso.";
    } elseif (!$puedeMatricular) {
        $error = "Ya has alcanzado el límite de 5 cursos matriculados.";
    } else {
        $resultado = $estudianteController->matricularCurso($_SESSION['user_id'], $cursoId);
        if ($resultado) {
            header('Refresh:0'); // Recargar la página para actualizar la lista
            exit();
        } else {
            $error = "Error al matricularse en el curso.";
        }
    }
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
            <p>Período de matrícula: 01/06/2025 - 15/06/2025</p>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            
            <h2>Cursos Disponibles</h2>
            <?php if ($puedeMatricular): ?>
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
                        <?php foreach ($cursosDisponiblesFiltrados as $curso): ?>
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
            <?php else: ?>
                <div class="alert alert-info">
                    Ya estás matriculado en 5 cursos (el máximo permitido). No puedes matricularte en más cursos.
                </div>
            <?php endif; ?>
            
            <h2>Mis Cursos Matriculados (<?= count($misCursos) ?>/5)</h2>
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

    <?php include(__DIR__ . '/../partials/footer.html'); ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>
</html>