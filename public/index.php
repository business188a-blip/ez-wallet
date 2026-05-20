<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../routes/web.php';
$route = $_GET['route'] ?? 'home';
if (!isset($routes[$route])) {
    http_response_code(404);
    $title = 'Page Not Found';
    $viewFile = __DIR__ . '/../views/errors/404.php';
    $layoutFile = __DIR__ . '/../views/layouts/app.php';
    require $layoutFile;
    exit;
}
[$controllerClass, $method] = $routes[$route];
$controller = new $controllerClass();
$controller->$method();
