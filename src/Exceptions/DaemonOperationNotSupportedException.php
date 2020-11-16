<?php

namespace Scaleplan\Daemon\Exceptions;

/**
 * Class DaemonOperationNotSupportedException
 *
 * @package Scaleplan\Daemon\Exceptions
 */
class DaemonOperationNotSupportedException extends DaemonException
{
    public const MESSAGE = 'daemon.not-supporting-operation';
    public const CODE = 406;
}
