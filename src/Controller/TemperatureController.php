<?php

namespace House\Controller;

use House\Console\Application;
use House\Model\Boiler;
use Symfony\Component\HttpFoundation\JsonResponse;

class TemperatureController
{
    /**
     * @var \House\House
     */
    protected $house;

    protected $boiler;

    public function __construct()
    {
        $this->house = new \House\House(new Application(), __DIR__.'/../../domus.yml');
        $this->boiler = new Boiler($this->house->config()->get('sensors')[$this->house->config()->get('mainSensor')]);
    }

    public function getDesiredTemperature($request, $response, $args)
    {
        return new JsonResponse([
            "current" => $this->house->boilerService()->getTemperature($this->boiler),
            "desired" => $this->house->boilerService()->getDesiredTemperature()
        ]);
    }

    public function setDesiredTemperature($request, $response, $args)
    {
        $desired = (float) $request->post('temperature');
        if ( ! $desired) {
            return new JsonResponse([
                "error" => true,
                "message" => sprintf(
                    "Invalid temperature value. Expected a float value bigger than 0, got %s",
                    $request->post("temperature")
                )
            ], 500);
        }

        $this->house->boilerService()->setDesiredTemperature($desired);

        return new JsonResponse([
            "success" => false,
            "message" => sprintf("Desired temperature set to %s", $desired)
        ]);
    }
}