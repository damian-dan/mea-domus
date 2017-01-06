<?php
declare(strict_types=1);
declare(ticks=1);

namespace House;

use House\Model\Session;
use House\Service\BoilerService;
use House\Service\ExecutorService;
use House\Service\GpioService;
use House\Service\SessionService;
use House\Util\ErrorHandler;
use House\Model\Gpio;
use Monolog\Handler\StreamHandler;
use House\Console\Application;
use Evenement\EventEmitter;
use Monolog\Logger;

/**
 * Class House
 * @package House
 */
class House
{
    /**
     * @var Application
     */
    protected $application;

    /**
     * @var string
     */
    protected $configurationFile;

    /**
     * @var EventEmitter
     */
    protected $dispatcher;

    /**
     * @var ExecutorService
     */
    protected $executor;

    /**
     * @var SessionService
     */
    protected $session;

    /**
     * @var BoilerService
     */
    protected $boilerService;

    /**
     * @var GpioService
     */
    protected $gpioService;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var array
     */
    protected $signals = [
        'WNOHANG' => 1,
        'WUNTRACED' => 2,
        'SIG_IGN' => 1,
        'SIG_DFL' => 0,
        'SIG_ERR' => -1,
        'SIGHUP' => 1,
        'SIGINT' => 2,
        'SIGQUIT' => 3,
        'SIGILL' => 4,
        'SIGTRAP' => 5,
        'SIGABRT' => 6,
        'SIGIOT' => 6,
        'SIGBUS' => 7,
        'SIGFPE' => 8,
        //'SIGKILL' => 9, //NOT ALLOWED
        'SIGUSR1' => 10,
        'SIGSEGV' => 11,
        'SIGUSR2' => 12,
        'SIGPIPE' => 13,
        'SIGALRM' => 14,
        'SIGTERM' => 15,
        'SIGSTKFLT' => 16,
        //'SIGCLD' => 17, //NOT ALLOWED
        //'SIGCHLD' => 17, //NOT ALLOWED
        'SIGCONT' => 18,
        'SIGSTOP' => 19,
        // 'SIGTSTP' => 20, //Sent every time the process is halted by the kernel or interpretor (like when using sleep)
        'SIGTTIN' => 21,
        'SIGTTOU' => 22,
        'SIGURG' => 23,
        'SIGXCPU' => 24,
        'SIGXFSZ' => 25,
        'SIGVTALRM' => 26,
        'SIGPROF' => 27,
        'SIGWINCH' => 28,
        'SIGPOLL' => 29,
        'SIGIO' => 29,
        'SIGPWR' => 30,
        'SIGSYS' => 31,
        'SIGBABY' => 31,
        'PRIO_PGRP' => 1,
        'PRIO_USER' => 2,
        'PRIO_PROCESS' => 0,
    ];

    /**
     * House constructor.
     * @param Application $application
     * @param string $configurationFile
     */
    public function __construct(Application $application, string $configurationFile)
    {
        $this->application = $application;
        $this->configurationFile = $configurationFile;

        ErrorHandler::register($this->logger(), $this);

        $this->application->setHouse($this);
        $this->attachSignalListeners();
        $this->attachShutdownListeners();
        $this->attachListeners();
    }

    /**
     * @return EventEmitter
     */
    public function emitter() : EventEmitter
    {
        if (!$this->dispatcher) {
            $this->dispatcher = new EventEmitter();
        }
        return $this->dispatcher;
    }

    /**
     * @return Logger
     */
    public function logger() : Logger
    {
        if (!$this->logger) {
            $this->logger = new Logger('home');
            $this->logger->pushHandler(new StreamHandler($this->config()->get('logFile'), Logger::DEBUG));
        }
        return $this->logger;
    }

    /**
     * @return Config
     */
    public function config() : Config
    {
        if (!$this->config) {
            $this->config = new Config($this->configurationFile);
			      $this->config->set("project_root", realpath(dirname(__DIR__)) . DIRECTORY_SEPARATOR);
        }
        return $this->config;
    }

    /**
     * @return ExecutorService
     */
    public function executor() : ExecutorService
    {
        if (!$this->executor) {
            $this->executor = new ExecutorService();
        }
        return $this->executor;
    }

    /**
     * @return SessionService
     */
    public function session() : SessionService
    {
        if (!$this->session) {
            $this->session = new SessionService(
                $this->emitter(),
                $this->config->get('project_root') . $this->config()->get('session_dir')
            );
        }
        return $this->session;
    }

    /**
     * @return BoilerService
     */
    public function boilerService() : BoilerService
    {
        if (!$this->boilerService) {
            $this->boilerService = new BoilerService(
                $this->config()->get('project_root') . $this->config()->get('temperature_file'),
                $this->emitter(),
                $this->executor(),
                $this->gpioService(),
                $this->session(),
                $this->logger(),
                (int) $this->config()->get('boiler_rest_time'),
                (string) $this->config()->get('central_aggregator'),
                (string) $this->config()->get('boiler_temp_read_command')
            );
        }
        return $this->boilerService;
    }

    /**
     * @return GpioService
     */
    public function gpioService() : GpioService
    {
        if (!$this->gpioService) {
            $this->gpioService = new GpioService(
                $this->emitter(),
                (string) $this->config()->get('gpio_binary')
            );
        }
        return $this->gpioService;
    }

    /**
     * @return void
     */
    public function shutdown()
    {
        $this->logger()->debug('Shutting down...bye');
        $this->gpioService->write(new Gpio((int) $this->config()->get('relay_pin')), 0);

        /**
         * Close current session if it's opened
         */
        $currentSession = $this->session()->current();
        if ($currentSession->isOpened()) {
            $this->session()->close($currentSession);
        }
        die;
    }

    /**
     * @return void
     */
    protected function attachSignalListeners()
    {
        if (php_sapi_name() === 'cli') { //stupid way of determining if we're in CLI or not
            if (!function_exists('pcntl_signal')) {
                throw new \RuntimeException('The PCNTL extension is not available (either not installed or disabled)');
            }

            foreach ($this->signals as $name => $id) {

                try {
                    pcntl_signal($id, function() use ($name) {
                        $this->logger()->debug(sprintf('Signal %s received. Notifying listeners', $name));

                        $this->emitter()->emit('signal', ['signal' => $name]);
                        $this->emitter()->emit(strtolower($name));
                    });

                    $this->logger()->debug(sprintf('Signal listener for %s attached', $name));
                } catch (\ErrorException $e) {
                    $this->logger()->addWarning(sprintf('Signal %s not supported by OS', $name));
                }
            }

        } else {
            $this->logger()->info('PCNTL extension not available when running in web. Skipping signal handlers');
        }
    }

    /**
     * @return void
     */
    protected function attachShutdownListeners()
    {
        foreach (['SIGTERM', 'PRIO_USER', 'SIGCONT'] as $signal) {
            $this->emitter()->on(strtolower($signal), function() {
                $this->shutdown();
            });
        }
    }

    protected function attachListeners()
    {
        $this->emitter()->on('relay', function($gpio, $session, $state)  {
            $this->gpioService()->relayOnOff($this, $gpio, $session, $state);
        });
    }
}
