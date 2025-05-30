<?php
class User {
    private $conn;
    private $table = 'usuarios';

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getUserByUsername($username) {
        $query = "SELECT * FROM {$this->table} WHERE usuario = :usuario";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':usuario', $username);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getUsersByRole($roleId) {
        $query = "SELECT * FROM {$this->table} WHERE rol_id = :rol_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':rol_id', $roleId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updatePassword($userId, $newPassword) {
        $query = "UPDATE {$this->table} SET contraseña = :contraseña WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':contraseña', $newPassword);
        $stmt->bindParam(':id', $userId);
        return $stmt->execute();
    }

    public function createUser($nombre, $apellido, $usuario, $contraseña, $correo, $rol_id) {
        $query = "INSERT INTO {$this->table} (nombre, apellido, usuario, contraseña, correo, rol_id) 
                  VALUES (:nombre, :apellido, :usuario, :contraseña, :correo, :rol_id)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':apellido', $apellido);
        $stmt->bindParam(':usuario', $usuario);
        $stmt->bindParam(':contraseña', $contraseña);
        $stmt->bindParam(':correo', $correo);
        $stmt->bindParam(':rol_id', $rol_id);
        
        return $stmt->execute();
    }
}
?>