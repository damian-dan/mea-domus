<?php

namespace App\Repository;

use App\Service\SettingsService;

class SensorRepository
{
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
        $sensor = $this->findOneSensorById($id);
    }

    public function findOneSensorById(string $id)
    {
        var_dump($this->settings);
        exit();
    }
}
