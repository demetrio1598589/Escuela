<?php
require_once(__DIR__ . '/../../config/paths.php');
require_once(__DIR__ . '/../../controlador/AuthController.php');
require_once(__DIR__ . '/../../controlador/AdminController.php');

$auth = new AuthController();
$auth->checkRole(1); // Solo admin

$adminController = new AdminController();
$cursos = $adminController->getCursos();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Cursos</title>
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
            <h1>Gestión de Cursos</h1>
            
            <div class="actions">
                <a href="#" class="btn">Agregar Nuevo Curso</a>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Profesor</th>
                        <th>Descripción</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cursos as $curso): ?>
                    <tr>
                        <td><?= htmlspecialchars($curso['id']) ?></td>
                        <td><?= htmlspecialchars($curso['nombre']) ?></td>
                        <td><?= htmlspecialchars($curso['profesor_nombre'] . ' ' . $curso['profesor_apellido']) ?></td>
                        <td><?= htmlspecialchars($curso['descripcion']) ?></td>
                        <td>
                            <a href="#" class="btn small">Editar</a>
                            <a href="#" class="btn small danger">Eliminar</a>
                        </td>
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