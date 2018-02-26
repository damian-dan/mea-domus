<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Logger\ConsoleLogger;
use App\Service\BoilerService;

class BoilerCommand extends Command
{
    use LockableTrait;

    // command name
    protected static $defaultName = 'app:boiler';

    /**
     * @var SymfonyStyle
     */
    private $io;

    /*
     * @var bool
     */
    private $shouldStop;

    /**
     * @var BoilerService
     */
    private $boilerService;

    /**
     * @var ...
     */
    private $logger;

    public function __construct(BoilerService $boilerService)
    {
        $this->boilerService = $boilerService;

        parent::__construct();
    }


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
        $this->logger = new ConsoleLogger($output);

        //ToDo:  Add relay state + Gpio pin + Gpio state
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->checkDuplicateProc();

        // ToDo: Check if we can use ConsoleEvents::TERMINATE Event instead
        pcntl_signal(SIGTERM, [$this, 'stopCommand']);
        pcntl_signal(SIGINT, [$this, 'stopCommand']);


        // This should be removed
        $t = 0;
        while (1) {
            pcntl_signal_dispatch();
            if ($this->shouldStop) break;

            echo gmdate("H:i:s", $t++) . "\n";
            $this->boilerService->monitor();

            // ToDo: mov e this as well
            sleep(1);
        }

        // Check what is this ???
        //$this->release();
    }

    private function stopCommand()
    {
        $this->shouldStop = true;
        //ToDo: Set output to false
    }

    private function checkDuplicateProc()
    {
        if (!$this->lock()) {
            $this->io->writeln('<error>The command is already running in another process.</error>');
            $this->logger->log("error", "Some stupid message");

            exit;
        }
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
