<?php

namespace Scaleplan\Daemon;

use Psr\Log\LoggerInterface;
use Scaleplan\Console\CommandFabric;
use Scaleplan\Daemon\Exceptions\DaemonOperationNotSupportedException;

/**
 * Class Daemon
 *
 * @package Scaleplan\Daemon
 */
class Daemon
{
    const OPERATION_START = 'start';
    const OPERATION_RESTART = 'restart';
    const OPERATION_STOP = 'stop';

    /**
     * @var string
     */
    protected $commandName;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var MonitInterface|null
     */
    protected $monit;

    /**
     * @var array
     */
    protected $startArgs = [];

    /**
     * @var bool
     */
    protected $withMonit = false;

    /**
     * Daemon constructor.
     *
     * @param string $commandName
     * @param LoggerInterface $logger
     * @param MonitInterface $monit
     */
    public function __construct(string $commandName, LoggerInterface $logger, MonitInterface $monit = null)
    {
        $this->commandName = $commandName;
        $this->logger = $logger;
        $this->monit = $monit;
        if ($monit) {
            $this->withMonit = true;
        }
    }

    /**
     * @return bool
     */
    public function isWithMonit() : bool
    {
        return $this->withMonit;
    }

    /**
     * @param bool $withMonit
     *
     * @return bool
     */
    public function setWithMonit(bool $withMonit) : bool
    {
        if (!$this->monit) {
            return false;
        }

        return $this->withMonit = $withMonit;
    }

    /**
     * @param array|null $args
     *
     * @throws \Scaleplan\Console\Exceptions\CommandClassNotFoundException
     * @throws \Scaleplan\Console\Exceptions\CommandClassNotImplementsCommandInterfaceException
     * @throws \Scaleplan\Console\Exceptions\CommandClassNotInstantiableException
     * @throws \Scaleplan\Console\Exceptions\CommandNameIsEmptyException
     */
    public function start(array $args = null) : void
    {
        $command = CommandFabric::getCommand($this->commandName, array_values($args));
        $this->startArgs = $args ?? $this->startArgs;
        if ($this->withMonit) {
            $this->monit->saveFile();
        }

        $this->logger->info("Daemon {$this->commandName} running...");
        while (true) {
            try {
                $command->run();
            } catch (\Throwable $e) {
                $this->logger->error($e->getMessage());
            }
        }
    }

    public function stop() : void
    {
        if ($this->withMonit) {
            $this->monit->removeFile();
        }

        $processName = escapeshellarg($this->commandName);
        shell_exec("pkill -9 \"$processName\"");
        $this->logger->info("Daemon {$this->commandName} was stopped");
    }

    /**
     * @throws \Scaleplan\Console\Exceptions\CommandClassNotFoundException
     * @throws \Scaleplan\Console\Exceptions\CommandClassNotImplementsCommandInterfaceException
     * @throws \Scaleplan\Console\Exceptions\CommandClassNotInstantiableException
     * @throws \Scaleplan\Console\Exceptions\CommandNameIsEmptyException
     */
    public function restart() : void
    {
        $oldWithMonit = $this->withMonit;
        $this->withMonit = false;
        $this->start();
        $this->stop();
        $this->withMonit = $oldWithMonit;
    }

    /**
     * @return array
     */
    public function getStartArgs() : array
    {
        return $this->startArgs;
    }

    /**
     * @param array $startArgs
     */
    public function setStartArgs(array $startArgs) : void
    {
        $this->startArgs = $startArgs;
    }

    /**
     * @param string $operation
     *
     * @throws DaemonOperationNotSupportedException
     * @throws \Scaleplan\Console\Exceptions\CommandClassNotFoundException
     * @throws \Scaleplan\Console\Exceptions\CommandClassNotImplementsCommandInterfaceException
     * @throws \Scaleplan\Console\Exceptions\CommandClassNotInstantiableException
     * @throws \Scaleplan\Console\Exceptions\CommandNameIsEmptyException
     */
    public function exec(string $operation) : void
    {
        switch ($operation) {
            case static::OPERATION_START:
                $this->start();
                break;

            case static::OPERATION_STOP:
                $this->stop();
                break;

            case static::OPERATION_RESTART:
                $this->restart();
                break;

            default:
                throw new DaemonOperationNotSupportedException();
        }
    }
}