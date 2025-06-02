<?php
require_once(__DIR__ . '/../../config/paths.php');
require_once(__DIR__ . '/../../controlador/AuthController.php');
require_once(__DIR__ . '/../../config/no_cache.php');
require_once(__DIR__ . '/../../config/db.php');

$auth = new AuthController();
$auth->checkRole(1); // Solo admin

$db = new Database();
$conn = $db->connect();

$query = "SELECT id, nombre, apellido, usuario, token, fecha_token 
          FROM usuarios 
          WHERE token IS NOT NULL OR fecha_token IS NOT NULL
          ORDER BY fecha_token DESC";
$stmt = $conn->prepare($query);
$stmt->execute();
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contraseñas Temporales</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>pagina/css/styles.css">
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
            <h1>Contraseñas Temporales Generadas</h1>
            
            <table>
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Contraseña Temporal</th>
                        <th>Fecha de Generación</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($usuarios)): ?>
                        <tr>
                            <td colspan="4">No hay contraseñas temporales registradas</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($usuarios as $usuario): ?>
                            <tr>
                                <td><?= htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']) ?></td>
                                <td><?= $usuario['token'] ? htmlspecialchars($usuario['token']) : 'N/A' ?></td>
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
</body>
</html>