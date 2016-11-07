<?php
declare(strict_types=1);

namespace House\Command;

use House\Console\Application;
use Symfony\Component\Console\Command\Command;

/**
 * Class AbstractCommand
 * @package House\Command
 */
class AbstractCommand extends Command
{
    /**
     * @return \House\House
     */
    public function getHouse()
    {
        return $this->getApplication()->getHouse();
    }

    /**
     * Rewrite for IDE auto-completion
     *
     * @return Application
     */
    public function getApplication() : Application
    {
        return parent::getApplication();
    }
}