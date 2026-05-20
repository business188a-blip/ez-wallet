<?php
abstract class BaseController
{
    protected function view(string $view, array $data = [], string $layout = 'app'): void
    {
        extract($data);
        $viewFile = __DIR__ . '/../views/' . $view . '.php';
        $layoutFile = __DIR__ . '/../views/layouts/' . $layout . '.php';
        require $layoutFile;
    }
}
