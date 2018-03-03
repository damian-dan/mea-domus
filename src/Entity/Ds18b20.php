<?php

namespace App\Entity;

class Ds18b20 extends Sensor
{
    private $cmd = "/bin/ds18b20.sh";

    public function getTemperature()
    {
        return "ula la";
    }

}
