<?php
require_once(__DIR__ . '/../config/paths.php');
require_once(__DIR__ . '/../config/no_cache.php');

session_start();

if (isset($_POST['clear_wait'])) {
    $_SESSION['in_wait'] = false;
    $_SESSION['wait_start'] = 0;
    $_SESSION['wait_user'] = '';
    echo json_encode(['success' => true]);
    exit();
}

echo json_encode(['success' => false]);
?>