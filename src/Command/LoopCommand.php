<?php

namespace House\Command;

use House\House;
use House\Model;
use House\Helper\GPIOHelper;
use House\Helper\SidHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressHelper;

/**
 * Class LoopCommand
 * @package House\Command
 */
abstract class LoopCommand extends AbstractCommand
{
    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setup();

        while (true) {
            pcntl_signal_dispatch();

            $restInterval = $this->tick($input, $output);

            pcntl_signal_dispatch();

            $this->rest(is_int($restInterval) ? $restInterval : 1000000);
        }
    }

    /**
     * @param int $sleep
     */
    protected function rest(int $sleep = 1000000)
    {
        usleep($sleep);
    }

    /**
     * Used to setup dependencies for the logic within the loop
     *
     * @return mixed
     */
    protected function setup()
    {
        //prepare your stuff
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int usleep interval between ticks
     */
    abstract protected function tick(InputInterface $input, OutputInterface $output);
}
