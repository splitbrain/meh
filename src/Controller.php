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
     * Throw an exception with the given message and code
     * 
     * @param string $message Error message
     * @param int $code HTTP status code
     * @throws \Exception
     */
    protected function error($message, $code = 400)
    {
        throw new \Exception($message, $code);
    }
}
