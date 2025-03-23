<?php

namespace splitbrain\meh;

class FileController
{
    const DIST_PATH = __DIR__ . '/../../frontend/dist/meh';
    const COMPONENTS_PATH = __DIR__ . '/../../frontend/src/components';
    const DOC_PATH = __DIR__ . '/../../doc';
    const README = __DIR__ . '/../../README.md';

    public function home()
    {
        header('Content-Type: text/html; charset=utf-8');
        echo '<html lang="en"><head><title>Meh</title>';
        echo '<link rel="stylesheet" href="https://unpkg.com/chota@latest">';
        echo '</head><body><div class="container">';
        echo '<h1>Welcome to Meh</h1>';
        echo '<p>It\'s a comment system.</p>';
        echo '<p>Check out the <a href="/doc/index">documentation</a>.</p>';
        echo '</div></body></html>';
    }

    /**
     * Serve a file from the dist directory
     *
     * @param string $file The file to serve
     * @return void
     * @throws HttpException If the file is not found or invalid
     */
    public function dist($file): void
    {
        // everything else should be already handled by the router match type
        if (str_contains($file, '..') || str_starts_with($file, '.')) {
            throw new HttpException('Invalid file path', 400);
        }

        if (!file_exists(self::DIST_PATH . '/' . $file)) {
            throw new HttpException('File not found', 404);
        }

        $extension = pathinfo($file, PATHINFO_EXTENSION);

        match ($extension) {
            'css' => header('Content-Type: text/css'),
            'js' => header('Content-Type: application/javascript'),
            'json' => header('Content-Type: application/json'),
            default => throw new HttpException('Invalid file type', 400),
        };

        readfile(self::DIST_PATH . '/' . $file);
    }

    public function i18n($file): void
    {
        $this->dist('i18n/' . $file);
    }

    public function doc($file): void
    {
        // everything else should be already handled by the router match type
        if (str_contains((string)$file, '..') || str_starts_with((string)$file, '.')) {
            throw new HttpException('Invalid file path', 400);
        }

        if ($file === 'index') {
            $mdfile = self::README;
        } elseif (file_exists(self::DOC_PATH . '/' . $file . '.md')) {
            $mdfile = self::DOC_PATH . '/' . $file . '.md';
        } else {
            $mdfile = self::COMPONENTS_PATH . '/' . $file . '/readme.md';
        }

        if (!file_exists($mdfile)) {
            throw new HttpException('File not found', 404);
        }

        $html = \Parsedown::instance()->text(file_get_contents($mdfile));

        header('Content-Type: text/html; charset=utf-8');
        echo '<html lang="en"><head><title>' . $file . '</title>';
        echo '<link rel="stylesheet" href="https://unpkg.com/chota@latest">';
        echo '</head><body><div class="container">';
        echo $html;
        echo '</div></body></html>';
    }
}
