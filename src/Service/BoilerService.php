<?php


namespace App\Service;

use Psr\Log\LoggerInterface;
use App\Service\SessionService;

class BoilerService
{
    /**
     * For dev, but for other non-gas related projects we need to modify this step
     */
    const STEP = 30;

    private $logger;

    /**
     * @var SessionService $sessionService
     */
    private $sessionService;

    public function __construct(LoggerInterface $logger, SessionService $sessionService)
    {
        $this->logger = $logger;
        $this->sessionService = $sessionService;
    }

// ToDo: 1. Add logging; 

    public function monitor()
    {
        /* @var int */
        $step = 30;

        $session = $this->sessionService->current();
        return;

        $prevSessionId = (int)$session->getId() - 1;
        $prevSession = $prevSessionId ? $this->sessionService->getSessionById($prevSessionId) : null;

        if (!$this->enoughTimeHasPassed($prevSession)) {
            $this->logger->debug(sprintf('Boiler rest interval has not passed (%s minutes). Doing nothing with boiler relay', $this->restTime));
            return; //bail early as is too early to do something
        }

        $desired = $this->getDesiredTemperature();
        $current = $this->getTemperature($boiler);
        $tempDiff = $desired - $current;

        $session->payload = $this->preparePayload($desired, $current);
        $sessionStartTime = $session->startTime();

        $this->logger->debug(sprintf('Desired = %s, Current = %s, Difference = %s', $desired, $current, $tempDiff));

        if ($tempDiff > 0.5) {
            echo "1" . PHP_EOL;
            $this->logger->debug('Difference higher than 0.5. Turning ON boiler relay');
            $this->turnOn($boilerRelay);

        } elseif (($tempDiff > 0.2) && ($tempDiff < 0.5)) {
            echo "2" . PHP_EOL;

            if ($sessionStartTime) {
                echo "=2.1=" . PHP_EOL;
                $minutesPassedSinceStart = $this->getTotalMinutes($sessionStartTime->diff(new \DateTime()));
                if ($minutesPassedSinceStart >= 10) { //10 minutes or more have passed
                    echo "=2.2=" . PHP_EOL;
                    $this->logger->debug(sprintf('Difference between 0.2 and 0.5 for more than %s minutes. Turning OFF relay', $this->restTime));
                    $this->turnOff($boilerRelay);
                }
            }

        } else {
            echo "3" . PHP_EOL;
            $this->logger->debug('Turning OFF boiler relay');
            $this->turnOff($boilerRelay);
        }
    }
}
