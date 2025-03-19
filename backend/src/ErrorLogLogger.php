<?php

namespace splitbrain\meh;

use Psr\Log\AbstractLogger;
use Psr\Log\InvalidArgumentException;

/**
 * A logger that writes to the PHP error log
 */
class ErrorLogLogger extends AbstractLogger
{

    protected $loglevels = [
        'emergency' => 1,
        'alert' => 2,
        'critical' => 3,
        'error' => 4,
        'warning' => 5,
        'notice' => 6,
        'info' => 7,
        'debug' => 8,
    ];

    protected int $loglevel = 5;

    public function __construct($loglevel = 'warning')
    {
        if (!isset($this->loglevels[$loglevel])) {
            throw new InvalidArgumentException('Invalid log level');
        }

        $this->loglevel = $this->loglevels[$loglevel];
    }

    public function log($level, \Stringable|string $message, array $context = []): void
    {
        if (!isset($this->loglevels[$level])) {
            throw new InvalidArgumentException('Invalid log level');
        }
        $numericLevel = $this->loglevels[$level];

        if ($numericLevel > $this->loglevel) {
            return;
        }

        $message = "[$level] " . $this->interpolate((string)$message, $context);
        if (isset($context['exception']) && $context['exception'] instanceof \Exception) {
            $message .= "\n" . $context['exception']->getTraceAsString();
        }

        if (php_sapi_name() == 'cli-server') {
            $message = date('c') . ' ' . $message . "\n";
            file_put_contents("php://stderr", $message);
        } else {
            error_log("[$level] $message");
        }
    }

    /**
     * Interpolates context values into the message placeholders.
     */
    protected function interpolate($message, array $context = array()): string
    {
        // build a replacement array with braces around the context keys
        $replace = array();
        foreach ($context as $key => $val) {
            // check that the value can be cast to string
            if (!is_array($val) && (!is_object($val) || method_exists($val, '__toString'))) {
                $replace['{' . $key . '}'] = $val;
            }
        }

        // interpolate replacement values into the message and return
        return strtr($message, $replace);
    }
}
