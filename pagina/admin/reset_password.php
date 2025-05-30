<?php
require_once(__DIR__ . '/../../config/paths.php');
require_once(__DIR__ . '/../../controlador/AuthController.php');
require_once(__DIR__ . '/../../controlador/AdminController.php');

$auth = new AuthController();
$auth->checkRole(1); // Solo admin

$adminController = new AdminController();
$estudiantes = $adminController->getEstudiantes();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_POST['user_id'];
    $newPassword = bin2hex(random_bytes(4)); // Genera una contraseña temporal de 8 caracteres
    $adminController->resetPassword($userId, $newPassword);
    $success = "Contraseña reseteada. Nueva contraseña temporal: $newPassword";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resetear Contraseñas</title>
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
            <h1>Resetear Contraseñas</h1>
            
            <?php if (isset($success)): ?>
                <div class="alert success"><?= $success ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="userSelect">Seleccionar Alumno:</label>
                    <select id="userSelect" name="user_id" required>
                        <?php foreach ($estudiantes as $estudiante): ?>
                        <option value="<?= $estudiante['id'] ?>">
                            <?= htmlspecialchars($estudiante['nombre'] . ' ' . $estudiante['apellido']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <button type="submit" class="btn danger">Resetear Contraseña</button>
            </form>
        </div>
    </main>

    <!-- Footer -->
    <?php include(__DIR__ . '/../partials/footer.html'); ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>
</html>