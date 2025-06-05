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
    public function getUserById($userId) {
        return $this->userModel->getUserById($userId);
    }
    public function getCursos() {
        return $this->cursoModel->getAllCursos();
    }
    public function getEstudiantes() {
        return $this->userModel->getUsersByRole(3); // Rol 3 = estudiante
    }
    public function searchEstudiantes($searchTerm) {
        $database = new Database();
        $db = $database->connect();
        
        $query = "SELECT * FROM usuarios 
                WHERE rol_id = 3 
                AND (nombre LIKE :search OR apellido LIKE :search)
                ORDER BY apellido ASC, nombre ASC";
                
        $stmt = $db->prepare($query);
        $searchParam = "%" . $searchTerm . "%";
        $stmt->bindParam(':search', $searchParam);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function resetPassword($userId, $newPassword) {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        return $this->userModel->updatePassword($userId, $hashedPassword);
    }
    public function actualizarEstudiante($datos) {
        try {
            $database = new Database();
            $db = $database->connect();
            
            $query = "UPDATE usuarios SET 
                    nombre = :nombre,
                    apellido = :apellido,
                    usuario = :usuario,
                    correo = :correo
                    WHERE id = :id AND rol_id = 3";
                    
            $stmt = $db->prepare($query);
            $stmt->bindParam(':nombre', $datos['nombre']);
            $stmt->bindParam(':apellido', $datos['apellido']);
            $stmt->bindParam(':usuario', $datos['usuario']);
            $stmt->bindParam(':correo', $datos['correo']);
            $stmt->bindParam(':id', $datos['id']);
            
            return $stmt->execute();
            
        } catch (PDOException $e) {
            error_log("Error al actualizar estudiante: " . $e->getMessage());
            return false;
        }
    }
    public function retirarEstudiante($id) {
        try {
            $database = new Database();
            $db = $database->connect();
            
            // Primero eliminamos las relaciones en otras tablas
            $query = "DELETE FROM estudiante_curso WHERE estudiante_id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            // Luego eliminamos al estudiante
            $query = "DELETE FROM usuarios WHERE id = :id AND rol_id = 3";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $id);
            
            return $stmt->execute();
            
        } catch (PDOException $e) {
            error_log("Error al retirar estudiante: " . $e->getMessage());
            return false;
        }
    }
}
?>