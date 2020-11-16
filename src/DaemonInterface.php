<?php

namespace Scaleplan\Daemon;

/**
 * Class Daemon
 *
 * @package Scaleplan\Daemon
 */
interface DaemonInterface
{
    /**
     * @return int
     */
    public function getTimeout() : int;

    /**
     * @param int $timeout
     */
    public function setTimeout(int $timeout) : void;

    /**
     * @return int
     */
    public function getRestartAfter() : int;

    /**
     * @param int $restartAfter
     */
    public function setRestartAfter(int $restartAfter) : void;

    /**
     * @return bool
     */
    public function isWithMonit() : bool;

    /**
     * @param bool $withMonit
     *
     * @return bool
     */
    public function setWithMonit(bool $withMonit) : bool;

    /**
     * @param array|null $args
     */
    public function start(array $args = null) : void;

    /**
     * Stop daemon process
     */
    public function stop() : void;

    /**
     * Restart daemon process
     */
    public function restart() : void;

    /**
     * @return array
     */
    public function getStartArgs() : array;

    /**
     * @param array $startArgs
     */
    public function setStartArgs(array $startArgs) : void;

    /**
     * @param string $operation
     */
    public function exec(string $operation) : void;
}
