<?php

namespace House\Console;

use House\Command\HouseCommand;
use Symfony\Component\Console\Application as BaseApplication;

/**
 * Class Application
 */
class Application extends BaseApplication
{
    const VERSION = '1.0';

    private static $help = '
 ';

    public function __construct()
    {
        parent::__construct('House', Application::VERSION);
    }

    public function getHelp()
    {
        return self::$help . parent::getHelp();
    }

    /**
     * Initializes all the composer commands
     */
    protected function getDefaultCommands()
    {
        $commands = parent::getDefaultCommands();
        $commands[] = new HouseCommand();
        return $commands;
    }
}