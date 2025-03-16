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
}
