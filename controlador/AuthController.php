<?php
require_once(__DIR__ . '/../config/paths.php');
require_once(__DIR__ . '/../config/db.php');
require_once(__DIR__ . '/../modelo/User.php');

session_start();

class AuthController {
    private $userModel;

    public function __construct() {
        $database = new Database();
        $db = $database->connect();
        $this->userModel = new User($db);

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function login($usuario, $contraseña) {
        $user = $this->userModel->getUserByUsername($usuario);
        
        if (!$user) {
            return false;
        }
        
        // Handle token login (works even if account is blocked)
        $database = new Database();
        $db = $database->connect();
        $query = "SELECT token FROM tokens 
                WHERE usuario_id = :user_id 
                AND usado = FALSE 
                AND fecha_token >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user['id']);
        $stmt->execute();
        $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($tokenData && $tokenData['token'] === $contraseña) {
            // Generar un nuevo ID de sesión único
            $newSessionId = session_id() . '_' . bin2hex(random_bytes(16));
            
            // Verificar si la columna session_id existe antes de actualizar
            if ($this->checkSessionIdColumnExists()) {
                $this->updateUserSessionId($user['id'], $newSessionId);
            }
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['usuario'];
            $_SESSION['nombre'] = $user['nombre'];
            $_SESSION['apellido'] = $user['apellido'];
            $_SESSION['rol'] = $user['rol_id'];
            $_SESSION['correo'] = $user['correo'];
            $_SESSION['first_login'] = true;
            $_SESSION['current_session_id'] = $newSessionId;
            
            header('Location: ' . BASE_URL . 'pagina/estudiante/contrasenaestudiante.php?token=' . $user['token']);
            exit();
        }
        
        // If account is blocked and no valid token was provided
        if ($user['contrasena'] === 'bloqueado') {
            return false;
        }

        // Normal password check
        $hashedInput = hash('sha256', $contraseña);
        if ($hashedInput === $user['contrasena']) {
            // Regenerar el ID de sesión primero
            session_regenerate_id(true);
            $newSessionId = session_id();
            
            // Actualizar el session_id en la base de datos
            $this->updateUserSessionId($user['id'], $newSessionId);
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['usuario'];
            $_SESSION['nombre'] = $user['nombre'];
            $_SESSION['apellido'] = $user['apellido'];
            $_SESSION['rol'] = $user['rol_id'];
            $_SESSION['correo'] = $user['correo'];
            $_SESSION['current_session_id'] = $newSessionId;

            switch ($user['rol_id']) {
                case 1: header('Location: ' . BASE_URL . 'pagina/admin/perfiladmin.php'); exit();
                case 2: header('Location: ' . BASE_URL . 'pagina/profesor/perfilprofesor.php'); exit();
                case 3: 
                    $_SESSION['first_login'] = $this->isFirstLogin($user['id']);
                    header('Location: ' . BASE_URL . ($_SESSION['first_login'] ? 
                        'pagina/estudiante/bienvenida.php' : 'pagina/estudiante/cursosestudiante.php'));
                    exit();
                default: header('Location: ' . BASE_URL . 'pagina/dashboard.php'); exit();
            }
        }
        return false;
    }
    private function updateUserSessionId($userId, $sessionId) {
        try {
            $database = new Database();
            $db = $database->connect();
            $query = "UPDATE usuarios SET session_id = :session_id WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':session_id', $sessionId);
            $stmt->bindParam(':id', $userId);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error updating session_id: " . $e->getMessage());
            return false;
        }
    }
    private function checkSessionIdColumnExists() {
        try {
            $database = new Database();
            $db = $database->connect();
            $stmt = $db->query("SHOW COLUMNS FROM usuarios LIKE 'session_id'");
            return ($stmt->rowCount() > 0);
        } catch (PDOException $e) {
            error_log("Error checking session_id column: " . $e->getMessage());
            return false;
        }
    }
    public function validateSession() {
        // Primero verificar si hay una sesión activa
        if (!isset($_SESSION['user_id'])) {
            return false;
        }

        // Obtener el usuario actual
        $user = $this->getUserById($_SESSION['user_id']);
        
        // Si no existe el usuario o no tiene session_id, cerrar sesión
        if (!$user || !isset($user['session_id'])) {
            $this->logout();
            header('Location: ' . BASE_URL . 'pagina/login.php?reason=invalid_session');
            exit();
        }

        // Verificar coincidencia de session_id
        if ($user['session_id'] !== $_SESSION['current_session_id']) {
            // Destruir la sesión local pero no limpiar el session_id en BD
            session_unset();
            session_destroy();
            
            // Redirigir con parámetro para mostrar mensaje
            header('Location: ' . BASE_URL . 'pagina/login.php?reason=session_taken');
            exit();
        }

        return true;
    }
    public function isFirstLogin($userId) {
        // Verificar si el estudiante tiene cursos matriculados
        $database = new Database();
        $db = $database->connect();
        $query = "SELECT COUNT(*) as count FROM estudiante_curso WHERE estudiante_id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return ($result['count'] == 0);
    }

