<?php

namespace splitbrain\meh;

use splitbrain\phpsqlite\SQLite;

abstract class Controller
{
    /**
     * Constructor
     *
     * @param App $app Application container
     */
    public function __construct(protected App $app)
    {
    }
}
