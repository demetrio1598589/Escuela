<?php
require_once(__DIR__ . '/../../config/paths.php');
require_once(__DIR__ . '/../../controlador/AuthController.php');

$auth = new AuthController();

// Verificar sesión activa
if (!$auth->isLoggedIn()) {
    header("Location: " . BASE_URL . "pagina/login.php");
    exit();
}

// Obtener nombre del rol
$rolNombre = '';
switch ($_SESSION['rol']) {
    case 1: $rolNombre = 'Administrador'; break;
    case 2: $rolNombre = 'Profesor'; break;
    case 3: $rolNombre = 'Estudiante'; break;
}

// Obtener información completa del usuario incluyendo foto
$userInfo = $auth->getUserById($_SESSION['user_id']);
$fotoPerfil = '';

// Priorizar archivo binario sobre enlace externo
if (!empty($userInfo['foto'])) {
    // Mostrar foto almacenada en la base de datos
    $fotoPerfil = 'data:image/jpeg;base64,' . base64_encode($userInfo['foto']);
} elseif (!empty($userInfo['foto_perfil'])) {
    // Mostrar enlace externo si no hay foto binaria
    $fotoPerfil = $userInfo['foto_perfil'];
}
?>
<header>
    <nav>
        <div class="logo">
            <h1>Plataforma Educativa</h1>
        </div>
        <ul class="nav-links">
            <li><?= htmlspecialchars($_SESSION['nombre'] . ' ' . $_SESSION['apellido']) ?></li>
            <li><?= $rolNombre ?></li>
            <?php if (!empty($fotoPerfil)): ?>
                <li class="profile-pic">
                    <a href="perfilestudiante.php">
                        <img src="<?= htmlspecialchars($fotoPerfil) ?>" alt="Foto de perfil" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                    </a>
                </li>
            <?php endif; ?>
            <li><a href="<?= BASE_URL ?>controlador/AuthController.php?action=logout" onclick="return confirm('¿Estás seguro de cerrar sesión?')">Cerrar sesión</a></li>
        </ul>
    </nav>
</header>