<?php
require_once(__DIR__ . '/../config/paths.php');
require_once(__DIR__ . '/../config/db.php');
require_once(__DIR__ . '/../modelo/User.php');
class User {
    private $conn;
    private $table = 'usuarios';
    private $lastError = null;

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
        $query = "UPDATE {$this->table} SET contrasena = :contrasena WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':contrasena', $newPassword);
        $stmt->bindParam(':id', $userId);
        return $stmt->execute();
    }
    public function createUser($nombre, $apellido, $usuario, $contraseña, $correo, $rol_id) {
        try {
            $query = "INSERT INTO {$this->table} 
                     (nombre, apellido, usuario, contrasena, correo, rol_id) 
                     VALUES (:nombre, :apellido, :usuario, :contrasena, :correo, :rol_id)";
            $stmt = $this->conn->prepare($query);

            $stmt->bindParam(':nombre', $nombre, PDO::PARAM_STR);
            $stmt->bindParam(':apellido', $apellido, PDO::PARAM_STR);
            $stmt->bindParam(':usuario', $usuario, PDO::PARAM_STR);
            $stmt->bindParam(':contrasena', $contraseña, PDO::PARAM_STR);
            $stmt->bindParam(':correo', $correo, PDO::PARAM_STR);
            $stmt->bindParam(':rol_id', $rol_id, PDO::PARAM_INT);

            if ($stmt->execute()) {
                return $this->conn->lastInsertId();
            }
            
            return false;
            
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            echo "Excepción PDO: " . $this->lastError . "\n";
            return false;
        }
    }
    public function checkExistingUser($username, $email) {
        $query = "SELECT COUNT(*) as count FROM usuarios 
                WHERE usuario = :usuario OR correo = :correo
                UNION
                SELECT COUNT(*) as count FROM usuarios_temp
                WHERE usuario = :usuario OR correo = :correo";
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':usuario', $username);
        $stmt->bindParam(':correo', $email);
        
        if ($stmt->execute()) {
            $count = 0;
            while ($result = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $count += $result['count'];
            }
            return ($count > 0);
        }
        
        return false;
    }
    public function getLastError() {
        return $this->lastError;
    }
    public function getUserById($userId) {
        $query = "SELECT *, IFNULL(foto_perfil, '') as foto_perfil, IFNULL(foto, '') as foto FROM {$this->table} WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $userId);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    //Metodo para foto perfil
    private function validatePhoto($file) {
        // Check file size (5MB max)
        if ($file['size'] > 5242880) {
            throw new Exception("El archivo excede el tamaño máximo de 5MB");
        }

        // Check file type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($file['type'], $allowedTypes)) {
            throw new Exception("Solo se permiten archivos JPG, PNG o GIF");
        }
    }
    public function handlePhotoUpload($userId, $photoData) {
        try {
            $photoBinary = null;
            $photoPath = null;
            
            // Verificar si hay un archivo subido
            $hasFileUpload = isset($photoData['file']) && $photoData['file']['error'] === UPLOAD_ERR_OK;
            $hasUrl = isset($photoData['url']) && !empty(trim($photoData['url']));
            
            if ($hasFileUpload) {
                $this->validatePhoto($photoData['file']);
                
                // Leer el contenido del archivo como binario
                $photoBinary = file_get_contents($photoData['file']['tmp_name']);
                
                // También guardamos la ruta del archivo para compatibilidad
                $extension = pathinfo($photoData['file']['name'], PATHINFO_EXTENSION);
                $filename = 'user_' . $userId . '_' . time() . '.' . $extension;
                $photoPath = 'fotos/' . $filename;
            } 
            elseif ($hasUrl) {
                $url = trim($photoData['url']);
                if (!filter_var($url, FILTER_VALIDATE_URL)) {
                    throw new Exception("La URL proporcionada no es válida");
                }
                $photoPath = $url;
            }
            
            // Actualizar en la base de datos
            if ($photoBinary !== null || $photoPath !== null) {
                return $this->actualizarFoto($userId, $photoBinary, $photoPath);
            }
            
            return false;
            
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    private function actualizarFoto($userId, $fotoBinary, $fotoPath) {
        try {
            $query = "UPDATE {$this->table} SET foto = :foto, foto_perfil = :foto_perfil WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            
            $stmt->bindParam(':foto', $fotoBinary, $fotoBinary ? PDO::PARAM_LOB : PDO::PARAM_NULL);
            $stmt->bindParam(':foto_perfil', $fotoPath);
            $stmt->bindParam(':id', $userId);
            
            if ($stmt->execute()) {
                return true;
            }
            
            $this->lastError = implode(" ", $stmt->errorInfo());
            return false;
            
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }    
    public function actualizarFotoPerfil($userId, $fotoPath) {
        try {
            $query = "UPDATE {$this->table} SET foto_perfil = :foto_perfil WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':foto_perfil', $fotoPath);
            $stmt->bindParam(':id', $userId);
            
            if ($stmt->execute()) {
                return true;
            }
            
            $this->lastError = implode(" ", $stmt->errorInfo());
            return false;
            
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    public function blockUser($userId) {
        // Solo bloquear la cuenta, los tokens se manejan en la tabla tokens
        $query = "UPDATE usuarios SET contrasena = 'bloqueado' WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $userId);
        return $stmt->execute();
    }
    public function ensureSessionIdColumnExists() {
        try {
            $checkQuery = "SHOW COLUMNS FROM usuarios LIKE 'session_id'";
            $stmt = $this->conn->query($checkQuery);
            
            if ($stmt->rowCount() == 0) {
                $alterQuery = "ALTER TABLE usuarios ADD COLUMN session_id VARCHAR(255) DEFAULT NULL";
                $this->conn->exec($alterQuery);
            }
            return true;
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }
    // Metodos para manejo de tokens en cambiar contraseña
    public function getTokenUser($token) {
        $query = "SELECT u.id, u.nombre, u.apellido, u.usuario, u.rol_id 
                FROM usuarios u
                JOIN tokens t ON u.id = t.usuario_id
                WHERE t.token = :token 
                AND t.usado = FALSE
                AND t.fecha_token >= DATE_SUB(NOW(), INTERVAL 58 MINUTE)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function markTokenAsUsed($token, $userId) {
        $query = "UPDATE tokens SET 
                usado = TRUE 
                WHERE token = :token AND usuario_id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':id', $userId);
        return $stmt->execute();
    }

    public function createToken($userId, $token) {
        $query = "INSERT INTO tokens (usuario_id, token) VALUES (:user_id, :token)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':token', $token);
        return $stmt->execute();
    }

    public function getActiveTokens($userId = null) {
        $query = "SELECT u.id, u.nombre, u.apellido, u.usuario, t.token, t.fecha_token, t.usado, r.nombre as rol
                FROM usuarios u
                JOIN roles r ON u.rol_id = r.id
                INNER JOIN tokens t ON u.id = t.usuario_id
                WHERE t.fecha_token >= DATE_SUB(NOW(), INTERVAL 58 MINUTE)";
        
        if ($userId) {
            $query .= " AND u.id = :user_id";
        }
        
        $query .= " ORDER BY t.fecha_token DESC";
        
        $stmt = $this->conn->prepare($query);
        if ($userId) {
            $stmt->bindParam(':user_id', $userId);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getAllTokens() {
        $query = "SELECT u.id, u.nombre, u.apellido, u.usuario, r.nombre as rol, 
                t.token, t.fecha_token, t.usado
                FROM usuarios u
                JOIN roles r ON u.rol_id = r.id
                JOIN tokens t ON u.id = t.usuario_id
                ORDER BY t.fecha_token DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>