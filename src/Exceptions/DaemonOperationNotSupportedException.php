<?php

namespace Scaleplan\Daemon\Exceptions;

/**
 * Class DaemonOperationNotSupportedException
 *
 * @package Scaleplan\Daemon\Exceptions
 */
class DaemonOperationNotSupportedException extends DaemonException
{
    public const MESSAGE = 'Неподдерживаемая операция для демона.';
    public const CODE = 406;
}
