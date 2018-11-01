<?php

namespace Scaleplan\Daemon\Exceptions;

/**
 * Class DaemonOperationNotSupportedException
 *
 * @package Scaleplan\Daemon\Exceptions
 */
class DaemonOperationNotSupportedException extends DaemonException
{
    public const MESSAGE = 'Daemon operation not supported.';
}