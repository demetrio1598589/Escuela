<?php
require_once(__DIR__ . '/../config/db.php');
require_once(__DIR__ . '/../modelo/User.php');
require_once(__DIR__ . '/../modelo/Curso.php');

class AdminController {
    private $userModel;
    private $cursoModel;

    public function __construct() {
        $database = new Database();
        $db = $database->connect();
        $this->userModel = new User($db);
        $this->cursoModel = new Curso($db);
    }

    public function getCursos() {
        return $this->cursoModel->getAllCursos();
    }

    public function getEstudiantes() {
        return $this->userModel->getUsersByRole(3); // Rol 3 = estudiante
    }

    public function resetPassword($userId, $newPassword) {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        return $this->userModel->updatePassword($userId, $hashedPassword);
    }
}
?>