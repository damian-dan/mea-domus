<?php
/**
 * Created by PhpStorm.
 * User: dan
 * Date: 27/03/16
 * Time: 12:54
 */

namespace House;


class HouseHelper
{
    const PATH = '/usr/local/bin/';
    const COMMAND = self::PATH . 'gpio';
    const PWM = 'pwm';
    const RELAY_PIN = 0; // this should be configurable
    const MODE = 'mode';
    const READ = 'read';
    const WRITE = 'write';
    const OUT = 'out';
    const IN = 'in';
    const ON = 1;
    const OFF = 0;

    private $sid;

    public function __construct()
    {
        $this->setup();
    }

    public function mode($pin, $state)
    {
        return $this->execute(self::MODE, $pin, $state);
    }
    public function write($pin, $state)
    {
        return $this->execute(self::WRITE, $pin, $state);
    }
    public function read($pin)
    {
        return $this->execute(self::READ, $pin);
    }
    protected function execute($command, $pin, $state="")
    {
        exec(static::COMMAND . " " . $command . " " . $pin . " " . $state, $output);
        return $output;
    }
    public function readRelayState()
    {
        $this->read(self::RELAY_PIN);
    }
    public function setup()
    {
        $this->sid = new SidHelper(__DIR__ . "/../data/", basename(__FILE__, '.php').".pid");
        $this->sid->createNewSid("info");
        //$sid->updateSidInfo("info");
        //$sid->kill();

        //ToDo: change it to validate if pin can be initialized; and it case is not trigger a fatal error and exit
        exec(static::COMMAND." ".static::MODE." ".static::RELAY_PIN." ". static::OUT);
        //exec(static::COMMAND." pwm-ms");
        //exec(static::COMMAND." pwmc 400");
        //exec(static::COMMAND." pwmr 1000");
    }
}