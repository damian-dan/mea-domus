<?php

namespace App\Repository;

use App\Service\SettingsService;
use App\Entity;

class SensorRepository
{
    /**
     * Column name from the settings file
     */
    const COLUMN_ID = "id";
    /**
     * @var SettingsService
     */
    private $settings;

    public function __construct(SettingsService $settingsService)
    {
        $this->settings = $settingsService->load();
    }

    /**
     *
     */
    public function getTemperatureBySensorId(string $id)
    {
        if(!$sensor = $this->findOneSensorById($id))
        {
            throw new \RuntimeException(sprintf('A sensor with "%s" could not be found be found', $id));
        }

        $sensorType = new \ReflectionClass ("Entity\\" .$sensor->getType());

        return $sensorType->getTemperature();

    }

    /**
     * Searches for a Sensor based on a sensor ID
     *
     * @param string $id
     * @return bool|mixed
     */
    public function findOneSensorById(string $id)
    {
        $sensor = array_filter(
            $this->settings->getSensors(),
            function ($e) use ($id) {
                return $e->getId() == $id;
            }
        );

        if(is_a(reset($sensor), 'App\Entity\Sensor')) {
            return reset($sensor);
        }
        return false;
    }
}
