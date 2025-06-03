<?php
require_once(__DIR__ . '/../../config/paths.php');
require_once(__DIR__ . '/../../controlador/AuthController.php');
require_once(__DIR__ . '/../../config/no_cache.php');
require_once(__DIR__ . '/../../config/db.php');

$auth = new AuthController();
$auth->checkRole(1); // Solo admin

$db = new Database();
$conn = $db->connect();

// Initialize variables
$usuarios = [];
$searchTerm = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search'])) {
    $searchTerm = $_POST['search'];
    $query = "SELECT u.id, u.nombre, u.apellido, u.usuario, u.token, u.fecha_token, r.nombre as rol 
              FROM usuarios u
              JOIN roles r ON u.rol_id = r.id
              WHERE (u.token IS NOT NULL OR u.fecha_token IS NOT NULL)
              AND (u.nombre LIKE :search OR u.apellido LIKE :search)
              ORDER BY u.fecha_token DESC";
    $stmt = $conn->prepare($query);
    $searchParam = "%" . $searchTerm . "%";
    $stmt->bindParam(':search', $searchParam);
    $stmt->execute();
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $query = "SELECT u.id, u.nombre, u.apellido, u.usuario, u.token, u.fecha_token, r.nombre as rol 
              FROM usuarios u
              JOIN roles r ON u.rol_id = r.id
              WHERE u.token IS NOT NULL OR u.fecha_token IS NOT NULL
              ORDER BY u.fecha_token DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contraseñas Temporales</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>pagina/css/styles.css">
    <style>
        .password-toggle {
            cursor: pointer;
            color: #2196F3;
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
        .hidden-password {
            filter: blur(4px);
            user-select: none;
        }
    </style>
</head>
<body>
    <!-- Header con sesión -->
    <?php include(__DIR__ . '/../partials/headersesion.php'); ?>

    <main class="dashboard">
        <?php include(__DIR__ . '/../partials/menuadmin.php'); ?>

        <div class="content">
            <h1>Contraseñas Temporales Generadas</h1>
            
            <form method="POST" class="search-form">
                <div class="form-group">
                    <label for="searchInput">Buscar Usuario:</label>
                    <input type="text" id="searchInput" name="search" value="<?= htmlspecialchars($searchTerm) ?>" placeholder="Ingrese nombre o apellido">
                    <button type="submit" class="btn">Buscar</button>
                    <?php if (!empty($searchTerm)): ?>
                        <a href="temp_password.php" class="btn">Limpiar</a>
                    <?php endif; ?>
                </div>
            </form>
            
            <table>
                <thead>
                    <tr>
                        <th>Nombre (usuario)</th>
                        <th>Rol</th>
                        <th>Token Temporal</th>
                        <th>Fecha de Generación</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($usuarios)): ?>
                        <tr>
                            <td colspan="5">No hay contraseñas temporales registradas</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($usuarios as $usuario): ?>
                            <tr>
                                <td><?= htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']) ?> (<?= htmlspecialchars($usuario['usuario']) ?>)</td>
                                <td><span class="role-badge role-<?= strtolower($usuario['rol']) ?>"><?= $usuario['rol'] ?></span></td>
                                <td>
                                    <?php if ($usuario['token']): ?>
                                        <span class="hidden-password"><?= str_repeat('•', strlen($usuario['token'])) ?></span>
                                        <span class="password-toggle" onclick="togglePassword(this)">Mostrar</span>
                                        <span class="actual-password" style="display:none"><?= htmlspecialchars($usuario['token']) ?></span>
                                    <?php else: ?>
                                        N/A
                                    <?php endif; ?>
                                </td>
                                <td><?= $usuario['fecha_token'] ? date('d/m/Y H:i', strtotime($usuario['fecha_token'])) : 'N/A' ?></td>
                                <td><?= $usuario['token'] ? 'Activa' : 'Usada' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <!-- Footer -->
    <?php include(__DIR__ . '/../partials/footer.html'); ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function togglePassword(element) {
            const container = element.parentElement;
            const hidden = container.querySelector('.hidden-password');
            const actual = container.querySelector('.actual-password');
            
            if (hidden.style.display === 'none') {
                hidden.style.display = 'inline';
                actual.style.display = 'none';
                element.textContent = 'Mostrar';
            } else {
                hidden.style.display = 'none';
                actual.style.display = 'inline';
                element.textContent = 'Ocultar';
            }
        }
        
        $(document).ready(function() {
            // Optional: live search with AJAX
            $('#searchInput').on('input', function() {
                const searchTerm = $(this).val();
                if (searchTerm.length >= 2) {
                    $('.search-form').submit();
                }
            });
        });
    </script>
</body>
</html>