<?php

namespace House\Command;

use House\House;
use House\Model;
use House\HouseHelper;
use House\SidHelper;
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
        $sid = new SidHelper(__DIR__ . "/../../data/", basename(__FILE__, '.php').".pid");

        while (1) {
            try {
                $desired = $boilerModel->getDesiredTemperature();
                $current = $boilerModel->getTempBySerial(
                    $boilerModel->getConfig()['sensors'][$boilerModel->getConfig()['mainSensor']]);

                $this->isLowerThan($current, $desired, $sid, new HouseHelper(), $boilerModel);
            } catch (\Exception $e) {
                echo $e->getMessage();
                $boilerModel->getLog()->addError($e->getMessage());
            }
            sleep(1);
        }
    }

    private function isLowerThan ($current, $desired, $sid, $sbh, $bm)
    {
        $diff = $desired - $current;

        echo "Dif: " . $diff . " \n"; //ToDo: remove this once debug completed

        if ($diff > 0.5)
        {
            $bm->doStartUpTheFire($sid, $sbh);
        }elseif (($diff > 0.2) && ($diff < 0.5) )
        {
            if (($sid->getSessionStartTime() + (60*10)) < $sid->now())
            {
                // Why ? there might be situations in which this is not started. Within doShutDownFire we do not have any knowledge about the current state of the relay
                // It would be more logically to keep logging/starting within then function still :)
                //ToDo: Log this case as well: we started the fire, but after 5 minutes we drop it
                echo "Edge case: after 5 minutes, we want to cancel the fire";
                $bm->doShutDownTheFire($sbh, $sid, 60*5);
            }
        }else
        {
            $bm->doShutDownTheFire($sbh, $sid);
        }
    }
}
