<?php
require_once(__DIR__ . '/../../config/paths.php');
require_once(__DIR__ . '/../../controlador/AuthController.php');
require_once(__DIR__ . '/../../controlador/AdminController.php');
require_once(__DIR__ . '/../../config/no_cache.php');

$auth = new AuthController();
if (!$auth->isLoggedIn()) {
    header('Location: ' . BASE_URL . 'pagina/login.php');
    exit();
}
$auth->checkRole(1); // Solo admin

$adminController = new AdminController();
$cursos = $adminController->getCursos();

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $nombre = $_POST['nombre'] ?? '';
                $descripcion = $_POST['descripcion'] ?? '';
                $profesor_id = $_POST['profesor_id'] ?? null;
                
                if (empty($profesor_id)) {
                    // Asignar el primer profesor por defecto si no se seleccionó
                    $database = new Database();
                    $db = $database->connect();
                    $query = "SELECT id FROM usuarios WHERE rol_id = 2 LIMIT 1";
                    $stmt = $db->query($query);
                    $profesor = $stmt->fetch(PDO::FETCH_ASSOC);
                    $profesor_id = $profesor['id'] ?? null;
                }
                
                if ($nombre && $profesor_id) {
                    $database = new Database();
                    $db = $database->connect();
                    $query = "INSERT INTO cursos (nombre, descripcion, profesor_id) VALUES (?, ?, ?)";
                    $stmt = $db->prepare($query);
                    $stmt->execute([$nombre, $descripcion, $profesor_id]);
                    header("Location: crud_curso.php?success=add");
                    exit();
                }
                break;
                
            case 'edit':
                $id = $_POST['id'] ?? 0;
                $nombre = $_POST['nombre'] ?? '';
                $descripcion = $_POST['descripcion'] ?? '';
                $profesor_id = $_POST['profesor_id'] ?? null;
                
                if ($id && $nombre && $profesor_id) {
                    $database = new Database();
                    $db = $database->connect();
                    $query = "UPDATE cursos SET nombre = ?, descripcion = ?, profesor_id = ? WHERE id = ?";
                    $stmt = $db->prepare($query);
                    $stmt->execute([$nombre, $descripcion, $profesor_id, $id]);
                    header("Location: crud_curso.php?success=edit");
                    exit();
                }
                break;
                
            case 'delete':
                $id = $_POST['id'] ?? 0;
                if ($id) {
                    // Verificar si el curso tiene alumnos matriculados
                    $database = new Database();
                    $db = $database->connect();
                    
                    // Consulta para obtener alumnos matriculados
                    $query = "SELECT u.nombre, u.apellido 
                              FROM estudiante_curso ec
                              JOIN usuarios u ON ec.estudiante_id = u.id
                              WHERE ec.curso_id = ?";
                    $stmt = $db->prepare($query);
                    $stmt->execute([$id]);
                    $alumnos = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if (count($alumnos) > 0) {
                        // Mostrar alerta con lista de alumnos
                        $alumnosList = array_map(function($alumno) {
                            return $alumno['nombre'] . ' ' . $alumno['apellido'];
                        }, $alumnos);
                        
                        echo "<script>
                            alert('No se puede eliminar el curso porque tiene alumnos matriculados:\\n\\n" . 
                            implode("\\n", $alumnosList) . "');
                            window.location.href = 'crud_curso.php';
                        </script>";
                        exit();
                    } else {
                        // Eliminar el curso si no tiene alumnos
                        $query = "DELETE FROM cursos WHERE id = ?";
                        $stmt = $db->prepare($query);
                        $stmt->execute([$id]);
                        header("Location: crud_curso.php?success=delete");
                        exit();
                    }
                }
                break;
        }
    }
}

