<?php
// Router para el servidor integrado de PHP al servir la aplicación durante las capturas del manual.
$path = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$file = __DIR__ . '/../public' . $path;
if ($path !== '/' && is_file($file)) {
    return false;
}

// Symfony Runtime resuelve el punto de entrada con SCRIPT_FILENAME; el servidor integrado de PHP lo
// pone a este router en lugar de a public/index.php, lo que provoca errores fatales intermitentes.
$_SERVER['SCRIPT_NAME']     = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/../public/index.php';
require __DIR__ . '/../public/index.php';
