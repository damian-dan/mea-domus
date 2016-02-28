#!/usr/bin/env php
<?php
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
            doShutDownTheFire();
            print "Caught SIGINT\n";
            exit;
    }
}

// Single File Application :)

$logFile = "logs/app.log";
$defaultValue = 19;
$sharedFile = "/data/temp.txt";
require_once __DIR__ . '/../vendor/autoload.php';

use Helper\SidHelper;
use Helper\SmartBoxModel;
use PhpGpio\Gpio;
use Symfony\Component\HttpFoundation\Request; // ToDo: Use Goute or smth else that should act as a Rest Client

$log = new Monolog\Logger('home');
$log->pushHandler(new Monolog\Handler\StreamHandler(__DIR__ . "/../" . $logFile, Monolog\Logger::WARNING));
$log->addWarning('Foo bar');
$log->addError("good");

$sid = new SidHelper(__DIR__ . "/../data/", basename(__FILE__, '.php').".pid");
//$sid->createNewSid("info");
//$sid->updateSidInfo("info");
//$sid->kill();
/*$gpio = new GPIO();
$id=17;
$gpio->setup($id, "out");
$gpio->output($id, 0);
sleep(1);
$gpio->output($id, 1);
sleep(0.5);
$gpio->output($id, 0);
exit();
*/

while(1){
    try{
        $desired = file_get_contents(__DIR__ . '/../' .$sharedFile);
        if($value === FALSE)
        {
            $log->addError("Value could not be read");
            throw new \Exception("File does not exits");
        }
        $sb = new SmartBoxModel();
        $current = $sb->getTempBySerial('28-00043c2d49ff');

        //ToDo: change it to validate if pin can be initialized
        $out = shell_exec("/usr/local/bin/gpio mode 0 out");

        isLowerThan($current, $desired);
    }catch (\Exception $e)
    {
        echo $e->getMessage();
        $log->addError($e->getMessage());
        exit();

    }
    sleep(1);
}

function isLowerThan ($current, $desired)
{
    echo "tmp curenta: " . $current . "\n";
    echo "tmp ceruta: " . $desired . "\n";
    $diff = $current - $desired;
    echo "Dif: " . $diff . " \n";
    if ($diff > 0.5)
    {
        echo "x";
        doStartUpTheFire();
    }elseif (($diff > 0.2) && ($diff < 0.5) )
    {
        echo "z";
        doShutDownTheFire();
    }else
    {
        echo "t";
        doShutDownTheFire();
    }

}

function doStartUpTheFire()
{
    echo "Start-or-Resume \n";
    $status = shell_exec("/usr/local/bin/gpio read 0");
    echo "Status Initial:" . $status . "\n";
    if ($status == 0){
        $status = shell_exec("/usr/local/bin/gpio write 0 1");
        $status = shell_exec("/usr/local/bin/gpio read 0");
        $sid = 1;
        file_get_contents('http://sb.imediat.eu/feed/log/sid/' . $sid . '/type/start', 'GET');
        //Guzzle
        //CI rest
        // packagsit: php rest client

    }

    //$gpio_off = shell_exec("/usr/local/bin/gpio -g write 17 1");
    sleep (1);
}

function doShutDownTheFire()
{
    echo "End";
    $status = shell_exec("/usr/local/bin/gpio read 0");
    if ($status == 1 ){
        $gpio_off = shell_exec("/usr/local/bin/gpio write 0 0");
        $sid = 1;
        file_get_contents('http://sb.imediat.eu/feed/log/sid/' . $sid . '/type/stop', 'GET');    }
    sleep (1);

}

function doNothing(){
    return ;
}


die('exit');

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
