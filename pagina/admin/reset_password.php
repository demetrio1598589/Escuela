<?php
require_once(__DIR__ . '/../../config/paths.php');
require_once(__DIR__ . '/../../controlador/AuthController.php');
require_once(__DIR__ . '/../../controlador/AdminController.php');
require_once(__DIR__ . '/../../config/no_cache.php');

$auth = new AuthController();
$auth->checkRole(1); // Solo admin

$adminController = new AdminController();
$estudiantes = $adminController->getEstudiantes();

// Get all users (students and teachers)
$database = new Database();
$db = $database->connect();
$query = "SELECT u.id, u.nombre, u.apellido, u.usuario, r.nombre as rol 
          FROM usuarios u
          JOIN roles r ON u.rol_id = r.id
          WHERE u.rol_id IN (2, 3)"; // 2=profesor, 3=estudiante
$stmt = $db->prepare($query);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['search'])) {
        $searchTerm = $_POST['search'];
        $query = "SELECT u.id, u.nombre, u.apellido, u.usuario, r.nombre as rol 
                 FROM usuarios u
                 JOIN roles r ON u.rol_id = r.id
                 WHERE (u.nombre LIKE :search OR u.apellido LIKE :search)
                 AND u.rol_id IN (2, 3)
                 ORDER BY u.apellido, u.nombre";
        $stmt = $db->prepare($query);
        $searchParam = "%" . $searchTerm . "%";
        $stmt->bindParam(':search', $searchParam);
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } elseif (isset($_POST['user_id'])) {
        $userId = $_POST['user_id'];
        $user = $auth->getUserById($userId);
        
        // Generar token: username + 6 dígitos aleatorios
        $randomDigits = mt_rand(100000, 999999);
        $token = $user['usuario'] . $randomDigits;
        
        // Actualizar el token en la base de datos
        $query = "UPDATE usuarios SET token = :token, fecha_token = NOW() WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':id', $userId);
        
        if ($stmt->execute()) {
            $success = "Token generado para " . htmlspecialchars($user['nombre'] . ' ' . $user['apellido']) . 
                      ": <strong>$token</strong>";
            // Refresh the user list
            $query = "SELECT u.id, u.nombre, u.apellido, u.usuario, r.nombre as rol 
                     FROM usuarios u
                     JOIN roles r ON u.rol_id = r.id
                     WHERE u.rol_id IN (2, 3)";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
            max-height: 1600px;
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
        .role-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 12px;
            margin-left: 8px;
        }
        .role-profesor {
            background-color: #4CAF50;
            color: white;
        }
        .role-estudiante {
            background-color: #2196F3;
            color: white;
        }
    </style>
</head>
<body>
    <!-- Header con sesión -->
    <?php include(__DIR__ . '/../partials/headersesion.php'); ?>

    <main class="dashboard">
        <?php include(__DIR__ . '/../partials/menuadmin.php'); ?>

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
                    <label for="searchInput">Buscar Usuario:</label>
                    <input type="text" id="searchInput" name="search" placeholder="Ingrese nombre o apellido">
                    <button type="submit" class="btn">Buscar</button>
                </div>
            </form>
            
            <?php if (!empty($users)): ?>
            <div class="search-results">
                <form method="POST">
                    <?php foreach ($users as $user): ?>
                    <div class="student-item">
                        <input type="radio" name="user_id" id="user_<?= $user['id'] ?>" value="<?= $user['id'] ?>" required>
                        <label for="user_<?= $user['id'] ?>">
                            <span class="role-badge role-<?= strtolower($user['rol']) ?>"><?= $user['rol'] ?></span>
                            <?= htmlspecialchars($user['apellido'] . ', ' . $user['nombre']) ?> 
                            (<?= htmlspecialchars($user['usuario']) ?>)
                        </label>
                    </div>
                    <?php endforeach; ?>
                    <div class="form-group" style="margin-top: 20px;">
                        <button type="submit" class="btn danger">Generar Token</button>
                    </div>
                </form>
            </div>
            <?php elseif (isset($_POST['search'])): ?>
                <div class="alert info">No se encontraron usuarios con ese criterio de búsqueda.</div>
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