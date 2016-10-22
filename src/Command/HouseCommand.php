<?php

namespace House\Command;

use House\House;
use House\Model;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressHelper;

/**
 * Class HouseCommand
 * @package House\Command
 */
class HouseCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('start')
            ->setDescription('bla bla ')
            ->setHelp(<<<EOT
Bla bla bla
<info>php bin/console execute</info>
EOT
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        //$house = new House();
        $boilerModel = new Model\GasBoiler();
        echo "me";
    }
}