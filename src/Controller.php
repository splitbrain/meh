<?php

namespace splitbrain\meh;

use splitbrain\phpsqlite\SQLite;

abstract class Controller
{
    /**
     * @var App Application container
     */
    protected $app;
    
    /**
     * Constructor
     * 
     * @param App $app Application container
     */
    public function __construct(App $app)
    {
        $this->app = $app;
    }
    
    /**
     * Get the database connection
     * 
     * @return SQLite
     */
    protected function getDatabase()
    {
        return $this->app->getDatabase();
    }
}
