<?php

namespace splitbrain\meh;

class FileController
{
    const DIST_PATH = __DIR__ . '/../../frontend/dist/meh';
    const COMPONENTS_PATH = __DIR__ . '/../../frontend/src/components';
    const README = __DIR__ . '/../../README.md';

    public function home()
    {
        header('Content-Type: text/html; charset=utf-8');
        echo '<html lang="en"><head><title>Meh</title></head><body>';
        echo '<h1>Welcome to Meh</h1>';
        echo '<p>It\'s a comment system.</p>';
        echo '<p>Check out the <a href="/doc/index">documentation</a>.</p>';
        echo '</body></html>';
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
        // everything else should be already handle by the router match type
        if(str_contains($file, '..') || str_starts_with($file, '.')) {
            throw new HttpException('Invalid file path', 400);
        }

        if(!file_exists(self::DIST_PATH . '/' . $file)) {
            throw new HttpException('File not found', 404);
        }

        $extension = pathinfo($file, PATHINFO_EXTENSION);

        switch ($extension) {
            case 'css':
                header('Content-Type: text/css');
                break;
            case 'js':
                header('Content-Type: application/javascript');
                break;
            case 'json':
                header('Content-Type: application/json');
                break;
            default:
                throw new HttpException('Invalid file type', 400);
        }

        readfile(self::DIST_PATH . '/' . $file);
    }

    public function i18n($file): void
    {
        $this->dist('i18n/' . $file);
    }

    public function doc($file): void
    {
        // everything else should be already handle by the router match type
        if(str_contains($file, '..') || str_starts_with($file, '.')) {
            throw new HttpException('Invalid file path', 400);
        }

        if($file === 'index') {
            $mdfile = self::README;
        } else {
            $mdfile = self::COMPONENTS_PATH . '/' . $file . '/readme.md';
        }

        if(!file_exists($mdfile)) {
            throw new HttpException('File not found', 404);
        }

        $html = \Parsedown::instance()->text(file_get_contents($mdfile));

        header('Content-Type: text/html; charset=utf-8');
        echo '<html lang="en"><head><title>' . $file . '</title></head><body>';
        echo $html;
        echo '</body></html>';
    }
}
