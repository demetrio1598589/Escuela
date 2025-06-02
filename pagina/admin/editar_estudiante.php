<?php
require_once(__DIR__ . '/../../config/paths.php');
require_once(__DIR__ . '/../../controlador/AuthController.php');
require_once(__DIR__ . '/../../controlador/AdminController.php');
require_once(__DIR__ . '/../../config/no_cache.php');
require_once(__DIR__ . '/../../modelo/User.php');

$auth = new AuthController();
$auth->checkRole(1); // Solo admin

$adminController = new AdminController();

// Obtener datos del estudiante
$estudiante = null;
if (isset($_GET['id'])) {
    $estudiante = $adminController->getUserById($_GET['id']);
    if (!$estudiante || $estudiante['rol_id'] != 3) {
        header('Location: alumnosadmin.php?error=1');
        exit();
    }
}

// Procesar formulario de edición
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos = [
        'id' => $_POST['id'],
        'nombre' => trim($_POST['nombre']),
        'apellido' => trim($_POST['apellido']),
        'usuario' => trim($_POST['usuario']),
        'correo' => trim($_POST['correo'])
    ];
    
    $actualizado = $adminController->actualizarEstudiante($datos);
    if ($actualizado) {
        header('Location: alumnosadmin.php?success=1');
        exit();
    } else {
        $error = "Error al actualizar el estudiante";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Estudiante</title>
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
            <h1>Editar Estudiante</h1>
            
            <?php if (isset($error)): ?>
                <div class="alert error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form method="POST" class="form-container">
                <input type="hidden" name="id" value="<?= htmlspecialchars($estudiante['id']) ?>">
                
                <div class="form-group">
                    <label for="nombre">Nombre:</label>
                    <input type="text" id="nombre" name="nombre" 
                           value="<?= htmlspecialchars($estudiante['nombre']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="apellido">Apellido:</label>
                    <input type="text" id="apellido" name="apellido" 
                           value="<?= htmlspecialchars($estudiante['apellido']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="usuario">Usuario:</label>
                    <input type="text" id="usuario" name="usuario" 
                           value="<?= htmlspecialchars($estudiante['usuario']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="correo">Correo:</label>
                    <input type="email" id="correo" name="correo" 
                           value="<?= htmlspecialchars($estudiante['correo']) ?>" required>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn editar-alumno">Guardar Cambios</button>
                    <a href="alumnosadmin.php" class="btn editar-alumno secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </main>

    <!-- Footer -->
    <?php include(__DIR__ . '/../partials/footer.html'); ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>
</html>