#!/usr/bin/env php
<?php

    // Single File Application :)

$logFile = "logs/app.log";
$defaultValue = 19;
$sharedFile = "data/tmp.txt";

require_once __DIR__ . '/../vendor/autoload.php';

use Helper\SidHelper;
use PhpGpio\Gpio;

$log = new Monolog\Logger('home');
$log->pushHandler(new Monolog\Handler\StreamHandler(__DIR__ . "/../" . $logFile, Monolog\Logger::WARNING));
$log->addWarning('Foo bar');
$log->addError("good");

$sid = new SidHelper(__DIR__ . "/../data/", basename(__FILE__, '.php').".pid");
//$sid->createNewSid("info");
//$sid->updateSidInfo("info");
//$sid->kill();
/*$gpio = new GPIO();
$id=0;
$gpio->setup($id, "out");
$gpio->output($id, 0);
$gpio->output($id, 1);
sleep(0.5);
$gpio->output($id, 0);
exit();*/

while(1){
    try{
        $value = @file_get_contents(__DIR__ . '/../' .$sharedFile);
        if($value === FALSE)
        {
            $log->addError("Value could not be read");
            throw new \Exception("File does not exits");
        }
        $desired = (int)strstr($value,"\n",true);

        isLowerThan($current, $desired);

        echo $desired;
    }catch (\Exception $e)
    {
        $log->addError($e->getMessage());
    }
    sleep(0.1);
}

function isLowerThan ($current, $desired)
{
    $diff = (int)($desired - $actual);
    if ($diff > 0.5)
    {
        doStartUpTheFire();
    }elseif (diff < 0.5 && diff > 0.2)
    {
        doStartUpTheFire();
    }else
    {
        doNothing();
    }

}

function doStartUpTheFire()
{
    $gpio_off = shell_exec("/usr/local/bin/gpio -g write 17 1");
    $gpio_off = shell_exec("/usr/local/bin/gpio -g write 17 1");
    $gpio_off = shell_exec("/usr/local/bin/gpio -g write 17 1");
    sleep (0.5);
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
