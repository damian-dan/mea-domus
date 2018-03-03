<?php

namespace App\Utils;


class SettingsStorage
{
    private $settings;

    public function loadSettings()
    {
        $this->settings = json_decode(file_get_contents('/root/md/config/mea-domus/settings.json'));
    }
    public function getSettings()
    {
        return $this->settings;
    }
}
