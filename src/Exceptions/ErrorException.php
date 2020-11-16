<?php

namespace Scaleplan\Daemon\Exceptions;

/**
 * Class ErrorException
 *
 * @package Scaleplan\Daemon\Exceptions
 */
class ErrorException extends DaemonException
{
    public const MESSAGE = 'daemon.error-with-reload';
    public const CODE = 500;
}
