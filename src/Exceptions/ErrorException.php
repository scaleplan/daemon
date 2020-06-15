<?php

namespace Scaleplan\Daemon\Exceptions;

/**
 * Class ErrorException
 *
 * @package Scaleplan\Daemon\Exceptions
 */
class ErrorException extends DaemonException
{
    public const MESSAGE = 'Ошибка работы демона. Перезапуск...';
    public const CODE = 500;
}
