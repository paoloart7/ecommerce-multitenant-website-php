<?php

class Controller
{
    /**
     * Charge une vue avec le layout de base
     */
    protected function view(string $path, array $data = []): void
    {
        $viewFile = dirname(__DIR__) . '/Views/' . $path . '.php';
        if (!file_exists($viewFile)) {
            throw new Exception("Vue introuvable: $path");
        }

        $data['__view'] = $viewFile;
        extract($data);
        require dirname(__DIR__) . '/Views/layouts/base.php';
    }

    protected function redirect(string $path = ''): void
    {
        Router::redirect($path);
    }
}