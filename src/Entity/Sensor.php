<?php

namespace App\Entity;

/**
 * Default class for sensors with basic properties
 *
 * Class Sensor
 * @package App\Entity
 */
class Sensor implements SensorInterface
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $type;

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $id
     * @return Sensor
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return Sensor
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }
}
