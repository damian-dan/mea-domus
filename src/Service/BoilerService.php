<?php
declare(strict_types=1);

namespace House\Service;

use Evenement\EventEmitter;
use GuzzleHttp\Client;
use House\Exception\BoilerReadException;
use House\Model\Boiler;
use House\Model\Gpio;
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
        string $centralAggregator = 'http://sb.imediat.eu/feed/log/sid/',
        string $commandTemplate = 'cat /sys/bus/w1/devices/%s/w1_slave |grep t='
    ) {
        $this->client = new Client();
        $this->emitter = $emitter;
        $this->executor = $executor;
        $this->gpioService = $gpioService;
        $this->sessionService = $sessionService;
        $this->logger = $logger;
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

            $endpoint = sprintf('%s/%s/type/start', $this->centralAgreggator, $session->getId());
            $this->client->get($endpoint); //notify central aggregator of change
            $this->logger->debug(sprintf('Central aggregator notified of event'));

            $this->emitter->emit('relay.on', [$gpio, $session]);
        }
    }

    /**
     * @param Gpio $gpio
     */
    public function turnOff(Gpio $gpio)
    {
        $relayStatus = $this->gpioService->read($gpio);
        if ($relayStatus == 1) {
            $this->gpioService->write($gpio, 1); //turn OFF relay
            $this->logger->debug(sprintf('GPIO %s turned OFF', $gpio->getPin()));

            $session = $this->sessionService->current();
            $session->close();

            $endpoint = sprintf('%s/%s/type/stop', $this->centralAgreggator, $session->getId());
            $this->client->get($endpoint); //notify central aggregator of change
            $this->logger->debug(sprintf('Central aggregator notified of event'));

            $this->emitter->emit('relay.off', [$gpio, $session]);
        }
    }

    /**
     * @param Boiler $boiler
     * @param Gpio $boilerRelay
     */
    public function monitorBoiler(Boiler $boiler, Gpio $boilerRelay)
    {
        $desired = $this->getDesiredTemperature();
        $current = $this->getTemperature($boiler);
        $tempDiff = $desired - $current;

        $this->logger->debug(sprintf('Desired = %s, Current = %s, Difference = %s', $desired, $current, $tempDiff));

        if ($tempDiff > 0.5)
        {
            $this->turnOn($boilerRelay);

        } elseif (($tempDiff > 0.2) && ($tempDiff < 0.5)) {

            $session = $this->sessionService->current();
            $sessionStartTime = $session->startTime();
            if ($sessionStartTime) {
                $timeDifference = $sessionStartTime->diff(new \DateTime());
                if ($timeDifference->i >= 10) { //10 minutes or more have passed
                    $this->turnOff($boilerRelay);
                }
            }

        } else {
            $this->turnOff($boilerRelay);
        }
    }

    /**
     * @param Boiler $boiler
     * @return string
     */
    protected function prepareCommand(Boiler $boiler) : string
    {
        return sprintf($this->commandTemplate, $boiler->getId());
    }
}
