<?php
require_once(__DIR__ . '/../config/paths.php');
require_once(__DIR__ . '/../controlador/AuthController.php');
require_once(__DIR__ . '/../config/no_cache.php');
require_once(__DIR__ . '/../modelo/User.php');

$database = new Database();
$db = $database->connect();
$userModel = new User($db);

$auth = new AuthController();
if ($auth->isLoggedIn()) {
    switch ($_SESSION['rol']) {
        case 1: header('Location: ' . BASE_URL . 'pagina/admin/perfiladmin.php'); break;
        case 2: header('Location: ' . BASE_URL . 'pagina/profesor/perfilprofesor.php'); break;
        case 3: 
            header('Location: ' . BASE_URL . ($_SESSION['first_login'] ? 
                'pagina/estudiante/bienvenida.php' : 'pagina/estudiante/cursosestudiante.php'));
            break;
    }
    exit();
}

// Initialize session counters
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = [];
    $_SESSION['last_failed_attempt'] = [];
    $_SESSION['block_count'] = [];
    $_SESSION['in_wait'] = false;
    $_SESSION['wait_start'] = 0;
    $_SESSION['wait_user'] = '';
}

$error = '';
$currentUser = trim($_POST['username'] ?? '');
$showCaptcha = false;
$waitTime = 0;

// Check if user is in timeout from previous session
if (isset($_SESSION['in_wait']) && $_SESSION['in_wait'] && 
    isset($_SESSION['wait_start']) && isset($_SESSION['wait_user'])) {
    
    $elapsed = time() - $_SESSION['wait_start'];
    if ($elapsed < 20) {
        $showCaptcha = true;
        $waitTime = 20 - $elapsed;
        $currentUser = $_SESSION['wait_user'];
    } else {
        // Timeout completed
        $_SESSION['in_wait'] = false;
        $_SESSION['wait_start'] = 0;
        $_SESSION['wait_user'] = '';
    }
}

