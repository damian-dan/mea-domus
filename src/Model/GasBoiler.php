<?php

namespace House\Model;

use House\SidHelper;
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

        if (!is_numeric($temp))
            throw new \Exception("Unable to read following ID: ". $sId);

        return round($temp, 2);

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
        $desired = file_get_contents(__DIR__ . '/../../' . $this->config['sharedFile']);
        if($desired == "")
        {
            $this->log->addError("Temperature value could not be read for ID: " . $this->config['sharedFile']);
            throw new \Exception("Shared temperature file does not exits");
        }
        //ToDo: add an in range validator
        //ToDo: move this to the Helper class SmartBoxHelper

        return $desired;
    }
    private function doStartUpTheFire($sid, $sbh)
    {

        $status = $sbh->readRelayState();
        echo "Status Initial:" . $status . "\n";
        if ($status == 0){
            $status = $sbh->write(0, 1);
            $sid->startNewCycle();
            file_get_contents('http://sb.imediat.eu/feed/log/sid/' . $sid->getCurrentSidId() . '/type/start', 'GET');
            //Guzzle
            //CI rest
            // packagsit: php rest client

        }else{
            if (($sid->getSessionStartTime() + (30*60)) < $sid->now())
            {
                // Maximum execution time has been reached
                //ToDo: Special Case: Log it
                echo "The fire is already burning for 30 minutes; give it some sleep/rest ";
                doShutDownTheFire($sid, 60*5);
            }
        }

        //$gpio_off = shell_exec("/usr/local/bin/gpio -g write 17 1");
        sleep (1);
    }

    private function doShutDownTheFire($sbh, $sid, $sleep=1)
    {
        // ToDo: 1. add $sid as parameter | 2. add sleep as param | 3. Add $sid->getDetails as mixed object
        $status = $sbh->readRelayState();
        if ($status == 1 ){
            $gpio_off = $sbh->write(0,1);
            $sid->stopNewCycle();
            file_get_contents('http://sb.imediat.eu/feed/log/sid/' . $sid->getCurrentSidId() . '/type/stop', 'GET');
        }
        sleep ($sleep);
    }

    private function doNothing()
    {
        return ;
    }

    public function getConfig()
    {
        return $this->config;
    }

    public function getLog()
    {
        return $this->log;
    }
}
