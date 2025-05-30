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
    }

    public function login($usuario, $contraseña) {
        $user = $this->userModel->getUserByUsername($usuario);
        
        if ($user) {
            // Verificar contraseña (SHA2 en la DB vs input hasheado)
            $hashedInput = hash('sha256', $contraseña);
            if ($hashedInput === $user['contraseña']) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['usuario'];
                $_SESSION['nombre'] = $user['nombre'];
                $_SESSION['apellido'] = $user['apellido'];
                $_SESSION['rol'] = $user['rol_id'];
                $_SESSION['correo'] = $user['correo'];
                return true;
            }
        }
        return false;
    }

    public function register($nombre, $apellido, $usuario, $contraseña, $correo, $rol_id) {
        // Verificar si el usuario ya existe
        if ($this->userModel->getUserByUsername($usuario)) {
            return false;
        }

        // Hashear la contraseña con SHA-256 (como está en la DB)
        $hashedPassword = hash('sha256', $contraseña);

        // Crear el usuario
        return $this->userModel->createUser($nombre, $apellido, $usuario, $hashedPassword, $correo, $rol_id);
    }

    public function logout() {
        session_unset();
        session_destroy();
        header('Location: ' . BASE_URL . 'pagina/index.php');
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
?>