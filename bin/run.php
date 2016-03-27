#!/usr/bin/env php
<?php
// Major ToDo's: 1. get rid of IsLowerThan and other nasty "Helper" functions; 2. Improve Logging
//Todo: move these
declare(ticks = 1);

pcntl_signal(SIGTERM, "signal_handler");
pcntl_signal(SIGINT, "signal_handler");

function signal_handler($signal) {
    switch($signal) {
        case SIGTERM:
            print "Caught SIGTERM\n";
            exit;
        case SIGKILL:
            print "Caught SIGKILL\n";
            exit;
        case SIGINT:
            doShutDownTheFire($sid);
            print "Caught SIGINT\n";
            exit;
    }
}

require_once __DIR__ . '/../vendor/autoload.php';

use Helper\SidHelper;
use Helper\SmartBoxModel;
use Symfony\Component\HttpFoundation\Request; // ToDo: Use Goute or smth else that should act as a Rest Client

$config = SidHelper::getConfig();

$log = new Monolog\Logger('home');
$log->pushHandler(new Monolog\Handler\StreamHandler(__DIR__ . "/../" . $config['logFile'], Monolog\Logger::WARNING));
//$log->addWarning('Foo bar');
//$log->addError("good");

$sid = new SidHelper(__DIR__ . "/../data/", basename(__FILE__, '.php').".pid");
$sid->createNewSid("info");
//$sid->updateSidInfo("info");
//$sid->kill();
$sbh = new \Helper\SmartBoxHelper();
$sbm = new SmartBoxModel();


while(1){
    try{
        $desired = $sbm->getDesiredTemperature();
        $current = $sbm->getTempBySerial($config['sensors'][$config['mainSensor']]);

        isLowerThan($current, $desired, $sid, $sbh);
    }catch (\Exception $e)
    {
        echo $e->getMessage();
        $log->addError($e->getMessage());
        exit();

    }
    sleep(1);
}

function isLowerThan ($current, $desired, $sid, $sbh)
{
    $desired = 27;
    $diff = $desired - $current;
    echo "Dif: " . $diff . " \n";

    if ($diff > 0.5)
    {
        doStartUpTheFire($sid, $sbh);
    }elseif (($diff > 0.2) && ($diff < 0.5) )
    {
        if (($sid->getSessionStartTime() + (60*10)) < $sid->now())
        {
            // Why ? there might be situations in which this is not started. Within doShutDownFire we do not have any knowledge about the current state of the relay
            // It would be more logically to keep logging/starting within then function still :)
            //ToDo: Log this case as well: we started the fire, but after 5 minutes we drop it
            echo "Edge case: after 5 minutes, we want to cancel the fire";
            doShutDownTheFire($sbh, $sid, 60*5);
        }
    }else
    {
        doShutDownTheFire($sbh, $sid);
    }
}

function doStartUpTheFire($sid, $sbh)
{

    $status = $sbh->readRelayState();
    echo "Status Initial:" . $status . "\n";
    if ($status == 0){
        $status = $sbh->write(0, 1);
        $sid->startNewCycle();
        file_get_contents('http://sb.imediat.eu/feed/log/sid/' . $sid->getCurrentSidId() . '/type/start', 'GET');
        //Guzzle
        //CI rest
        // packagsit: php rest client

    }else{
        if (($sid->getSessionStartTime() + (30*60)) < $sid->now())
        {
            // Maximum execution time has been reached
            //ToDo: Special Case: Log it
            echo "The fire is already burning for 30 minutes; give it some sleep/rest ";
            doShutDownTheFire($sid, 60*5);
        }
    }

    //$gpio_off = shell_exec("/usr/local/bin/gpio -g write 17 1");
    sleep (1);
}

function doShutDownTheFire($sbh, $sid, $sleep=1)
{
    // ToDo: 1. add $sid as parameter | 2. add sleep as param | 3. Add $sid->getDetails as mixed object
    $status = $sbh->readRelayState();
    if ($status == 1 ){
        $gpio_off = $sbh->write(0,1);
        $sid->stopNewCycle();
        file_get_contents('http://sb.imediat.eu/feed/log/sid/' . $sid->getCurrentSidId() . '/type/stop', 'GET');
    }
    sleep ($sleep);
}

function doNothing()
{
    return ;
}

/*
 use PhpGpio\Gpio;
$gpio = new GPIO();
$id=17;
$gpio->setup($id, "out");
$gpio->output($id, 0);
sleep(1);
$gpio->output($id, 1);
sleep(0.5);
$gpio->output($id, 0);
exit();
*/


// ToDO: on each loop add a session_ID and log this
// Ar trebuii sa fie ceva de genul ok, treb sa maresc temperatura cu 0.5 grade rulez pentru 30 de min sub aceeasi sesiune. Apoi las sa se odihneasca putin centrala sii ii dau o noua sesiune chiar daca nu am ajuns la temp care trebuie

// chmod +x /var/www/netrom/dan/io/read.php
// run it as
// nohup php myscript.php &
// retrieve temp from a file based storage. An Web client will write it there

// Check if PID is available. If it is available then the system is already running
if (file_exists( "/proc/$pid" )){

}
// Thse shot way: posix_getpgid($pid);

// Add logging (maybe Monolog dirrectly)
// Include composer
// Switch to Express for web

// put a green dot when a connection has been made less than
// Handle CRC errors from devices. Add maybe a santinel?
