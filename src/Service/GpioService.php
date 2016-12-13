<?php
declare(strict_types=1);

namespace House\Service;

use Evenement\EventEmitter;
use GuzzleHttp\Client;
use House\Exception\GpioDirectionException;
use House\House;
use House\Model\Gpio;
use House\Model\ReadOnlyGpio;
use House\Model\WriteOnlyGpio;

/**
 * Class GpioService
 * @package Home\Service
 */
class GpioService
{
    const READ  = 'read';
    const WRITE = 'write';
    const MODE  = 'mode';

    /**
     * @var ExecutorService
     */
    protected $executor;

    /**
     * @var EventEmitter
     */
    protected $emitter;

    /**
     * @var string
     */
    protected $binaryPath;

    /**
     * GpioService constructor.
     * @param string $binaryPath
     */
    public function __construct(EventEmitter $emiter, string $binaryPath)
    {
        $this->binaryPath = $binaryPath;
        $this->emitter = $emiter;
    }

    /**
     * @param Gpio $gpio
     * @return string
     */
    public function read(Gpio $gpio) : string
    {
        if ($gpio instanceof WriteOnlyGpio) {
            throw new GpioDirectionException('GPIO allows only WRITE');
        }

        $cmd = $this->buildCommand($gpio->getPin(), self::READ);

        return $this->getExecutor()->execute($cmd);
    }

    /**
     * @param Gpio $gpio
     * @param string $data
     * @return string
     */
    public function write(Gpio $gpio, $data = '') : string
    {
        if ($gpio instanceof ReadOnlyGpio) {
            throw new GpioDirectionException('GPIO allows only READ');
        }

        $cmd = $this->buildCommand($gpio->getPin(), self::WRITE, $data);

        return $this->getExecutor()->execute($cmd);
    }

    /**
     * @param Gpio $gpio
     * @param string $mode
     * @return string
     * @throws \House\Exception\ProcessFailedException
     */
    public function mode(Gpio $gpio, $mode) : string
    {
        $cmd = $this->buildCommand($gpio->getPin(), self::MODE, $mode);

        return $this->getExecutor()->execute($cmd);
    }

    /**
     * @param House $house
     * @param $gpio
     * @param $session
     * @param $state
     */
    public function relayOnOff(House $house, $gpio, $session, $state)
    {
        $client = new Client();
        if($state == "on"){
            echo "State ON \n";
            $endpoint = sprintf('%s/%s/type/start', $house->config()->get('central_aggregator'), $session->getId());
            $client->get($endpoint); //notify central aggregator of change
            $house->logger()->debug(sprintf('Central aggregator notified of event'));
        }
        else if($state == "off"){
            echo "State OFF \n";
            $endpoint = sprintf('%s/%s/type/stop', $house->config()->get('central_aggregator'), $session->getId());
            $client->get($endpoint); //notify central aggregator of change
            $house->logger()->debug(sprintf('Central aggregator notified of event'));
        }

    }

    /**
     * @param int $pin
     * @param string $action
     * @param string $data
     * @return string
     */
    protected function buildCommand(int $pin, string $action, $data = '') : string
    {
        return sprintf(
            '%s %s %s %s',
            $this->binaryPath,
            $action,
            $pin,
            $data
        );
    }

    /**
     * @return ExecutorService
     */
    protected function getExecutor() : ExecutorService
    {
        if (!$this->executor) {
            $this->executor = new ExecutorService();
        }
        return $this->executor;
    }
}
