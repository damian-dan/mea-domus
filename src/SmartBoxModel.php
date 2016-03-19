<?php

namespace Helper;

class SmartBoxModel
{
    public function updateValue($value)
    {
        //TODO: Retrieve file from configuration file
        return file_put_contents(__DIR__ . '/../data/temp.txt', $value, LOCK_EX);
    }

    public function getTempBySerial($sId)
    {
        $temp = exec('cat /sys/bus/w1/devices/' . $sId . '/w1_slave |grep t=');
	$temp = explode('t=',$temp);
	$temp = $temp[1] / 1000;
	$temp = round($temp,2);

	return $temp;

    }

    /*
    * Retrieves the temperature from the shared text file
    *
    * @return: mixed
    */    
    public function getDesiredTemperature()
    {
        $desired = file_get_contents(__DIR__ . '/../' .$sharedFile);
        if($value === FALSE)
        {
            $log->addError("Value could not be read");
            throw new \Exception("File does not exits");
        }

        return $desired;
    }
}
