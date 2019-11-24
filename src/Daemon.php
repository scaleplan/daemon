<?php

namespace Scaleplan\Daemon;

use Psr\Log\LoggerInterface;
use Scaleplan\Console\CommandFabric;
use Scaleplan\Console\CommandInterface;
use Scaleplan\Daemon\Exceptions\CriticalException;
use Scaleplan\Daemon\Exceptions\DaemonOperationNotSupportedException;
use Scaleplan\Daemon\Exceptions\ErrorException;
use Scaleplan\Daemon\Hooks\CriticalErrorEvent;
use Scaleplan\Daemon\Hooks\ErrorEvent;
use function Scaleplan\Event\dispatch;

/**
 * Class Daemon
 *
 * @package Scaleplan\Daemon
 */
class Daemon implements DaemonInterface
{
    public const OPERATION_START   = 'start';
    public const OPERATION_RESTART = 'restart';
    public const OPERATION_STOP    = 'stop';

    public const STOP_SIGNALS = [
        SIGTERM,
        SIGINT,
        SIGQUIT,
        SIGKILL,
        SIGALRM,
        SIGABRT,
    ];

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
     * @var int
     */
    protected $timeout = 5;

    /**
     * @var int
     */
    protected $restartAfter = 86400;

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

        pcntl_async_signals(false);
        $this->stopSignalHandlerInit();
    }

    /**
     * @return int
     */
    public function getTimeout() : int
    {
        return $this->timeout;
    }

    /**
     * @param int $timeout
     */
    public function setTimeout(int $timeout) : void
    {
        $this->timeout = $timeout;
    }

    /**
     * @return int
     */
    public function getRestartAfter() : int
    {
        return $this->restartAfter;
    }

    /**
     * @param int $restartAfter
     */
    public function setRestartAfter(int $restartAfter) : void
    {
        $this->restartAfter = $restartAfter;
    }

    /**
     * Stop signal handler
     */
    protected function stopSignalHandler() : void
    {
        $this->logger->info("Daemon {$this->commandName} was stopped.");
        exit(0);
    }

    /**
     * Stop signal listening initialization
     */
    protected function stopSignalHandlerInit() : void
    {
        foreach (static::STOP_SIGNALS as $signal) {
            pcntl_signal($signal, [$this, 'stopSignalHandler']);
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
     * @throws \Scaleplan\Console\Exceptions\CommandException
     * @throws \Scaleplan\Console\Exceptions\InvalidCommandSignatureException
     * @throws \Scaleplan\Event\Exceptions\ClassNotImplementsEventInterfaceException
     */
    public function start(array $args = null) : void
    {
        $this->logger->info("Starting daemon command {$this->commandName}...");
        $command = CommandFabric::getCommand($this->commandName, array_values($args));
        $this->startArgs = $args ?? $this->startArgs;
        if ($this->withMonit) {
            $this->monit->saveFile();
        }

        if ($command::DAEMON_TIMEOUT) {
            $this->timeout = $command::DAEMON_TIMEOUT;
        }

        if ($command::DAEMON_RESTART_AFTER) {
            $this->restartAfter = $command::DAEMON_RESTART_AFTER;
        }

        $this->loop($command);
    }

    /**
     * @param CommandInterface $command
     *
     * @throws \Scaleplan\Event\Exceptions\ClassNotImplementsEventInterfaceException
     */
    protected function loop(CommandInterface $command) : void
    {
        $this->logger->info("Daemon command {$this->commandName} running...");
        $startTime = \time();
        while (true) {
            try {
                $command->run();
                pcntl_signal_dispatch();
                if ($startTime + $this->restartAfter >= \time()) {
                    $this->restart();
                }

                sleep($this->timeout);
            } catch (\Throwable $e) {
                if ($e instanceof CriticalException) {
                    $this->stop();
                    dispatch(CriticalErrorEvent::class);
                    $this->logger->critical($e->getMessage());
                    exit();
                }

                if ($e instanceof ErrorException) {
                    dispatch(ErrorEvent::class);
                    $this->restart();
                }

                $this->logger->error($e->getMessage());
            }
        }
    }

    /**
     * Stop daemon process
     */
    public function stop() : void
    {
        if ($this->withMonit) {
            $this->monit->removeFile();
        }

        $processName = escapeshellarg($this->commandName);
        shell_exec("pkill \"$processName\"");
        $this->logger->info("Sending stop signal to daemon {$this->commandName}...");
    }

    /**
     * Restart daemon process
     */
    public function restart() : void
    {
        //$oldWithMonit = $this->withMonit;
        $this->withMonit = false;
        $this->stop();
        shell_exec("{$_SERVER['SCRIPT_FILENAME']} start {$this->commandName} " . implode(' ', $this->getStartArgs()));
        //$this->start();
        //$this->withMonit = $oldWithMonit;
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
     * @throws \Scaleplan\Console\Exceptions\CommandException
     * @throws \Scaleplan\Console\Exceptions\InvalidCommandSignatureException
     * @throws \Scaleplan\Event\Exceptions\ClassNotImplementsEventInterfaceException
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
