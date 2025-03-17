<?php

namespace splitbrain\meh;

use splitbrain\phpsqlite\SQLite;

abstract class Controller
{
    /**
     * @var App Application container
     */
    protected App $app;

    /**
     * Constructor
     *
     * @param App $app Application container
     */
    public function __construct(App $app)
    {
        $this->app = $app;
    }
}
