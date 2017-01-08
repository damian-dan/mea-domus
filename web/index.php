<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Slim\App;
$app = new App();

$app->get('/', '\House\Controller\TemperatureController:getDesiredTemperature');
$app->post('/', '\House\Controller\TemperatureController:setDesiredTemperature');

$app->run();
