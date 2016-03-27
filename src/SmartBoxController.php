<?php 

namespace Helper;

use Symfony\Component\HttpFoundation\Request; 
use Symfony\Component\HttpFoundation\JsonResponse;
use Helper\SmartBoxModel; 


class SmartBoxController
{
    private $response;
    
    public function __construct()
    {
        $this->response = new Response();
    }
    
    public function index($request, $attributes)
    {
        $sbm = new SmartBoxModel();
        if($response = $sbm->setDesiredTemperature($attributes['parameters']))
        {
            return new JsonResponse("Update Successfull", 200);    
        }

        throw new \Exception($response);
        //return new Response('An error occurred', 500);        
    }
}