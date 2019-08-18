<?php

namespace Scaleplan\Daemon\Exceptions;

/**
 * Class ErrorException
 *
 * @package Scaleplan\Daemon\Exceptions
 */
class ErrorException extends DaemonException
{
    public const MESSAGE = 'Daemon error. Restart.';
    public const CODE = 500;
}
