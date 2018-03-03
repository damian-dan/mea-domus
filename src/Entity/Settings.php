<?php

namespace App\Entity;

class Settings
{
    /**
     * @var Sensor[]
     */
    private $sensors;

    private $relays;

    private $workflow;

    /**
     * @return Sensor[]
     */
    public function getSensors()
    {
        return $this->sensors;
    }

    /**
     * @param Sensor[] $sensors
     */
    public function setSensors($sensors)
    {
        $this->sensors = $sensors;
    }

    /**
     * @return mixed
     */
    public function getRelays()
    {
        return $this->relays;
    }

    /**
     * @param mixed $relays
     */
    public function setRelays($relays)
    {
        $this->relays = $relays;
    }

    /**
     * @return mixed
     */
    public function getWorkflow()
    {
        return $this->workflow;
    }

    /**
     * @param mixed $workflow
     */
    public function setWorkflow($workflow)
    {
        $this->workflow = $workflow;
    }
}
