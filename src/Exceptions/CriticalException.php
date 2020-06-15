<?php

namespace Scaleplan\Daemon\Exceptions;

/**
 * Class CriticalException
 *
 * @package Scaleplan\Daemon\Exceptions
 */
class CriticalException extends DaemonException
{
    public const MESSAGE = 'Критическая ошибка работы демона.';
    public const CODE = 500;
}
