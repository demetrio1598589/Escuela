<?php
require_once(__DIR__ . '/../../config/paths.php');
require_once(__DIR__ . '/../../controlador/AuthController.php');

$auth = new AuthController();
$auth->checkRole(1); // Solo admin
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
                    <tr>
                        <td>Estudiante A</td>
                        <td>abc123xyz</td>
                        <td>01/10/2023</td>
                        <td>Activa</td>
                    </tr>
                    <tr>
                        <td>Estudiante B</td>
                        <td>def456uvw</td>
                        <td>15/10/2023</td>
                        <td>Activa</td>
                    </tr>
                    <tr>
                        <td>Estudiante C</td>
                        <td>ghi789rst</td>
                        <td>20/10/2023</td>
                        <td>Usada</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </main>

    <!-- Footer -->
    <?php include(__DIR__ . '/../partials/footer.html'); ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>
</html>