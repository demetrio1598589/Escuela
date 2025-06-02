<?php
require_once(__DIR__ . '/../../config/paths.php');
require_once(__DIR__ . '/../../controlador/AuthController.php');
require_once(__DIR__ . '/../../controlador/AdminController.php');
require_once(__DIR__ . '/../../config/no_cache.php');

$auth = new AuthController();
$auth->checkRole(1); // Solo admin

$adminController = new AdminController();
$estudiantes = $adminController->getEstudiantes();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['search'])) {
        $searchTerm = $_POST['search'];
        $estudiantes = $adminController->searchEstudiantes($searchTerm);
    } elseif (isset($_POST['user_id'])) {
        $userId = $_POST['user_id'];
        $user = $auth->getUserById($userId);
        
        // Generar token: username + 6 dígitos aleatorios
        $randomDigits = mt_rand(100000, 999999);
        $token = $user['usuario'] . $randomDigits;
        
        // Actualizar el token en la base de datos
        $database = new Database();
        $db = $database->connect();
        $query = "UPDATE usuarios SET token = :token WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':id', $userId);
        
        if ($stmt->execute()) {
            $success = "Token generado para " . htmlspecialchars($user['nombre'] . ' ' . $user['apellido']) . 
                      ": <strong>$token</strong>";
        } else {
            $error = "Error al generar el token";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resetear Contraseñas</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>pagina/css/styles.css">
    <style>
        .search-results {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #ddd;
            margin-top: 10px;
        }
        .student-item {
            padding: 10px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
        }
        .student-item:hover {
            background-color: #f5f5f5;
        }
    </style>
</head>
<body>
    <!-- Header con sesión -->
    <?php include(__DIR__ . '/../partials/headersesion.php'); ?>

    <main class="dashboard">
        <div class="sidebar">
            <h2>Menú Admin</h2>
            <ul>
                <li><a href="perfiladmin.php">Perfil</a></li>
                <li><a href="crud_curso.php">Gestión de Cursos</a></li>
                <li><a href="alumnosadmin.php">Alumnos</a></li>
                <li><a href="reset_password.php">Resetear Contraseñas</a></li>
                <li><a href="temp_password.php">Contraseñas Temporales</a></li>
                <li><a href="<?= BASE_URL ?>controlador/AuthController.php?action=logout">Cerrar Sesión</a></li>
            </ul>
        </div>

        <div class="content">
            <h1>Resetear Contraseñas</h1>
            
            <?php if (isset($success)): ?>
                <div class="alert success"><?= $success ?></div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="alert error"><?= $error ?></div>
            <?php endif; ?>
            
            <form method="POST" id="searchForm">
                <div class="form-group">
                    <label for="searchInput">Buscar Alumno:</label>
                    <input type="text" id="searchInput" name="search" placeholder="Ingrese nombre o apellido">
                    <button type="submit" class="btn">Buscar</button>
                </div>
            </form>
            
            <?php if (!empty($estudiantes)): ?>
            <div class="search-results">
                <form method="POST">
                    <?php foreach ($estudiantes as $estudiante): ?>
                    <div class="student-item">
                        <input type="radio" name="user_id" id="user_<?= $estudiante['id'] ?>" value="<?= $estudiante['id'] ?>" required>
                        <label for="user_<?= $estudiante['id'] ?>">
                            <?= htmlspecialchars($estudiante['apellido'] . ', ' . $estudiante['nombre']) ?> 
                            (<?= htmlspecialchars($estudiante['usuario']) ?>)
                        </label>
                    </div>
                    <?php endforeach; ?>
                    <div class="form-group" style="margin-top: 20px;">
                        <button type="submit" class="btn danger">Generar Token</button>
                    </div>
                </form>
            </div>
            <?php elseif (isset($_POST['search'])): ?>
                <div class="alert info">No se encontraron estudiantes con ese criterio de búsqueda.</div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Footer -->
    <?php include(__DIR__ . '/../partials/footer.html'); ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Opcional: agregar búsqueda en tiempo real con AJAX
            $('#searchInput').on('input', function() {
                const searchTerm = $(this).val();
                if (searchTerm.length >= 2) {
                    $('#searchForm').submit();
                }
            });
        });
    </script>
</body>
</html>