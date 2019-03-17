<?php

namespace Scaleplan\Daemon\Exceptions;

/**
 * Class DaemonException
 *
 * @package Scaleplan\Daemon\Exceptions
 */
class DaemonException extends \Exception
{
    public const MESSAGE = 'Daemon error.';

    /**
     * DaemonException constructor.
     *
     * @param string|null $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(string $message = '', int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message ?: static::MESSAGE, $code, $previous);
    }
}
