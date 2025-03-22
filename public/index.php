<?php

// Load Composer's autoloader
use splitbrain\meh\FileRouter;

require_once __DIR__ . '/../backend/vendor/autoload.php';

// If the request is for the API, redirect to the API handler
if (str_starts_with($_SERVER['REQUEST_URI'], '/api/')) {
    include __DIR__ . '/api/index.php';
    exit;
}

(new FileRouter())->route();
