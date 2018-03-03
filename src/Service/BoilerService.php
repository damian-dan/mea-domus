<?php


namespace App\Service;

use App\Repository\SensorRepository;
use function MongoDB\BSON\toJSON;
use Psr\Log\LoggerInterface;
use App\Utils\Validator;


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

    /**
     * @var string
     */
    private $desiredTemperatureFile;

    /**
     * @var Validator
     */
    private $validator;

    /*
     * @var Settings
     */
    private $settings;

    /**
     * @var SensorRepository
     */
    private $sensorRepository;

    /**
     * BoilerService constructor.
     * @param LoggerInterface $logger
     * @param \App\Service\SessionService $sessionService
     * @param string $desiredTemperatureFile
     * @param Validator $validator
     * @param SettingsService $settingsService
     * @param SensorRepository $sensorRepository
     */
    public function __construct(
        LoggerInterface $logger,
        SessionService $sessionService,
        string $desiredTemperatureFile,
        Validator $validator,
        SettingsService $settingsService,
        SensorRepository $sensorRepository
    )
    {
        $this->logger = $logger;
        $this->sessionService = $sessionService;
        $this->desiredTemperatureFile = $desiredTemperatureFile;
        $this->validator = $validator;
        $this->settings = $settingsService->load();
        $this->sensorRepository = $sensorRepository;
    }

// ToDo: 1. Add logging; 

    public function monitor()
    {

        $session = $this->sessionService->current();
        $prevSessionId = (int)$session->getId() - 1;
        $prevSession = $prevSessionId ? $this->sessionService->getSessionById($prevSessionId) : null;

        if (!$this->enoughTimeHasPassed($prevSession)) {
            $this->logger->debug(sprintf('Boiler rest interval has not passed (%s minutes). Doing nothing with boiler relay', $this->restTime));
            return; //bail early as is too early to do something
        }

        $desired = $this->getDesiredTemperature();

        $current = $this->sensorRepository->getTemperatureBySensorId($this->settings->getWorkflow()->type->what);

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

    /**
     * @param Session|null $previousSession
     * @param \DateTime|null $now
     * @return bool
     */
    public function enoughTimeHasPassed(Session $previousSession = null, \DateTime $now = null): bool
    {
        if (!$previousSession) {
            // When we run the application for the
            // first time and there's no other sessions on disk
            return true;
        }

        if (!$now) {
            $now = new \DateTime();
        }

        $minutesPassed = $this->getTotalMinutes($now->diff($previousSession->closeTime()));

        return $minutesPassed >= $this->restTime;
    }

    /**
     * @param \DateInterval $int
     * @return int
     */
    protected function getTotalMinutes(\DateInterval $int): int
    {
        $minutes = (int)($int->d * 24 * 60) + ($int->h * 60) + $int->i;

        return $minutes / self::STEP;
    }

    /**
     * @return float
     */
    public function getDesiredTemperature(): float
    {
        // ToDo: Move the $this->>desiredTemperatureFile within method parameter
        // ToDo: this should get a private model of it' own: AppConfig
        $temperature = json_decode(file_get_contents($this->desiredTemperatureFile), true);

        if (
            json_last_error() != JSON_ERROR_NONE
            || !$this->validator->validateTemperatureValue($temperature['desired-temperature'])
        ) {
            $message = sprintf('Temperature could not read from %s', $this->desiredTemperatureFile);
            $this->logger->critical($message);
            throw new \RuntimeException($message);
        }

        return (float) $temperature['desired-temperature'];
    }
}
