<?php
require_once(__DIR__ . '/../config/paths.php');
require_once(__DIR__ . '/../controlador/AuthController.php');

$auth = new AuthController();
if (!$auth->validateSession()) {
    echo json_encode(['valid' => false]);
    exit();
}

echo json_encode(['valid' => true]);
?>