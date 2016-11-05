<?php

namespace Home\Controller;

use Slim\App;

/**
 * Class AbstractController
 * @package Home\Controller
 */
class AbstractController
{
    /**
     * @var App
     */
    protected $app;

    public function __construct(App $app)
    {
        $this->app = $app;
    }
}