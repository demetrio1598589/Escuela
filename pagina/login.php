<?php
require_once(__DIR__ . '/../config/paths.php');
require_once(__DIR__ . '/../controlador/AuthController.php');
require_once(__DIR__ . '/../config/no_cache.php');
require_once(__DIR__ . '/../modelo/User.php');

$database = new Database();
$db = $database->connect();
$userModel = new User($db);

$auth = new AuthController();
// Si ya está logueado, redirigir según rol
if ($auth->isLoggedIn()) {
    switch ($_SESSION['rol']) {
        case 1: // Admin
            header('Location: ' . BASE_URL . 'pagina/admin/perfiladmin.php');
            break;
        case 2: // Profesor
            header('Location: ' . BASE_URL . 'pagina/profesor/perfilprofesor.php');
            break;
        case 3: // Estudiante
            if ($_SESSION['first_login']) {
                header('Location: ' . BASE_URL . 'pagina/estudiante/bienvenida.php');
            } else {
                header('Location: ' . BASE_URL . 'pagina/estudiante/cursosestudiante.php');
            }
            break;
    }
    exit();
}

// Inicializar contadores de intentos fallidos
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['last_failed_attempt'] = 0;
    $_SESSION['block_count'] = []; // Para llevar cuenta de bloqueos por usuario
}

$error = '';
$showCaptcha = ($_SESSION['login_attempts'] >= 3);
$waitTime = 0;

