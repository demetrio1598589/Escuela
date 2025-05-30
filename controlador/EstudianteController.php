<?php
require_once(__DIR__ . '/../config/db.php');
require_once(__DIR__ . '/../modelo/Curso.php');
require_once(__DIR__ . '/../modelo/Matricula.php');

class EstudianteController {
    private $cursoModel;
    private $matriculaModel;

    public function __construct() {
        $database = new Database();
        $db = $database->connect();
        $this->cursoModel = new Curso($db);
        $this->matriculaModel = new Matricula($db);
    }

    public function getCursosDisponibles() {
        return $this->cursoModel->getAllCursos();
    }

    public function getMisCursos($estudianteId) {
        return $this->matriculaModel->getCursosByEstudiante($estudianteId);
    }

    public function matricularCurso($estudianteId, $cursoId) {
        return $this->matriculaModel->create($estudianteId, $cursoId);
    }
}
?>