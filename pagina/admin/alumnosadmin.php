<?php
require_once(__DIR__ . '/../../config/paths.php');
require_once(__DIR__ . '/../../controlador/AuthController.php');
require_once(__DIR__ . '/../../controlador/AdminController.php');
require_once(__DIR__ . '/../../config/no_cache.php');

$auth = new AuthController();
$auth->checkRole(1); // Solo admin

$adminController = new AdminController();

// Manejar búsqueda si se envió
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
$estudiantes = $searchTerm ? $adminController->searchEstudiantes($searchTerm) : $adminController->getEstudiantes();

// Manejar retiro de estudiante
if (isset($_GET['retirar_id'])) {
    $retirado = $adminController->retirarEstudiante($_GET['retirar_id']);
    if ($retirado) {
        header('Location: alumnosadmin.php?success=1');
        exit();
    } else {
        header('Location: alumnosadmin.php?error=1');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Alumnos</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>pagina/css/styles.css">
</head>
<body>
    <!-- Header con sesión -->
    <?php include(__DIR__ . '/../partials/headersesion.php'); ?>

    <main class="dashboard">
        <?php include(__DIR__ . '/../partials/menuadmin.php'); ?>

        <div class="content">
            <h1>Gestión de Alumnos</h1>
            
            <!-- Barra de búsqueda -->
            <form method="GET" id="searchForm" class="content">
                <div class="form-group">
                    <label for="searchInput">Buscar Alumno:</label>
                    <input type="text" id="searchInput" name="search" placeholder="Buscar alumnos..." value="<?= htmlspecialchars($searchTerm) ?>">
                    <button type="submit" class="btn">Buscar</button>
                    <?php if ($searchTerm): ?>
                        <a href="alumnosadmin.php" class="btn secondary">Mostrar todos</a>
                    <?php endif; ?>
                </div>
            </form>
            
            <!-- Mensajes de éxito/error -->
            <?php if (isset($_GET['success'])): ?>
                <div class="alert success">Operación realizada con éxito.</div>
            <?php endif; ?>
            <?php if (isset($_GET['error'])): ?>
                <div class="alert error">Error al realizar la operación.</div>
            <?php endif; ?>
            
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Apellido</th>
                        <th>Usuario</th>
                        <th>Correo</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($estudiantes as $estudiante): ?>
                    <tr>
                        <td><?= htmlspecialchars($estudiante['id']) ?></td>
                        <td><?= htmlspecialchars($estudiante['nombre']) ?></td>
                        <td><?= htmlspecialchars($estudiante['apellido']) ?></td>
                        <td><?= htmlspecialchars($estudiante['usuario']) ?></td>
                        <td><?= htmlspecialchars($estudiante['correo']) ?></td>
                        <td class="actions">
                            <div class="actions-container">
                                <a href="editar_estudiante.php?id=<?= $estudiante['id'] ?>" class="btn editar-alumno">Editar</a>
                                <a href="alumnosadmin.php?retirar_id=<?= $estudiante['id'] ?>" 
                                class="btn editar-alumno danger" 
                                onclick="return confirm('¿Está seguro que desea retirar a este estudiante?')">Retirar</a>
                            </div>
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