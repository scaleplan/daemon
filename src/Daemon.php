<?php

namespace Scaleplan\Daemon;

use Psr\Log\LoggerInterface;
use Scaleplan\Console\AbstractCommand;
use Scaleplan\Console\CommandFabric;
use Scaleplan\Console\CommandInterface;
use Scaleplan\Daemon\Exceptions\DaemonOperationNotSupportedException;
use function Scaleplan\Helpers\get_env;

/**
 * Class Daemon
 *
 * @package Scaleplan\Daemon
 */
class Daemon
{
    public const OPERATION_START = 'start';
    public const OPERATION_RESTART = 'restart';
    public const OPERATION_STOP = 'stop';

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
                $timeout = \constant("$command::DAEMON_TIMEOUT")
                    ?? get_env('DAEMON_TIMEOUT')
                    ?? AbstractCommand::DAEMON_TIMEOUT;
                pcntl_signal_dispatch();
                usleep($timeout);
            } catch (\Throwable $e) {
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
     * @throws \Scaleplan\Console\Exceptions\CommandClassNotFoundException
     * @throws \Scaleplan\Console\Exceptions\CommandClassNotImplementsCommandInterfaceException
     * @throws \Scaleplan\Console\Exceptions\CommandClassNotInstantiableException
     * @throws \Scaleplan\Console\Exceptions\CommandException
     * @throws \Scaleplan\Console\Exceptions\InvalidCommandSignatureException
     */
    public function restart() : void
    {
        $oldWithMonit = $this->withMonit;
        $this->withMonit = false;
        $this->start();
        $this->stop();
        $this->start();
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
     * @throws \Scaleplan\Console\Exceptions\CommandException
     * @throws \Scaleplan\Console\Exceptions\InvalidCommandSignatureException
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
