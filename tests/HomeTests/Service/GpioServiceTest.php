<?php

namespace HomeTests\Service;

use House\Exception\GpioDirectionException;
use House\Model\ReadOnlyGpio;
use House\Model\WriteOnlyGpio;
use House\Service\GpioService;
use House\Model\Gpio;

/**
 * Class GpioServiceTest
 * @package HomeTests\Service
 */
class GpioServiceTest extends \PHPUnit_Framework_TestCase
{
    const PIN = 1;

    /**
     * @var GpioService
     */
    protected $service;

    /**
     * @var Gpio
     */
    protected $gpio;

    /**
     * @var Gpio
     */
    protected $readOnlyGpio;

    /**
     * @var Gpio
     */
    protected $writeOnlyGpio;

    public function setUp()
    {
        $this->service = new GpioService('php ' . __DIR__.'/../bin/gpioMock');
        $this->gpio = new Gpio(self::PIN);
        $this->readOnlyGpio = new ReadOnlyGpio(self::PIN);
        $this->writeOnlyGpio = new WriteOnlyGpio(self::PIN);
    }

    public function tearDown()
    {
        unset($this->service);
        unset($this->gpio);
        unset($this->readOnlyGpio);
        unset($this->writeOnlyGpio);
    }

    public function testIfPinIsReturnedCorrectly()
    {
        $this->assertTrue($this->gpio->getPin() === self::PIN);
    }

    public function testIfReadIsOk()
    {
        $this->assertTrue($this->service->read($this->gpio) === 'OK');
    }

    public function testIfWriteIsOk()
    {
        $this->assertTrue($this->service->write($this->gpio, 'DATA') === 'DATA');
    }

    public function testIfModeIsOk()
    {
        $this->assertTrue($this->service->mode($this->gpio, 'OK') === 'OK');
    }

    public function testWriteOnlyGpioShouldAllowWrite()
    {
        $this->assertTrue($this->service->write($this->writeOnlyGpio, 'DATA') === 'DATA');
    }

    public function testWriteOnlyGpioShouldFailRead()
    {
        $this->expectException(GpioDirectionException::class);
        $this->service->read($this->writeOnlyGpio);
    }

    public function testReadOnlyGpioShouldAllowRead()
    {
        $this->assertTrue($this->service->read($this->readOnlyGpio) === 'OK');
    }

    public function testReadOnlyGpioShouldFailWrite()
    {
        $this->expectException(GpioDirectionException::class);
        $this->service->write($this->readOnlyGpio);
    }
}