if ($showCaptcha) {
    $currentTime = time();
    $lastAttempt = $_SESSION['last_failed_attempt'];
    $elapsed = $currentTime - $lastAttempt;
    
    if ($elapsed < 20) {
        $waitTime = 20 - $elapsed;
        $showCaptcha = true;
        
        // Incrementar contador de bloqueos para este usuario
        if (isset($_POST['username'])) {
            $username = $_POST['username'];
            if (!isset($_SESSION['block_count'][$username])) {
                $_SESSION['block_count'][$username] = 0;
            }
            $_SESSION['block_count'][$username]++;
            
            // Si es el segundo bloqueo, bloquear la cuenta
            if ($_SESSION['block_count'][$username] >= 2) {
                $userData = $userModel->getUserByUsername($username);
                if ($userData) {
                    $userModel->blockUser($userData['id']);
                }
            }
        }
    } else {
        $_SESSION['login_attempts'] = 0;
        $showCaptcha = false;
        $error = '';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = $_POST['username'] ?? '';
    $contraseña = $_POST['password'] ?? '';
    
    // Verificar si la cuenta está bloqueada
    $userData = $userModel->getUserByUsername($usuario);
    if ($userData && $userData['contrasena'] === 'bloqueado') {
        $error = 'Tu cuenta ha sido bloqueada. Por favor, contacta al administrador.';
    } elseif ($showCaptcha && $waitTime > 0) {
        echo '<script>$(document).ready(function() { showWaitMessage('.$waitTime.'); });</script>';
    } else {
        if ($auth->login($usuario, $contraseña)) {
            // Resetear contadores al loguearse correctamente
            $_SESSION['login_attempts'] = 0;
            $_SESSION['last_failed_attempt'] = 0;
            if (isset($_SESSION['block_count'][$usuario])) {
                unset($_SESSION['block_count'][$usuario]);
            }
            
            switch ($_SESSION['rol']) {
                case 1: // Admin
                    header('Location: ' . BASE_URL . 'pagina/admin/perfiladmin.php');
                    break;
                case 2: // Profesor
                    header('Location: ' . BASE_URL . 'pagina/profesor/perfilprofesor.php');
                    break;
                case 3: // Estudiante
                    header('Location: ' . BASE_URL . 'pagina/estudiante/perfilestudiante.php');
                    break;
            }
            exit();
        } else {
            $error = 'Credenciales incorrectas. Intente nuevamente.';
            $_SESSION['login_attempts']++;
            $_SESSION['last_failed_attempt'] = time();
            
            if ($_SESSION['login_attempts'] >= 3) {
                $showCaptcha = true;
                $waitTime = 20;
            }
        }
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
</head>
<body>
    <!-- Header -->
    <?php include(__DIR__ . '/partials/header.php'); ?>

    <main class="login-container">
        <h1>Iniciar Sesión</h1>
            <?php if ($error): ?>
                <div class="alert error">
                    <?php 
                    if (isset($userData) && $userData['contrasena'] === 'bloqueado') {
                        echo 'Tu cuenta ha sido bloqueada por seguridad. <br>Por favor, contacta al administrador para recuperar el acceso.';
                    } else {
                        echo $error;
                    }
                    ?>
                </div>
            <?php endif; ?>
        
        <div id="login-form-container">
            <form id="loginForm" method="POST" action="">
                <div class="form-group">
                    <label for="username">Usuario:</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="password">Contraseña:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="btn">Ingresar</button>
            </form>
            <p>¿No tienes cuenta? <a href="<?= BASE_URL ?>pagina/registrar.php">Regístrate aquí</a></p>
        </div>
        
        <div id="wait-message" style="display: none;">
            <h3>Juega memorama mientras esperas</h3>
            <div class="stats">
                <p>Juegos completados: <span id="games-completed">0</span></p>
                <p>Errores: <span id="error-count">0</span></p>
            </div>
            <div id="memory-game-container" class="memory-game"></div>
            
            <h2>Demasiados intentos fallidos</h2>
            <div id="original-wait-message">
                <p>Por seguridad, debes esperar <span id="countdown">30</span> segundos antes de intentar nuevamente.</p>
                <div class="timer" id="timer">30</div>
            </div>            
        </div>
    </main>

    <!-- Footer -->
    <?php include(__DIR__ . '/partials/footer.html'); ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script>        
        let gamesCompleted = 0;
        function showWaitMessage(waitSeconds) {
            console.log("Mostrando mensaje de espera con", waitSeconds, "segundos");
            $('#login-form-container').hide();
            $('#wait-message').show();
            
            // Iniciar audio de espera
            const waitAudio = document.getElementById('wait-audio');
            waitAudio.play().catch(e => console.log("No se pudo reproducir audio:", e));
            
            // Inicializar contadores
            $('#error-count').text('0');
            $('#games-completed').text('0');
            
            initializeMemoryGame();
            startCountdown(waitSeconds);
            
            // Prevenir navegación hacia atrás
            history.pushState(null, null, location.href);
            window.onpopstate = function() {
                history.go(1);
            };
        }

        function startCountdown(seconds) {
            let counter = seconds;
            const timerElement = $('#timer');
            const countdownElement = $('#countdown');
            const originalMessage = $('#original-wait-message');
            const waitAudio = document.getElementById('wait-audio');
            
            // Mostrar el valor inicial
            timerElement.text(counter);
            countdownElement.text(counter);
            
            const countdownInterval = setInterval(() => {
                counter--;
                
                if (counter > 0) {
                    timerElement.text(counter);
                    countdownElement.text(counter);
                } else {
                    clearInterval(countdownInterval);
                    // Detener audio de espera
                    waitAudio.pause();
                    waitAudio.currentTime = 0;
                    
                    // Reemplazar completamente el mensaje original
                    originalMessage.replaceWith(`
                        <div class="countdown-complete">
                            <p>Tiempo completado! Puedes intentar iniciar sesión nuevamente.</p>
                            <button onclick="window.location.reload()" class="btn">Volver a Login</button>
                            <p>Revisa tus credenciales antes de continuar.</p>
                        </div>
                    `);
                }
            }, 1000);
        }

        function initializeMemoryGame() {
            const symbols = ['🐶', '🐱', '🐭', '🐰', '🦊', '🐼'];
            const cards = [...symbols, ...symbols]; // Duplicar para hacer pares
            
            // Barajar las cartas
            for (let i = cards.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [cards[i], cards[j]] = [cards[j], cards[i]];
            }
            
            // Crear el tablero
            const gameContainer = $('#memory-game-container');
            gameContainer.empty();
            
            let flippedCards = [];
            let matchedPairs = 0;
            let errorCount = 0;
            let canFlip = true;
            
            cards.forEach((symbol, index) => {
                const card = $('<div>').addClass('memory-card').attr('data-index', index);
                card.text('?').attr('data-symbol', symbol);
                
                card.click(function() {
                    if (!canFlip || $(this).hasClass('flipped') || $(this).hasClass('matched')) {
                        return;
                    }
                    
                    // Voltear la carta
                    $(this).addClass('flipped').text($(this).data('symbol'));
                    flippedCards.push($(this));
                    
                    // Cuando tenemos 2 cartas volteadas
                    if (flippedCards.length === 2) {
                        canFlip = false;
                        const card1 = flippedCards[0];
                        const card2 = flippedCards[1];
                        
                        if (card1.data('symbol') === card2.data('symbol')) {
                            // Es un par - mantenerlas visibles
                            card1.addClass('matched');
                            card2.addClass('matched');
                            flippedCards = [];
                            matchedPairs++;
                            canFlip = true;
                            
                            // Verificar si se completó el juego
                            if (matchedPairs === symbols.length) {
                                gamesCompleted++;
                                $('#games-completed').text(gamesCompleted);
                                setTimeout(() => {
                                    initializeMemoryGame(); // Reiniciar juego
                                }, 500);
                            }
                        } else {
                            // No es un par - incrementar contador de errores
                            errorCount++;
                            $('#error-count').text(errorCount);
                            
                            // Esperar 0.5 segundos antes de voltear las cartas
                            setTimeout(() => {
                                card1.removeClass('flipped').text('?');
                                card2.removeClass('flipped').text('?');
                                flippedCards = [];
                                canFlip = true;
                            }, 500);
                        }
                    }
                });
                
                gameContainer.append(card);
            });
        }

        // Iniciar el juego cuando el documento esté listo
        $(document).ready(function() {
            initializeMemoryGame();
            
            <?php if ($showCaptcha && $waitTime > 0): ?>
                showWaitMessage(<?= $waitTime ?>);
            <?php endif; ?>
        });

        function startCountdown(seconds) {
            let counter = seconds;
            const timerElement = $('#timer');
            const countdownElement = $('#countdown');
            const originalMessage = $('#original-wait-message');
            
            // Mostrar el valor inicial
            timerElement.text(counter);
            countdownElement.text(counter);
            
            const countdownInterval = setInterval(() => {
                counter--;
                
                if (counter > 0) {
                    timerElement.text(counter);
                    countdownElement.text(counter);
                } else {
                    clearInterval(countdownInterval);
                    // Reemplazar completamente el mensaje original
                    originalMessage.replaceWith(`
                        <div class="countdown-complete">
                            <p>Tiempo completado! Puedes intentar iniciar sesión nuevamente.</p>
                            <button onclick="window.location.reload()" class="btn">Volver a Login</button>
                            <p>Revisa tus credenciales antes de continuar.</p>
                        </div>
                    `);
                }
            }, 1000);
        }
        
        function toggleSound() {
            alert('Funcionalidad de sonido será implementada próximamente');
        }
        $(document).on('click', '.btn[onclick*="reload"]', function() {
            const waitAudio = document.getElementById('wait-audio');
            waitAudio.pause();
            waitAudio.currentTime = 0;
            window.location.reload();
        });
    </script>
    <audio id="wait-audio" loop>
    <source src="<?= BASE_URL ?>pagina/audio/the-return-of-the-8-bit-era-301292.mp3" type="audio/mpeg">
    Tu navegador no soporta el elemento de audio.
</audio>
</body>
</html>