<?php
require_once(__DIR__ . '/../../config/paths.php');
require_once(__DIR__ . '/../../controlador/AuthController.php');
require_once(__DIR__ . '/../../config/no_cache.php');
require_once(__DIR__ . '/../../config/db.php');

$auth = new AuthController();
if (!$auth->isLoggedIn()) {
    header('Location: ' . BASE_URL . 'pagina/login.php');
    exit();
}
$auth->checkRole(1); // Solo admin

$db = new Database();
$conn = $db->connect();

// Inicializar variables
$usuarios = [];
$searchTerm = '';

$userModel = new User($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search'])) {
    $searchTerm = $_POST['search'];
    $usuarios = $userModel->getActiveTokens(); // This will only get tokens from last 58 minutes
    
    // Filter by search term
    if (!empty($searchTerm)) {
        $searchTerm = strtolower($searchTerm);
        $usuarios = array_filter($usuarios, function($user) use ($searchTerm) {
            return strpos(strtolower($user['nombre']), $searchTerm) !== false ||
                   strpos(strtolower($user['apellido']), $searchTerm) !== false ||
                   strpos(strtolower($user['usuario']), $searchTerm) !== false;
        });
    }
} else {
    $usuarios = $userModel->getActiveTokens();
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contraseñas Temporales</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>pagina/css/styles.css">
    <style>
        .password-toggle {
            cursor: pointer;
            color: #2196F3;
        }
        .role-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 12px;
            margin-left: 8px;
        }
        .role-profesor {
            background-color: #4CAF50;
            color: white;
        }
        .role-estudiante {
            background-color: #2196F3;
            color: white;
        }
        .role-administrador {
            background-color: #f44336;
            color: white;
        }
        .hidden-password {
            filter: blur(4px);
            user-select: none;
        }
        .status-active-green {
            color: green;
            font-weight: bold;
        }
        .status-active-yellow {
            color: orange;
            font-weight: bold;
        }
        .status-active-red {
            color: red;
            font-weight: bold;
        }
        .status-expired {
            color: #888;
            font-weight: bold;
        }
        .status-used {
            color: #666;
            font-style: italic;
        }
    </style>
</head>
<body>
    <?php include(__DIR__ . '/../partials/headersesion.php'); ?>

    <main class="dashboard">
        <?php include(__DIR__ . '/../partials/menuadmin.php'); ?>

        <div class="content">
            <h1>Contraseñas Temporales Generadas</h1>

            <form method="POST" class="search-form">
                <div class="form-group">
                    <label for="searchInput">Buscar Usuario:</label>
                    <input type="text" id="searchInput" name="search" value="<?= htmlspecialchars($searchTerm) ?>" placeholder="Ingrese nombre o apellido">
                    <button type="submit" class="btn">Buscar</button>
                    <?php if (!empty($searchTerm)): ?>
                        <a href="temp_password.php" class="btn">Limpiar</a>
                    <?php endif; ?>
                </div>
            </form>

            <table>
                <thead>
                    <tr>
                        <th>Nombre (usuario)</th>
                        <th>Rol</th>
                        <th>Token Temporal</th>
                        <th>Fecha de Generación</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($usuarios)): ?>
                        <tr>
                            <td colspan="5">No hay contraseñas temporales registradas</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($usuarios as $usuario): ?>
                            <tr>
                                <td><?= htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']) ?> (<?= htmlspecialchars($usuario['usuario']) ?>)</td>
                                <td><span class="role-badge role-<?= strtolower($usuario['rol']) ?>"><?= $usuario['rol'] ?></span></td>
                                <td>
                                    <?php if ($usuario['token']): ?>
                                        <span class="hidden-password"><?= str_repeat('•', strlen($usuario['token'])) ?></span>
                                        <span class="password-toggle" onclick="togglePassword(this)">Mostrar</span>
                                        <span class="actual-password" style="display:none"><?= htmlspecialchars($usuario['token']) ?></span>
                                    <?php else: ?>
                                        N/A
                                    <?php endif; ?>
                                </td>
                                <td><?= $usuario['fecha_token'] ? date('d/m/Y H:i', strtotime($usuario['fecha_token'])) : 'N/A' ?></td>
                                <td>
                                    <?php
                                    if ($usuario['token']) {
                                        $creationTime = strtotime($usuario['fecha_token']);
                                        $expirationDuration = 24 * 60 * 60; // 24 horas en segundos
                                        $expirationTime = $creationTime + $expirationDuration;
                                    ?>
                                        <span class="token-status"
                                              data-is-used="<?= $usuario['usado'] ? 'true' : 'false' ?>"
                                              data-expiration-time="<?= $expirationTime ?>"
                                              data-creation-time="<?= $creationTime ?>">
                                            <?php 
                                            if ($usuario['usado']) {
                                                echo '<span class="status-used">Usada</span>';
                                            } else {
                                                $remaining = $expirationTime - time();
                                                if ($remaining <= 0) {
                                                    echo '<span class="status-expired">Caducada</span>';
                                                } else {
                                                    echo '<span class="status-active-green">Activa</span>';
                                                }
                                            }
                                            ?>
                                        </span>
                                    <?php } else { ?>
                                        N/A
                                    <?php } ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <?php include(__DIR__ . '/../partials/footer.html'); ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Function to show/hide password
        function togglePassword(element) {
            const container = element.parentElement;
            const hidden = container.querySelector('.hidden-password');
            const actual = container.querySelector('.actual-password');

            if (hidden.style.display === 'none') {
                hidden.style.display = 'inline';
                actual.style.display = 'none';
                element.textContent = 'Mostrar';
            } else {
                hidden.style.display = 'none';
                actual.style.display = 'inline';
                element.textContent = 'Ocultar';
            }
        }

        // Function to format time
        function formatTime(seconds) {
            const hours = Math.floor(seconds / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);
            const secs = seconds % 60;
            
            let parts = [];
            if (hours > 0) parts.push(hours + 'h');
            if (minutes > 0 || hours > 0) parts.push(minutes + 'm');
            parts.push(secs + 's');
            
            return parts.join(' ');
        }

        // Function to update token status dynamically
        function updateTokenStatus() {
            const now = Math.floor(Date.now() / 1000); // Tiempo actual en segundos

            document.querySelectorAll('.token-status').forEach(statusSpan => {
                const isUsed = statusSpan.dataset.isUsed === 'true';
                const expirationTime = parseInt(statusSpan.dataset.expirationTime);
                const creationTime = parseInt(statusSpan.dataset.creationTime);
                
                if (isUsed) {
                    statusSpan.innerHTML = '<span class="status-used">Usada</span>';
                } else {
                    const remainingTime = expirationTime - now;
                    
                    if (remainingTime <= 0) {
                        statusSpan.innerHTML = '<span class="status-expired">Caducada</span>';
                    } else {
                        // Calcular porcentaje de tiempo restante
                        const totalDuration = expirationTime - creationTime;
                        const percentage = (remainingTime / totalDuration) * 100;
                        
                        let statusClass;
                        if (percentage > 50) {
                            statusClass = 'status-active-green';
                        } else if (percentage > 20) {
                            statusClass = 'status-active-yellow';
                        } else {
                            statusClass = 'status-active-red';
                        }
                        
                        const formattedTime = formatTime(remainingTime);
                        statusSpan.innerHTML = `<span class="${statusClass}">Activa (${formattedTime})</span>`;
                    }
                }
            });
        }

        $(document).ready(function() {
            // Initial update when the page loads
            updateTokenStatus();

            // Update token status every second
            setInterval(updateTokenStatus, 1000);
        });
    </script>
</body>
</html>