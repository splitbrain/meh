<?php

namespace splitbrain\meh;

use splitbrain\phpsqlite\SQLite;

class App
{
    /**
     * @var SQLite Database connection
     */
    protected $db;
    
    /**
     * Get the database connection
     * 
     * @return SQLite
     */
    public function getDatabase()
    {
        if (!$this->db) {
            $file = __DIR__ . '/../data/meh.sqlite';
            $schema = __DIR__ . '/../db/';
            $this->db = new SQLite($file, $schema);
        }
        return $this->db;
    }
    
    /**
     * Set a custom database connection (useful for testing)
     * 
     * @param SQLite $db
     * @return void
     */
    public function setDatabase(SQLite $db)
    {
        $this->db = $db;
    }
}
