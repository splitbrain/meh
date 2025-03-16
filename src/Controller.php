<?php

namespace splitbrain\meh;

use splitbrain\phpsqlite\SQLite;

abstract class Controller
{
    /**
     * Get the database connection
     * 
     * @return SQLite
     */
    protected function getDatabase()
    {
        $file = __DIR__ . '/../data/meh.sqlite';
        $schema = __DIR__ . '/../db/';
        return new SQLite($file, $schema);
    }
    
    /**
     * Send a JSON response
     * 
     * @param mixed $data Data to encode as JSON
     * @param int $statusCode HTTP status code
     * @return string JSON encoded response
     */
    protected function jsonResponse($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        return json_encode($data);
    }
}
