<?php
declare(strict_types=1);

namespace House\Service;

use Evenement\EventEmitter;
use GuzzleHttp\Client;
use House\Exception\BoilerReadException;
use House\Model\Boiler;
use House\Model\Gpio;
use House\Model\Session;
use Monolog\Logger;

/**
 * Class BoilerService
 * @package House\Service
 */
class BoilerService
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @var EventEmitter
     */
    protected $emitter;

    /**
     * @var ExecutorService
     */
    protected $executor;

    /**
     * @var GpioService
     */
    protected $gpioService;

    /**
     * @var SessionService
     */
    protected $sessionService;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var int
     */
    protected $restTime;

    /**
     * @var string
     */
    protected $temperatureFile;

    /**
     * @var string
     */
    protected $commandTemplate;

    /**
     * BoilerService constructor.
     * @param string $temperatureFile
     * @param EventEmitter $emitter
     * @param ExecutorService $executor
     * @param GpioService $gpioService
     * @param SessionService $sessionService
     * @param Logger $logger
     * @param int $restTime
     * @param string $centralAggregator
     * @param string $commandTemplate
     */
    public function __construct(
        string $temperatureFile,
        EventEmitter $emitter,
        ExecutorService $executor,
        GpioService $gpioService,
        SessionService $sessionService,
        Logger $logger,
        int $restTime = 5,
        string $centralAggregator = 'http://sb.imediat.eu/feed/log/sid/',
        string $commandTemplate = 'cat /sys/bus/w1/devices/%s/w1_slave |grep t='
    ) {
        $this->client = new Client();
        $this->emitter = $emitter;
        $this->executor = $executor;
        $this->gpioService = $gpioService;
        $this->sessionService = $sessionService;
        $this->logger = $logger;
        $this->restTime = $restTime;
        $this->centralAgreggator = rtrim($centralAggregator, '/');
        $this->commandTemplate = $commandTemplate;
        $this->setTemperatureFile($temperatureFile);
    }

    /**
     * @param string $commandTemplate
     * @return BoilerService
     */
    public function setCommandTemplate(string $commandTemplate) : BoilerService
    {
        $this->commandTemplate = $commandTemplate;

        return $this;
    }

    /**
     * @param string $temperatureFile
     * @return BoilerService
     */
    public function setTemperatureFile(string $temperatureFile) : BoilerService
    {
        if (!realpath($temperatureFile)) {
            $handle = @fopen($temperatureFile, 'w');
            if (!$handle) {
                $message = sprintf('Could not create temperature file at %s', $temperatureFile);
                $this->logger->critical($message);
                throw new \RuntimeException($message);
            }
            @fclose($handle);
        }

        if (!is_file($temperatureFile)) {
            $message = sprintf('Given temperature file path %s does not point to a file', $temperatureFile);
            $this->logger->critical($message);
            throw new \InvalidArgumentException($message);
        }

        if (!is_readable($temperatureFile)) {
            $message = sprintf('The temperature file %s is not readable', $temperatureFile);
            $this->logger->critical($message);
            throw new \RuntimeException($message);
        }

        $this->temperatureFile = $temperatureFile;

        return $this;
    }

    /**
     * @param Boiler $boiler
     * @return float
     * @throws \House\Exception\ProcessFailedException
     */
    public function getTemperature(Boiler $boiler) : float
    {
        $cmd = $this->prepareCommand($boiler);
        $temperature = array_map('trim', explode('t=', $this->executor->execute($cmd)));
        $temperature = $temperature[1] / 1000;
        if (!is_numeric($temperature)) {
            $message = sprintf('Boiler read failed for boiler %s', $boiler->getId());
            $this->logger->critical($message);
            throw new BoilerReadException($message);
        }

        return round($temperature, 2);
    }

    /**
     * @param float $value
     * @return bool
     */
    public function setDesiredTemperature(float $value) : bool
    {
        if (@file_put_contents($this->temperatureFile, $value, LOCK_EX)) {
            $message = sprintf('Desired temperature could not be set in %s', $this->temperatureFile);
            $this->logger->critical($message);
            throw new \RuntimeException($message);
        }
        return true;
    }

    /**
     * @return float
     */
    public function getDesiredTemperature() : float
    {
        $temperature = trim(file_get_contents($this->temperatureFile));
        if($temperature === '') {
            $message = sprintf('Temperature could not read from %s', $this->temperatureFile);
            $this->logger->critical($message);
            throw new \RuntimeException($message);
        }

        //ToDo: add an in range validator
        //ToDo: move this to the Helper class SmartBoxHelper

        return (float) $temperature;
    }

    /**
     * @param Gpio $gpio
     */
    public function turnOn(Gpio $gpio)
    {
        $relayStatus = $this->gpioService->read($gpio);
        if ($relayStatus == 0) {
            $this->gpioService->write($gpio, 1); //turn ON relay
            $this->logger->debug(sprintf('GPIO %s turned ON', $gpio->getPin()));

            $session = $this->sessionService->current();
            if (!$session->isOpened()) {
                $this->sessionService->start($session);
            }

            $this->emitter->emit('relay', [$gpio, $session, "on"]);
        }
    }

    /**
     * @param Gpio $gpio
     */
    public function turnOff(Gpio $gpio)
    {
        $relayStatus = $this->gpioService->read($gpio);
        if ($relayStatus == 1) {
            $this->gpioService->write($gpio, 0); //turn OFF relay
            $this->logger->debug(sprintf('GPIO %s turned OFF', $gpio->getPin()));

            $session = $this->sessionService->current();
            $session->close();

            $this->emitter->emit('relay', [$gpio, $session, "off"]);
        }
    }

    /**
     * @param Boiler $boiler
     * @param Gpio $boilerRelay
     */
    public function monitorBoiler(Boiler $boiler, Gpio $boilerRelay)
    {
        $session = $this->sessionService->current();
        $prevSessionId = (int) $session->getId() - 1;
        $prevSession = $prevSessionId ? $this->sessionService->getSessionById($prevSessionId) : null;

        if (!$this->enoughTimeHasPassed($prevSession)) {
            return; //bail early as is too early to do something
        }

        $desired = $this->getDesiredTemperature();
        $current = $this->getTemperature($boiler);
        $tempDiff = $desired - $current;

        $session->payload = $this->preparePayload($desired, $current);
        $sessionStartTime = $session->startTime();

        $this->logger->debug(sprintf('Desired = %s, Current = %s, Difference = %s', $desired, $current, $tempDiff));

        if ($tempDiff > 0.5) {
            echo 1;
            $this->turnOn($boilerRelay);

        } elseif (($tempDiff > 0.2) && ($tempDiff < 0.5)) {
            echo 2;

            if ($sessionStartTime) {
                echo "=2.1=";
                $timeDifference = $sessionStartTime->diff(new \DateTime());
                if ($timeDifference->i >= 10) { //10 minutes or more have passed
                    echo "=2.2=";
                    $this->turnOff($boilerRelay);
                }
            }

        } else {
            echo 3;
            $this->turnOff($boilerRelay);
        }
    }

    /**
     * @param Session|null $previousSession
     * @param \DateTime|null $now
     * @return bool
     */
    public function enoughTimeHasPassed(Session $previousSession = null, \DateTime $now = null)
    {
        if (!$previousSession) {
            // When we run the application for the
            // first time and there's no other sessions on disk
            return true;
        }

        if (!$now) {
            $now = new \DateTime();
        }

        $minutesPassed = $this->getTotalMinutes($now->diff($previousSession->closeTime()));

        return $minutesPassed >= $this->restTime;
    }

    /**
     * @param \DateInterval $int
     * @return int
     */
    protected function getTotalMinutes(\DateInterval $int) : int
    {
        return (int) ($int->d * 24 * 60) + ($int->h * 60) + $int->i;
    }

    /**
     * @param Boiler $boiler
     * @return string
     */
    protected function prepareCommand(Boiler $boiler) : string
    {
        return sprintf($this->commandTemplate, $boiler->getId());
    }

    /**
     */
    protected function preparePayload(float $desired, float $current) : string
    {
        return json_encode([$desired, $current]);
    }
	
}
