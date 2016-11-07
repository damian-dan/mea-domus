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
 * Class SignalCommand
 * @package House\Command
 */
class SignalCommand extends LoopCommand
{
    protected function configure()
    {
        $this
            ->setName('signal:test')
            ->setDescription('Demo command to test the LoopCommand and PCNTL signaling')
            ->setHelp(<<<EOT
Bla bla bla
<info>php bin/console execute</info>
EOT
            )
        ;
    }

    protected function tick(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Tail -f logs/app.log while running this and then press CTRL+C');

        return 250000; //sleep between ticks
    }
}
