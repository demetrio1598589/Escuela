<?php
class Curso {
    private $conn;
    private $table = 'cursos';

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAllCursos() {
        $query = "SELECT c.*, u.nombre as profesor_nombre, u.apellido as profesor_apellido 
                  FROM {$this->table} c
                  JOIN usuarios u ON c.profesor_id = u.id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCursosByProfesor($profesorId) {
        $query = "SELECT * FROM {$this->table} WHERE profesor_id = :profesor_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':profesor_id', $profesorId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCursoById($cursoId) {
        $query = "SELECT * FROM {$this->table} WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $cursoId);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>