// Obtener lista de profesores para los formularios
$database = new Database();
$db = $database->connect();
$query = "SELECT id, nombre, apellido FROM usuarios WHERE rol_id = 2";
$profesores = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Cursos</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>pagina/css/styles.css">
    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
        }
        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 500px;
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <!-- Header con sesión -->
    <?php include(__DIR__ . '/../partials/headersesion.php'); ?>

    <main class="dashboard">
        <?php include(__DIR__ . '/../partials/menuadmin.php'); ?>

        <div class="content">
            <h1>Gestión de Cursos</h1>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="alert success">
                    <?php 
                    switch ($_GET['success']) {
                        case 'add': echo 'Curso agregado correctamente'; break;
                        case 'edit': echo 'Curso modificado correctamente'; break;
                        case 'delete': echo 'Curso eliminado correctamente'; break;
                    }
                    ?>
                </div>
            <?php endif; ?>
            
            <div class="actions">
                <button onclick="openModal('add')" class="btn">Agregar Nuevo Curso</button>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Profesor</th>
                        <th>Descripción</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cursos as $curso): ?>
                    <tr>
                        <td><?= htmlspecialchars($curso['id']) ?></td>
                        <td><?= htmlspecialchars($curso['nombre']) ?></td>
                        <td><?= htmlspecialchars($curso['profesor_nombre'] . ' ' . $curso['profesor_apellido']) ?></td>
                        <td><?= htmlspecialchars($curso['descripcion']) ?></td>
                        <td class="actions">
                            <div class="actions-container">
                                <button onclick="openModal('edit', <?= $curso['id'] ?>, '<?= htmlspecialchars($curso['nombre'], ENT_QUOTES) ?>', '<?= htmlspecialchars($curso['descripcion'], ENT_QUOTES) ?>', <?= $curso['profesor_id'] ?>)" class="btn small">Editar</button>
                                <button onclick="confirmDelete(<?= $curso['id'] ?>)" class="btn small danger">Eliminar</button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>

    <!-- Modales -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('add')">&times;</span>
            <h2>Agregar Nuevo Curso</h2>
            <form method="POST" action="crud_curso.php">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label for="nombre">Nombre:</label>
                    <input type="text" id="nombre" name="nombre" required>
                </div>
                <div class="form-group">
                    <label for="descripcion">Descripción:</label>
                    <textarea id="descripcion" name="descripcion"></textarea>
                </div>
                <div class="form-group">
                    <label for="profesor_id">Profesor:</label>
                    <select id="profesor_id" name="profesor_id">
                        <?php foreach ($profesores as $profesor): ?>
                            <option value="<?= $profesor['id'] ?>"><?= htmlspecialchars($profesor['nombre'] . ' ' . $profesor['apellido']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn">Guardar</button>
            </form>
        </div>
    </div>

    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('edit')">&times;</span>
            <h2>Editar Curso</h2>
            <form method="POST" action="crud_curso.php">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" id="edit_id" name="id">
                <div class="form-group">
                    <label for="edit_nombre">Nombre:</label>
                    <input type="text" id="edit_nombre" name="nombre" required>
                </div>
                <div class="form-group">
                    <label for="edit_descripcion">Descripción:</label>
                    <textarea id="edit_descripcion" name="descripcion"></textarea>
                </div>
                <div class="form-group">
                    <label for="edit_profesor_id">Profesor:</label>
                    <select id="edit_profesor_id" name="profesor_id">
                        <?php foreach ($profesores as $profesor): ?>
                            <option value="<?= $profesor['id'] ?>"><?= htmlspecialchars($profesor['nombre'] . ' ' . $profesor['apellido']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn">Guardar Cambios</button>
            </form>
        </div>
    </div>

    <form id="deleteForm" method="POST" action="crud_curso.php">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" id="delete_id" name="id">
    </form>

    <!-- Footer -->
    <?php include(__DIR__ . '/../partials/footer.html'); ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function openModal(action, id = null, nombre = '', descripcion = '', profesor_id = null) {
            if (action === 'add') {
                document.getElementById('addModal').style.display = 'block';
            } else if (action === 'edit') {
                document.getElementById('edit_id').value = id;
                document.getElementById('edit_nombre').value = nombre;
                document.getElementById('edit_descripcion').value = descripcion;
                if (profesor_id) {
                    document.getElementById('edit_profesor_id').value = profesor_id;
                }
                document.getElementById('editModal').style.display = 'block';
            }
        }

        function closeModal(action) {
            if (action === 'add') {
                document.getElementById('addModal').style.display = 'none';
            } else if (action === 'edit') {
                document.getElementById('editModal').style.display = 'none';
            }
        }

        function confirmDelete(id) {
            if (confirm('¿Estás seguro de que deseas eliminar este curso?')) {
                document.getElementById('delete_id').value = id;
                document.getElementById('deleteForm').submit();
            }
        }

        // Cerrar modal al hacer clic fuera de él
        window.onclick = function(event) {
            if (event.target.className === 'modal') {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>