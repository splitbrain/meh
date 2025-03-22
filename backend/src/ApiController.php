<?php

namespace splitbrain\meh;

/**
 * Base class for API controllers
 *
 * Called from the ApiRouter, methods are given a flat array of parameters and are expected to return
 * JSON-serializable data.
 */
abstract class ApiController
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
