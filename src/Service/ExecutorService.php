<?php
declare(strict_types=1);

namespace House\Service;

use House\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * Class ExecutorService
 * @package Home\Service
 */
class ExecutorService
{
    public function execute($cmd, int $timeout = 60, callable $callback = null) : string
    {
        $process = new Process($cmd, null, null, null, $timeout);
        $process->start(function() {
            pcntl_signal_dispatch();
        });

        while($process->isRunning()) {
            pcntl_signal_dispatch();
        }
        $process->run(function() use ($callback) {
            pcntl_signal_dispatch();
            if ($callback instanceof \Closure) {
                $callback();
            }
            pcntl_signal_dispatch();
        });
        if ($process->isTerminated()) {
            if ($process->isSuccessful()) {
                pcntl_signal_dispatch();
                return $process->getOutput();
            } else {
                throw new ProcessFailedException(sprintf('Command %s failed with output %s', $cmd, $process->getErrorOutput()));
            }
        }
    }
}
