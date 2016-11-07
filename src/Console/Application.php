<?php
declare(strict_types=1);

namespace House\Console;

use House\Command\HouseCommand;
use House\Command\SignalCommand;
use House\House;
use Symfony\Component\Console\Application as BaseApplication;

/**
 * Class Application
 */
class Application extends BaseApplication
{
    const VERSION = '1.0';

    private static $logo = <<<LOGO
      (
      )
   ___I_
  /\-_--\
 /  \_-__\
 |[]| [] |

LOGO;

    /**
     * @var House
     */
    protected $house;

    /**
     * Application constructor.
     */
    public function __construct()
    {
        parent::__construct('Mea Domus', Application::VERSION);
    }

    /**
     * @return string
     */
    public function getHelp() : string
    {
        return self::$logo . parent::getHelp();
    }

    /**
     * @param House $house
     */
    public function setHouse(House $house)
    {
        $this->house = $house;
    }

    /**
     * @return House
     */
    public function getHouse() : House
    {
        return $this->house;
    }

    /**
     * Initializes all the composer commands
     */
    protected function getDefaultCommands() : array
    {
        return array_merge(parent::getDefaultCommands(), [
            new HouseCommand(),
            new SignalCommand(),
        ]);
    }
}