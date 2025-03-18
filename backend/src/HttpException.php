<?php

namespace splitbrain\meh;

class HttpException extends \Exception
{
    /**
     * Constructor
     *
     * @param string $message Exception message
     * @param int $code HTTP status code
     */
    public function __construct($message, $code = 500, $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
