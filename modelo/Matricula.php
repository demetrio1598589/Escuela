<?php
class Matricula {
    private $conn;
    private $table = 'estudiante_curso';

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getCursosByEstudiante($estudianteId) {
        $query = "SELECT c.*, ec.nota 
                  FROM {$this->table} ec
                  JOIN cursos c ON ec.curso_id = c.id
                  WHERE ec.estudiante_id = :estudiante_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':estudiante_id', $estudianteId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getEstudiantesByCurso($cursoId) {
        $query = "SELECT u.*, ec.nota 
                  FROM {$this->table} ec
                  JOIN usuarios u ON ec.estudiante_id = u.id
                  WHERE ec.curso_id = :curso_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':curso_id', $cursoId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($estudianteId, $cursoId) {
        $query = "INSERT INTO {$this->table} (estudiante_id, curso_id) 
                  VALUES (:estudiante_id, :curso_id)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':estudiante_id', $estudianteId);
        $stmt->bindParam(':curso_id', $cursoId);
        return $stmt->execute();
    }
}
?>