<?php

namespace Scaleplan\Daemon\Exceptions;

/**
 * Class CriticalException
 *
 * @package Scaleplan\Daemon\Exceptions
 */
class CriticalException extends DaemonException
{
    public const MESSAGE = 'daemon.critical-error';
    public const CODE = 500;
}
