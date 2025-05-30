<?php
// Configuración de rutas base
define('ROOT_PATH', realpath(dirname(__FILE__) . '/..'));
define('BASE_URL', 'http://localhost/DEM/Escuela/');

// Rutas a directorios importantes
define('CONTROLLER_PATH', ROOT_PATH . '/controlador/');
define('MODEL_PATH', ROOT_PATH . '/modelo/');
define('VIEW_PATH', ROOT_PATH . '/pagina/');

// Configuración de la aplicación
define('DEBUG_MODE', true);
?>