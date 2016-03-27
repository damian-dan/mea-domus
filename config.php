<?php
return $config = array(
    'logFile' => "logs/app.log",
    'defaultValue' => 19,
    'sharedFile' => "/data/temp.txt",
    '60' => 60,
    'relayPinNumber' => 0, //bc,
    'sensors' => array("dormitor-1" => "28-000006b095a7",
                "hol-1" => "none"), // ToDo: move this under a web settings section
    'mainSensor' => "dormitor-1" // ToDo: should be retrieved from the above and allow the box to configure the main one
);
