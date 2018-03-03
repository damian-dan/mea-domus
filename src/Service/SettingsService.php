<?php

namespace App\Service;

use JsonMapper;
use App\Entity\Settings;

class SettingsService
{
    /**
     * @var string
     */
    private $settingsFile;

    public function __construct(string $settingsFile)
    {
        $this->settingsFile = $settingsFile;
    }

    public function load()
    {
        $json = json_decode(file_get_contents($this->settingsFile));
        // ToDo: validate JSON
        $mapper = new JsonMapper();
        $mapper->bIgnoreVisibility = true;

        return $settings = $mapper->map($json, new Settings());
    }

}
