<?php
// menuadmin.php
require_once(__DIR__ . '/../../config/paths.php');
?>
<div class="sidebar">
    <h2>Menú Administrador</h2>
    <ul>
        <li><a href="perfiladmin.php">Perfil</a></li>
        <li><a href="crud_curso.php">Gestión de Cursos</a></li>
        <li><a href="alumnosadmin.php">Alumnos</a></li>
        <li><a href="reset_password.php">Resetear Contraseñas</a></li>
        <li><a href="temp_password.php">Contraseñas Temporales</a></li>
        <li><a href="<?= BASE_URL ?>controlador/AuthController.php?action=logout">Cerrar Sesión</a></li>
    </ul>
</div>