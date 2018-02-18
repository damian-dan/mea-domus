<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Command\LockableTrait;

class BoilerCommand extends Command
{
    use LockableTrait;

    // command name (after bin/console)
    protected static $defaultName = 'app:boiler';

    /**
     * @var SymfonyStyle
     */
    private $io;

    /*
     * Needed to reset all input and outputs to their initial state
     */
    private $shouldStop;



    protected function configure()
    {
        $this
            ->setDescription('Handle Boiler states (on/off)')
            ->setHelp($this->getCommandHelp());
    }

    /**
     * Initialize some variables for style an relay default value
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->shouldStop = false;
        $this->io = new SymfonyStyle($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // ToDo: Check if we can use ConsoleEvents::TERMINATE Event instead
        pcntl_signal(SIGTERM, [$this, 'stopCommand']);
        pcntl_signal(SIGINT, [$this, 'stopCommand']);

        if (!$this->lock()) {
            $output->writeln('<error>The command is already running in another process.</error>');

            return 0;
        }

        $t=0;
        while(1)
        {
            //pcntl_signal_dispatch();
            if ( $this->shouldStop ) break;

            // All the code should go here
            echo gmdate("H:i:s", $t++). "\n";

            sleep(1);
        }

        $this->release();
    }

    public function stopCommand()
    {
        $this->shouldStop = true;
        //ToDo: Set output to false
    }

    /**
     * Too long to be added within the configure command
     */
    private function getCommandHelp()
    {
        return <<<'HELP'
The <info>%command.name%</info> command checks current state of the boiler against our needs:

  <info>php %command.full_name%</info> <comment> needs no further arguments</comment>

By default the command handle events of 30min with 5m for delay. To debug, or overwrite these, add <comment>--step</comment> option:

HELP;
    }
}