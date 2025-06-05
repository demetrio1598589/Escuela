<?php
require_once(__DIR__ . '/../../config/paths.php');
require_once(__DIR__ . '/../../controlador/AuthController.php');
require_once(__DIR__ . '/../../controlador/ProfesorController.php');

$auth = new AuthController();
if (!$auth->isLoggedIn()) {
    header('Location: ' . BASE_URL . 'pagina/login.php');
    exit();
}
$auth->checkRole(2); // Solo profesores

$profesorController = new ProfesorController();
$misCursos = $profesorController->getMisCursos($_SESSION['user_id']);

$cursoSeleccionado = null;
$alumnos = [];

// Procesar selección de curso
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['curso_id'])) {
        $cursoSeleccionado = $_POST['curso_id'];
        $alumnos = $profesorController->getAlumnosPorCurso($cursoSeleccionado);
    }
    
    // Procesar actualización de nota
    if (isset($_POST['action']) && $_POST['action'] === 'update_grade') {
        $estudianteId = $_POST['estudiante_id'];
        $cursoId = $_POST['curso_id'];
        $nota = $_POST['nota'];
        
        // Validar que la nota sea numérica y entre 0 y 20 con un decimal
        if (is_numeric($nota) && $nota >= 0 && $nota <= 20 && preg_match('/^\d{1,2}(\.\d)?$/', $nota)) {
            $database = new Database();
            $db = $database->connect();
            
            // Verificar si ya existe una calificación
            $query = "SELECT COUNT(*) as count FROM estudiante_curso 
                     WHERE estudiante_id = :estudiante_id AND curso_id = :curso_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':estudiante_id', $estudianteId);
            $stmt->bindParam(':curso_id', $cursoId);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] > 0) {
                // Actualizar nota existente
                $query = "UPDATE estudiante_curso SET nota = :nota 
                         WHERE estudiante_id = :estudiante_id AND curso_id = :curso_id";
            } else {
                // Insertar nueva nota
                $query = "INSERT INTO estudiante_curso (estudiante_id, curso_id, nota) 
                         VALUES (:estudiante_id, :curso_id, :nota)";
            }
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':estudiante_id', $estudianteId);
            $stmt->bindParam(':curso_id', $cursoId);
            $stmt->bindParam(':nota', $nota);
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Nota actualizada correctamente";
            } else {
                $_SESSION['error_message'] = "Error al actualizar la nota";
            }
            
            // Redirigir para evitar reenvío del formulario
            header("Location: alumnos.php?curso_id=" . $cursoId);
            exit();
        } else {
            $_SESSION['error_message'] = "La nota debe ser un número entre 0 y 20 (ej. 15.5)";
        }
    }
}

// Obtener curso seleccionado de GET si viene de redirección
if (isset($_GET['curso_id'])) {
    $cursoSeleccionado = $_GET['curso_id'];
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
    <style>
        .grade-form {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .grade-input {
            width: 80px;
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .btn.small {
            padding: 5px 10px;
            font-size: 0.8rem;
        }
    </style>
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
            
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert success"><?= $_SESSION['success_message'] ?></div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert error"><?= $_SESSION['error_message'] ?></div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>
            
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
                        <td><?= isset($alumno['nota']) ? htmlspecialchars($alumno['nota']) : 'Sin calificar' ?></td>
                        <td>
                            <form method="POST" class="grade-form">
                                <input type="hidden" name="action" value="update_grade">
                                <input type="hidden" name="curso_id" value="<?= $cursoSeleccionado ?>">
                                <input type="hidden" name="estudiante_id" value="<?= $alumno['id'] ?>">
                                <input type="number" name="nota" class="grade-input" 
                                       min="0" max="20" step="0.1" 
                                       value="<?= isset($alumno['nota']) ? htmlspecialchars($alumno['nota']) : '' ?>" 
                                       placeholder="0-20" required>
                                <button type="submit" class="btn small">Actualizar nota</button>
                            </form>
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