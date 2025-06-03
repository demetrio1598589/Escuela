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
        if (!empty($user['token']) && $user['token'] === $contraseña) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['usuario'];
            $_SESSION['nombre'] = $user['nombre'];
            $_SESSION['apellido'] = $user['apellido'];
            $_SESSION['rol'] = $user['rol_id'];
            $_SESSION['correo'] = $user['correo'];
            $_SESSION['first_login'] = true;
            
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
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['usuario'];
            $_SESSION['nombre'] = $user['nombre'];
            $_SESSION['apellido'] = $user['apellido'];
            $_SESSION['rol'] = $user['rol_id'];
            $_SESSION['correo'] = $user['correo'];

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

    public function logout() {
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
        return isset($_SESSION['user_id']);
    }

    public function checkRole($requiredRole) {
        if (!$this->isLoggedIn() || $_SESSION['rol'] != $requiredRole) {
            header('Location: ' . BASE_URL . 'pagina/login.php');
            exit();
        }
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

    public function getUserByToken($token) {
        $database = new Database();
        $db = $database->connect();
        $query = "SELECT * FROM usuarios WHERE token = :token";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
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

    public function clearToken($userId) {
        $database = new Database();
        $db = $database->connect();
        $query = "UPDATE usuarios SET token = NULL WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $userId);
        return $stmt->execute();
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