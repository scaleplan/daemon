<?php

namespace Scaleplan\Daemon;

/**
 * Interface MonitInterface
 *
 * @package Scaleplan\Daemon
 */
interface MonitInterface
{
    public function saveFile() : void;

    public function removeFile() : void;
}