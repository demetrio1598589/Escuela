<?php
require_once(__DIR__ . '/../../config/paths.php');
require_once(__DIR__ . '/../../controlador/AuthController.php');
require_once(__DIR__ . '/../../controlador/ProfesorController.php');
require_once(__DIR__ . '/../../modelo/Curso.php');

$auth = new AuthController();
if (!$auth->isLoggedIn()) {
    header('Location: ' . BASE_URL . 'pagina/login.php');
    exit();
}
$auth->checkRole(2); // Solo profesores

$profesorController = new ProfesorController();
$misCursos = $profesorController->getMisCursos($_SESSION['user_id']);

// Procesar agregar nuevo curso
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_course') {
    $nombre = $_POST['nombre'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';
    
    if (!empty($nombre) && !empty($descripcion)) {
        $database = new Database();
        $db = $database->connect();
        $cursoModel = new Curso($db);
        
        // Crear el nuevo curso
        $query = "INSERT INTO cursos (nombre, descripcion, profesor_id) VALUES (?, ?, ?)";
        $stmt = $db->prepare($query);
        $success = $stmt->execute([$nombre, $descripcion, $_SESSION['user_id']]);
        
        if ($success) {
            header("Location: curso.php?success=1");
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Cursos</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>pagina/css/styles.css">
    <style>
        .course-container {
            display: flex;
            flex-direction: column;
            gap: 20px;
            margin-bottom: 40px;
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .course-item {
            background-color: #34495e;
            color: white;
            border-radius: 8px;
            padding: 20px;
            display: grid;
            grid-template-columns: 2fr 3fr 1fr;
            gap: 20px;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: background-color 0.3s;
        }
        
        .course-item:hover {
            background-color: #223f5c;
        }
        
        .course-name {
            font-weight: bold;
            font-size: 1.1rem;
            color: white;
        }
        
        .course-description {
            color: #ecf0f1;
            font-size: 0.9rem;
        }
        
        .course-actions {
            text-align: right;
        }
        
        .btn {
            display: inline-block;
            padding: 8px 16px;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-size: 0.9rem;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background-color: #2980b9;
        }
        
        .form-container {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 30px auto;
            width: 100%;
            max-width: 800px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            font-size: 0.9rem;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.9rem;
        }
        
        .form-group textarea {
            min-height: 100px;
        }
        
        .alert {
            padding: 10px 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            width: 100%;
            max-width: 800px;
            margin: 0 auto 20px;
        }
        
        @media (max-width: 768px) {
            .course-item {
                grid-template-columns: 1fr;
                gap: 10px;
            }
            
            .course-actions {
                text-align: left;
                margin-top: 10px;
            }
            
            .course-actions .btn {
                width: 100%;
                text-align: center;
            }
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
            <h1>Mis Cursos</h1>
            
            <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
                <div class="alert">Curso agregado exitosamente!</div>
            <?php endif; ?>
            
            <div class="course-container">
                <?php if (empty($misCursos)): ?>
                    <p>No tienes cursos asignados todavía.</p>
                <?php else: ?>
                    <?php foreach ($misCursos as $curso): ?>
                    <div class="course-item">
                        <div class="course-name"><?= htmlspecialchars($curso['nombre']) ?></div>
                        <div class="course-description"><?= htmlspecialchars($curso['descripcion']) ?></div>
                        <div class="course-actions">
                            <a href="alumnos.php?curso_id=<?= $curso['id'] ?>" class="btn">Gestionar</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div class="form-container">
                <h2>Agregar Nuevo Curso</h2>
                <form method="POST" action="curso.php">
                    <input type="hidden" name="action" value="add_course">
                    <div class="form-group">
                        <label for="courseName">Nombre del Curso:</label>
                        <input type="text" id="courseName" name="nombre" required>
                    </div>
                    <div class="form-group">
                        <label for="courseDescription">Descripción:</label>
                        <textarea id="courseDescription" name="descripcion" rows="3" required></textarea>
                    </div>
                    <button type="submit" class="btn">Agregar Curso</button>
                </form>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <?php include(__DIR__ . '/../partials/footer.html'); ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>
</html>