    public function register($nombre, $apellido, $usuario, $contraseña, $correo) {
        try {
            // Validación básica
            if (empty($nombre) || empty($apellido) || empty($usuario) || empty($contraseña) || empty($correo)) {
                throw new Exception("Todos los campos son requeridos");
            }

            // Verificar si el usuario o correo ya existen
            if ($this->userModel->checkExistingUser($usuario, $correo)) {
                throw new Exception("El usuario o correo electrónico ya están registrados");
            }

            // Hashear la contraseña con SHA2
            $hashedPassword = hash('sha256', $contraseña);

            // Crear el usuario con rol_id = 3 (estudiante)
            $userId = $this->userModel->createUser($nombre, $apellido, $usuario, $hashedPassword, $correo, 3);
            
            if (!$userId) {
                throw new Exception("Error al completar el registro. Por favor intente nuevamente.");
            }

            // Iniciar sesión automáticamente después del registro
            $_SESSION['user_id'] = $userId;
            $_SESSION['username'] = $usuario;
            $_SESSION['nombre'] = $nombre;
            $_SESSION['apellido'] = $apellido;
            $_SESSION['rol'] = 3;
            $_SESSION['correo'] = $correo;
            $_SESSION['first_login'] = true; // Forzar primera sesión

            return $userId;

        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) {
                throw new Exception("El usuario o correo electrónico ya están registrados");
            }
            throw new Exception("Error técnico al registrar. Por favor intente más tarde.");
        }
    }
    public function createTempUser($nombre, $apellido, $usuario, $email, $token) {
        try {
            // Validación básica
            if (empty($nombre) || empty($apellido) || empty($usuario) || empty($email)) {
                throw new Exception("Todos los campos son requeridos");
            }

            // Verificar si el usuario o correo ya existen (en usuarios o usuarios_temp)
            if ($this->userModel->checkExistingUser($usuario, $email)) {
                throw new Exception("El usuario o correo electrónico ya están registrados");
            }

            // Crear el usuario temporal
            $database = new Database();
            $db = $database->connect();
            $query = "INSERT INTO usuarios_temp 
                    (nombre, apellido, usuario, correo, token) 
                    VALUES (:nombre, :apellido, :usuario, :correo, :token)";
            $stmt = $db->prepare($query);

            $stmt->bindParam(':nombre', $nombre);
            $stmt->bindParam(':apellido', $apellido);
            $stmt->bindParam(':usuario', $usuario);
            $stmt->bindParam(':correo', $email);
            $stmt->bindParam(':token', $token);

            if ($stmt->execute()) {
                return $db->lastInsertId();
            }
            
            throw new Exception("Error al crear usuario temporal");
            
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) {
                throw new Exception("El usuario o correo electrónico ya están registrados");
            }
            throw new Exception("Error técnico al registrar. Por favor intente más tarde.");
        }
    }
    public function completeRegistration($tempUserId, $password) {
        try {
            // Obtener datos del usuario temporal
            $database = new Database();
            $db = $database->connect();
            $query = "SELECT * FROM usuarios_temp WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $tempUserId);
            $stmt->execute();
            $tempUser = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$tempUser) {
                throw new Exception("Usuario temporal no encontrado");
            }
            
            // Hashear la contraseña
            $hashedPassword = hash('sha256', $password);
            
            // Crear usuario real
            $userId = $this->userModel->createUser(
                $tempUser['nombre'],
                $tempUser['apellido'],
                $tempUser['usuario'],
                $hashedPassword,
                $tempUser['correo'],
                3 // Rol de estudiante
            );
            
            if (!$userId) {
                throw new Exception("Error al completar el registro");
            }
            
            // Eliminar usuario temporal
            $query = "DELETE FROM usuarios_temp WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $tempUserId);
            $stmt->execute();
            
            return $userId;
            
        } catch (Exception $e) {
            throw new Exception("Error al completar el registro: " . $e->getMessage());
        }
    }

    public function logout($clearSessionId = true) {
        if ($this->isLoggedIn() && $clearSessionId) {
            // Limpiar el session_id en la base de datos
            $this->updateUserSessionId($_SESSION['user_id'], null);
        }
        // Limpiar todas las variables de sesión
        $_SESSION = array();

        // Borrar la cookie de sesión
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(), 
                '', 
                time() - 42000,
                $params["path"], 
                $params["domain"],
                $params["secure"], 
                $params["httponly"]
            );
        }

        // Destruir la sesión
        session_destroy();

        // Redirigir al inicio con headers para evitar caché
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
        header("Location: " . BASE_URL . "pagina/index.php");
        exit();
    }
    public function isLoggedIn() {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['current_session_id'])) {
            return false;
        }
        
        return $this->validateSession();
    }

    public function checkRole($requiredRole) {
        if (!$this->isLoggedIn() || $_SESSION['rol'] != $requiredRole) {
            header('Location: ' . BASE_URL . 'pagina/login.php');
            exit();
        }
    }

    public function getUserById($userId) {
        $database = new Database();
        $db = $database->connect();
        $query = "SELECT * FROM usuarios WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $userId);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function getUserByToken($token) {
        $database = new Database();
        $db = $database->connect();
        $query = "SELECT u.* FROM usuarios u 
                JOIN tokens t ON u.id = t.usuario_id
                WHERE t.token = :token 
                AND t.usado = FALSE
                AND t.fecha_token >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function clearToken($userId) {
        $database = new Database();
        $db = $database->connect();
        $query = "UPDATE tokens SET usado = TRUE WHERE usuario_id = :id AND usado = FALSE";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $userId);
        return $stmt->execute();
    }
    public function sendPasswordResetEmail($userId, $token) {
        $user = $this->getUserById($userId);
        if (!$user || empty($user['correo'])) {
            return false;
        }

        $resetLink = BASE_URL . 'pagina/restablecimiento.php?token=' . urlencode($token);

        try {
            // Eliminar tokens antiguos no usados para este usuario
            $database = new Database();
            $db = $database->connect();
            $query = "DELETE FROM tokens WHERE usuario_id = :user_id AND usado = FALSE";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            
            // Insertar nuevo token en la tabla tokens
            $query = "INSERT INTO tokens (usuario_id, token) VALUES (:user_id, :token)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':token', $token);
            $stmt->execute();

            // Configurar PHPMailer
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            
            // Configuración SMTP (mantener igual)
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'demetrio7000@gmail.com';
            $mail->Password = 'weln ldhi bwwn daoh';
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;
            $mail->CharSet = 'UTF-8';

            // Remitente y destinatario
            $mail->setFrom('no-reply@escuela.com', 'Escuela');
            $mail->addAddress($user['correo'], $user['nombre'] . ' ' . $user['apellido']);

            // Contenido del email (mantener igual)
            $mail->isHTML(true);
            $mail->Subject = 'Restablecimiento de contraseña';
            
            $mail->Body = "
            <html>
            <head>
                <title>Restablecimiento de contraseña</title>
            </head>
            <body>
                <h2>Restablecimiento de contraseña</h2>
                <p>Hola {$user['nombre']} {$user['apellido']},</p>
                <p>Se ha solicitado un restablecimiento de contraseña para tu cuenta.</p>
                <p>Tu token de acceso es: <strong>{$token}</strong></p>
                <p>Por favor, haz clic en el siguiente enlace para establecer una nueva contraseña:</p>
                <p><a href='{$resetLink}'>{$resetLink}</a></p>
                <p>Este token expirará en 58 minutos.</p>
                <p>Si no solicitaste este cambio, por favor ignora este correo.</p>
            </body>
            </html>
            ";

            // Enviar el correo
            $mail->send();
            
            // Registrar en el log de depuración
            $debug_content = "Email enviado a: ".$user['correo']."\n";
            $debug_content .= "Asunto: Restablecimiento de contraseña\n";
            $debug_content .= "Token: ".$token."\n";
            $debug_content .= "Enlace: ".$resetLink."\n";
            $debug_content .= "------\n";
            file_put_contents('C:\xampp\htdocs\DEM\Escuela\mail_debug.log', $debug_content, FILE_APPEND);

            return true;
        } catch (Exception $e) {
            // Registrar el error en el log
            $error_content = "Error al enviar email a ".$user['correo'].": ".$mail->ErrorInfo."\n";
            file_put_contents('C:\xampp\htdocs\DEM\Escuela\mail_error.log', $error_content, FILE_APPEND);
            
            return false;
        }
    } 
    public function sendRecoveryEmail($email) {
        // Buscar usuario por email
        $database = new Database();
        $db = $database->connect();
        $query = "SELECT id, nombre, apellido, usuario FROM usuarios WHERE correo = :email";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            return true; // Por seguridad
        }
        
        // Generar token: username + 6 dígitos aleatorios
        $randomDigits = mt_rand(100000, 999999);
        $token = $user['usuario'] . $randomDigits;
        
        // Insertar o actualizar token en la tabla tokens
        $query = "INSERT INTO tokens (usuario_id, token) 
                VALUES (:user_id, :token)
                ON DUPLICATE KEY UPDATE 
                token = VALUES(token), 
                fecha_token = NOW(), 
                usado = FALSE";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':user_id', $user['id']);
        
        if (!$stmt->execute()) {
            error_log("Error al actualizar token para usuario ID: " . $user['id']);
            return false;
        }
        
        // Enviar correo con el token
        $resetLink = BASE_URL . 'pagina/restablecimiento.php?token=' . urlencode($token);
        
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        try {
            // Configuración SMTP (usando la misma que ya tienes)
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'demetrio7000@gmail.com';
            $mail->Password = 'weln ldhi bwwn daoh';
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;
            $mail->CharSet = 'UTF-8';

            // Remitente y destinatario
            $mail->setFrom('no-reply@escuela.com', 'Soporte Escuela');
            $mail->addAddress($email, $user['nombre'] . ' ' . $user['apellido']);

            // Contenido del email
            $mail->isHTML(true);
            $mail->Subject = 'Restablecimiento de contraseña';
            
            $mail->Body = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; }
                    .button {
                        display: inline-block;
                        padding: 10px 20px;
                        background: #4CAF50;
                        color: white;
                        text-decoration: none;
                        border-radius: 4px;
                    }
                </style>
            </head>
            <body>
                <h2>Restablecer tu contraseña</h2>
                <p>Hola {$user['nombre']},</p>
                <p>Hemos recibido una solicitud para restablecer la contraseña de tu cuenta.</p>
                <p>Por favor, haz clic en el siguiente botón para continuar:</p>
                <p><a href='{$resetLink}' class='button'>Restablecer contraseña</a></p>
                <p>Si no solicitaste este cambio, puedes ignorar este mensaje.</p>
                <p>Este enlace expirará en 24 horas.</p>
            </body>
            </html>
            ";

            // Versión alternativa sin HTML
            $mail->AltBody = "Hola {$user['nombre']},\n\n" .
                            "Para restablecer tu contraseña, visita este enlace:\n" .
                            "{$resetLink}\n\n" .
                            "Si no solicitaste este cambio, ignora este mensaje.\n\n" .
                            "Este enlace expirará en 24 horas.";

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Error al enviar email de recuperación a $email: " . $mail->ErrorInfo);
            return false;
        }
    }
}

// Manejar acciones desde URL
if (isset($_GET['action'])) {
    $auth = new AuthController();
    switch ($_GET['action']) {
        case 'logout':
            $auth->logout();
            break;
    }
}
// Manejar acciones de logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    $auth = new AuthController();
    $auth->logout();
}
?>