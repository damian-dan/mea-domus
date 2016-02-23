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
}
