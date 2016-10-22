<?php

namespace House\Model;

use Monolog;

class GasBoiler extends BaseModel
{
    private $log;
    private $config;

    public function __construct()
    {
        $this->config = SidHelper::getConfig();

        $this->log = new Monolog\Logger('home');
        $this->log->pushHandler(new Monolog\Handler\StreamHandler(__DIR__ . "/../" . $this->config['logFile'], Monolog\Logger::WARNING));

    }

    public function getTempBySerial($sId)
    {
        $temp = exec('cat /sys/bus/w1/devices/' . $sId . '/w1_slave |grep t=');
        $temp = explode('t=',$temp);
        $temp = $temp[1] / 1000;
        $temp = round($temp,2);
        //ToDo: Validate
        return $temp;

    }

    public function setDesiredTemperature($value)
    {
        return file_put_contents(__DIR__ . '/../' .$this->config['sharedFile'], $value, LOCK_EX);
    }

    /*
    * Retrieves the temperature from the shared text file
    *
    * @return: mixed
    */
    public function getDesiredTemperature()
    {
        $desired = file_get_contents(__DIR__ . '/../' .$this->config['sharedFile']);
        if($desired == "")
        {
            $this->log->addError("Temperature value could not be read");
            throw new \Exception("Shared temperature file does not exits");
        }
        //ToDo: add an in range validator
        //ToDo: move this to the Helper class SmartBoxHelper

        return $desired;
    }
}
