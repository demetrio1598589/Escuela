<?php
require_once(__DIR__ . '/../../config/paths.php');
require_once(__DIR__ . '/../../controlador/AuthController.php');

$auth = new AuthController();

if (!$auth->isLoggedIn()) {
    header('Location: ' . BASE_URL . 'pagina/login.php');
    exit();
}

// Obtener nombre del rol
$rolNombre = '';
switch ($_SESSION['rol']) {
    case 1: $rolNombre = 'Administrador'; break;
    case 2: $rolNombre = 'Profesor'; break;
    case 3: $rolNombre = 'Estudiante'; break;
}
?>
<header>
    <nav>
        <div class="logo">
            <h1>Plataforma Educativa</h1>
        </div>
        <ul class="nav-links">
            <li><a href="#"><?= htmlspecialchars($_SESSION['nombre'] . ' ' . $_SESSION['apellido']) ?></a></li>
            <li><a href="#"><?= $rolNombre ?></a></li>
            <li><a href="<?= BASE_URL ?>controlador/AuthController.php?action=logout">Cerrar sesión</a></li>
        </ul>
    </nav>
</header>