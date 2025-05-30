<?php
require_once(__DIR__ . '/../config/db.php');
require_once(__DIR__ . '/../modelo/Curso.php');
require_once(__DIR__ . '/../modelo/Matricula.php');

class ProfesorController {
    private $cursoModel;
    private $matriculaModel;

    public function __construct() {
        $database = new Database();
        $db = $database->connect();
        $this->cursoModel = new Curso($db);
        $this->matriculaModel = new Matricula($db);
    }

    public function getMisCursos($profesorId) {
        return $this->cursoModel->getCursosByProfesor($profesorId);
    }

    public function getAlumnosPorCurso($cursoId) {
        return $this->matriculaModel->getEstudiantesByCurso($cursoId);
    }
}
?>