// Check if user is in timeout from current session
if (!empty($currentUser) && isset($_SESSION['last_failed_attempt'][$currentUser])) {
    $elapsed = time() - $_SESSION['last_failed_attempt'][$currentUser];
    if ($_SESSION['login_attempts'][$currentUser] >= 3 && $elapsed < 20) {
        $showCaptcha = true;
        $waitTime = 20 - $elapsed;
        
        // Set session wait state
        $_SESSION['in_wait'] = true;
        $_SESSION['wait_start'] = $_SESSION['last_failed_attempt'][$currentUser];
        $_SESSION['wait_user'] = $currentUser;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = $currentUser;
    $contraseña = trim($_POST['password'] ?? '');
    
    $userData = $userModel->getUserByUsername($usuario);
    
    if ($userData !== false) {
        // Handle blocked account
        if ($userData['contrasena'] === 'bloqueado') {
            if (!empty($userData['token']) && $userData['token'] === $contraseña) {
                if ($auth->login($usuario, $contraseña)) {
                    exit();
                }
            } else {
                $error = 'Tu cuenta ha sido bloqueada. Por favor, contacta al administrador o usa tu token de recuperación.';
                
                // Initialize counters for this user if not set
                if (!isset($_SESSION['login_attempts'][$usuario])) {
                    $_SESSION['login_attempts'][$usuario] = 0;
                }
                
                // Only increment attempts if not in wait state
                if (!isset($_SESSION['in_wait']) || !$_SESSION['in_wait']) {
                    $_SESSION['login_attempts'][$usuario]++;
                    $_SESSION['last_failed_attempt'][$usuario] = time();
                }
                
                $remaining_attempts = max(0, 3 - $_SESSION['login_attempts'][$usuario]);
                if ($remaining_attempts > 0) {
                    $error .= '<br>Intentos restantes: ' . $remaining_attempts;
                }
                
                if ($_SESSION['login_attempts'][$usuario] >= 3) {
                    $showCaptcha = true;
                    $waitTime = 20;
                    $_SESSION['in_wait'] = true;
                    $_SESSION['wait_start'] = time();
                    $_SESSION['wait_user'] = $usuario;
                }
            }
        } 
        // Handle normal login
        else {
            if ($auth->login($usuario, $contraseña)) {
                // Reset counters for ALL users on successful login
                $_SESSION['login_attempts'] = [];
                $_SESSION['last_failed_attempt'] = [];
                $_SESSION['block_count'] = [];
                $_SESSION['in_wait'] = false;
                $_SESSION['wait_start'] = 0;
                $_SESSION['wait_user'] = '';
                exit();
            } else {
                // Initialize counters for this user if not set
                if (!isset($_SESSION['login_attempts'][$usuario])) {
                    $_SESSION['login_attempts'][$usuario] = 0;
                    $_SESSION['block_count'][$usuario] = 0;
                }
                
                // Only increment attempts if not in wait state
                if (!isset($_SESSION['in_wait']) || !$_SESSION['in_wait']) {
                    $_SESSION['login_attempts'][$usuario]++;
                    $_SESSION['last_failed_attempt'][$usuario] = time();
                }
                
                $remaining_attempts = max(0, 3 - $_SESSION['login_attempts'][$usuario]);
                $error = 'Credenciales incorrectas. Intente nuevamente.';
                
                if ($remaining_attempts > 0) {
                    $error .= '<br>Intentos restantes: ' . $remaining_attempts;
                }
                
                if ($_SESSION['login_attempts'][$usuario] >= 3) {
                    $showCaptcha = true;
                    $waitTime = 20;
                    $_SESSION['in_wait'] = true;
                    $_SESSION['wait_start'] = time();
                    $_SESSION['wait_user'] = $usuario;
                    
                    // Increment block count only when entering wait state
                    $_SESSION['block_count'][$usuario]++;
                    
                    // Reset login attempts for the next cycle
                    $_SESSION['login_attempts'][$usuario] = 0;
                    
                    // Block account if this is the second timeout
                    if ($_SESSION['block_count'][$usuario] >= 2) {
                        $userModel->blockUser($userData['id']);
                        $error = 'Tu cuenta ha sido bloqueada por seguridad. Por favor, contacta al administrador.';
                    }
                }
            }
        }
    } else {
        $error = 'Credenciales incorrectas. Intente nuevamente.';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Iniciar Sesión</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>pagina/css/login_styles.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>pagina/css/styles.css">
    <script>
        // Prevent back button navigation
        history.pushState(null, null, location.href);
        window.onpopstate = function() {
            history.go(1);
        };
    </script>
</head>
<body>
    <?php include(__DIR__ . '/partials/header.php'); ?>

    <main class="login-container">
        <h1>Iniciar Sesión</h1>
        <?php if ($error): ?>
            <div class="alert error"><?= $error ?></div>
        <?php endif; ?>
        
        <div id="login-form-container" <?= $showCaptcha ? 'style="display: none;"' : '' ?>>
            <form id="loginForm" method="POST" action="">
                <div class="form-group">
                    <label for="username">Usuario:</label>
                    <input type="text" id="username" name="username" value="<?= htmlspecialchars($currentUser) ?>" required>
                </div>
                <div class="form-group">
                    <label for="password">Contraseña:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="btn">Ingresar</button>
            </form>
            <p>¿No tienes cuenta? <a href="<?= BASE_URL ?>pagina/registrar.php">Regístrate aquí</a></p>
        </div>
        
        <div id="wait-message" style="<?= $showCaptcha ? '' : 'display: none;' ?>">
            <h3>Juega memorama mientras esperas</h3>
            <div class="stats">
                <p>Juegos completados: <span id="games-completed">0</span></p>
                <p>Errores: <span id="error-count">0</span></p>
            </div>
            <div id="memory-game-container" class="memory-game"></div>
            
            <h2>Demasiados intentos fallidos</h2>
            <div id="original-wait-message">
                <p>Por seguridad, debes esperar <span id="countdown"><?= $waitTime ?></span> segundos antes de intentar nuevamente.</p>
                <div class="timer" id="timer"><?= $waitTime ?></div>
            </div>            
        </div>
    </main>

    <?php include(__DIR__ . '/partials/footer.html'); ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="<?= BASE_URL ?>pagina/js/juego.js"></script>
    <script>
        function showWaitMessage(waitSeconds) {
            $('#login-form-container').hide();
            $('#wait-message').show();
            
            const waitAudio = document.getElementById('wait-audio');
            waitAudio.play().catch(e => console.log("No se pudo reproducir audio:", e));
            
            startCountdown(waitSeconds);
        }

        function startCountdown(seconds) {
            let counter = seconds;
            const timerElement = $('#timer');
            const countdownElement = $('#countdown');
            const originalMessage = $('#original-wait-message');
            
            timerElement.text(counter);
            countdownElement.text(counter);
            
            const countdownInterval = setInterval(() => {
                counter--;
                
                if (counter > 0) {
                    timerElement.text(counter);
                    countdownElement.text(counter);
                } else {
                    clearInterval(countdownInterval);
                    originalMessage.replaceWith(`
                        <div class="countdown-complete">
                            <p>Tiempo completado! Puedes intentar iniciar sesión nuevamente.</p>
                            <button onclick="window.location.href='<?= BASE_URL ?>pagina/login.php'" class="btn">Volver a Login</button>
                            <p>Revisa tus credenciales antes de continuar.</p>
                        </div>
                    `);
                    
                    // Update session state via AJAX
                    $.post('<?= BASE_URL ?>controlador/clear_wait.php', {clear_wait: true});
                }
            }, 1000);
        }

        $(document).ready(function() {
            <?php if ($showCaptcha && $waitTime > 0): ?>
                showWaitMessage(<?= $waitTime ?>);
            <?php endif; ?>
        });
    </script>
    <audio id="wait-audio" loop>
        <source src="<?= BASE_URL ?>pagina/audio/the-return-of-the-8-bit-era-301292.mp3" type="audio/mpeg">
        Tu navegador no soporta el elemento de audio.
    </audio>
</body>
</html>