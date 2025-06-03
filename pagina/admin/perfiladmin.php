<?php
require_once(__DIR__ . '/../../config/paths.php');
require_once(__DIR__ . '/../../controlador/AuthController.php');
require_once(__DIR__ . '/../../modelo/User.php');
require_once(__DIR__ . '/../../controlador/AdminController.php');
require_once(__DIR__ . '/../../config/no_cache.php');

$auth = new AuthController();
$auth->checkRole(1); // Solo admin

$database = new Database();
$db = $database->connect();
$userModel = new User($db);
$adminController = new AdminController();

// Obtener información actualizada del usuario
$userInfo = $userModel->getUserById($_SESSION['user_id']);
$_SESSION['foto_perfil'] = $userInfo['foto_perfil'];

// Manejar el envío del formulario de foto
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['submit_photo'])) {
        $photoData = [
            'file' => $_FILES['foto_archivo'] ?? null,
            'url' => !empty(trim($_POST['foto_url'])) ? trim($_POST['foto_url']) : null
        ];
        
        if ($userModel->handlePhotoUpload($_SESSION['user_id'], $photoData)) {
            // Actualizar la sesión con la nueva foto
            $userInfo = $userModel->getUserById($_SESSION['user_id']);
            $_SESSION['foto_perfil'] = $userInfo['foto_perfil'];
            $success = "Foto de perfil actualizada correctamente";
        } else {
            $error = $userModel->getLastError() ?: "Error al actualizar la foto de perfil";
        }
    }
}

// Determinar la ruta de la foto de perfil
$foto_perfil = BASE_URL . 'pagina/fotos/user.webp'; // Valor por defecto

if (!empty($userInfo['foto'])) {
    // Mostrar la foto desde el BLOB si existe
    $foto_perfil = 'data:image/jpeg;base64,' . base64_encode($userInfo['foto']);
} elseif (!empty($userInfo['foto_perfil'])) {
    // Si no hay BLOB, mostrar desde la URL o ruta
    $foto_perfil = filter_var($userInfo['foto_perfil'], FILTER_VALIDATE_URL) ? 
        $userInfo['foto_perfil'] : 
        BASE_URL . 'pagina/' . $userInfo['foto_perfil'];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil de Administrador</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>pagina/css/styles.css">
</head>
<body>
    <!-- Header con sesión -->
    <?php include(__DIR__ . '/../partials/headersesion.php'); ?>

    <main class="dashboard">
        <?php include(__DIR__ . '/../partials/menuadmin.php'); ?>

        <div class="content">
            <h1>Perfil de Administrador</h1>
            
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <p>Bienvenido, <?= htmlspecialchars($_SESSION['nombre']) ?>. Aquí puedes gestionar toda la plataforma.</p><br>
            
            <div class="profile-section">
                <div class="profile-photo-container">
                    <img src="<?= htmlspecialchars($foto_perfil) ?>" alt="Foto de perfil" class="profile-photo" id="profilePhoto">
                    
                    <div id="photoOptions" class="photo-options">
                        <form class="photo-upload-form" method="POST" enctype="multipart/form-data" action="">
                            <h3 style="margin-top: 0;">Actualizar foto de perfil</h3>
                            <div>
                                <input type="file" name="foto_archivo" accept="image/jpeg,image/png,image/gif">
                            </div>
                            <p style="text-align: center; margin: 10px 0;">ó</p>
                            <div>
                                <input type="text" name="foto_url" placeholder="Pega una URL de imagen">
                            </div>
                            <p style="font-size: 0.8em; color: #666; margin: 10px 0;">
                                Máximo 5MB
                            </p>
                            <button type="submit" name="submit_photo">Guardar cambios</button>
                        </form>
                    </div>
                </div>
                
                <h2>Información del Perfil</h2>
                <p><strong>Nombre:</strong> <?= htmlspecialchars($_SESSION['nombre'] . ' ' . $_SESSION['apellido']) ?></p>
                <p><strong>Correo:</strong> <?= htmlspecialchars($_SESSION['correo']) ?></p>
                <p><strong>Rol:</strong> Administrador</p>
                
                <a href="#" class="btn btn-photo" onclick="togglePhotoOptions(event)">Actualizar Foto</a>
                <a href="contrasenaadmin.php" class="btn">Cambiar Contraseña</a>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <?php include(__DIR__ . '/../partials/footer.html'); ?>

    <script>
        function togglePhotoOptions(e) {
            e.preventDefault();
            const options = document.getElementById('photoOptions');
            // Ocultar primero todos los demás menús de foto
            document.querySelectorAll('.photo-options').forEach(opt => {
                if (opt !== options) opt.style.display = 'none';
            });
            // Mostrar/ocultar el menú actual
            options.style.display = options.style.display === 'block' ? 'none' : 'block';
        }

        // Cerrar el menú si se hace clic fuera de él
        document.addEventListener('click', function(event) {
            const photoOptions = document.getElementById('photoOptions');
            const photoContainer = document.querySelector('.profile-photo-container');
            const photoButton = document.querySelector('.btn-photo');
            
            if (!photoContainer.contains(event.target) && event.target !== photoButton && !photoButton.contains(event.target)) {
                photoOptions.style.display = 'none';
            }
        });

        // Opcional: Cerrar el menú al hacer scroll
        window.addEventListener('scroll', function() {
            document.getElementById('photoOptions').style.display = 'none';
        });
    </script>
</body>
</html>