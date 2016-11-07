<?php
declare(strict_types=1);

namespace House\Util;

use House\House;
use Monolog\Logger;

/**
 * Class ErrorHandler
 * @package House\Util
 */
class ErrorHandler
{
    /**
     * @var Logger
     */
    private static $logger;

    /**
     * @var House
     */
    private static $house;

    /**
     * Error handler
     *
     * @param int    $level   Level of the error raised
     * @param string $message Error message
     * @param string $file    Filename that the error was raised in
     * @param int    $line    Line number the error was raised at
     *
     * @static
     * @throws \ErrorException
     */
    public static function handle(int $level, string $message, string $file, int $line)
    {
        // error code is not included in error_reporting
        if (!(error_reporting() & $level)) {
            return;
        }
        if (ini_get('xdebug.scream')) {
            $message .= "\n\nWarning: You have xdebug.scream enabled, the warning above may be".
                "\na legitimately suppressed error that you were not supposed to see.";
        }
        if ($level !== E_DEPRECATED && $level !== E_USER_DEPRECATED) {
            throw new \ErrorException($message, 0, $level, $file, $line);
        }

        if (self::$logger) {
            self::$logger->critical($message);
            $message = 'Stack trace:';
            $message .= array_filter(array_map(function ($a) {
                if (isset($a['line'], $a['file'])) {
                    return $a['file'].':'.$a['line'];
                }
                return null;
            }, array_slice(debug_backtrace(), 2)));

            self::$logger->critical($message);
        }

        if (self::$house) {
            self::$house->shutdown();
        }
    }

    /**
     * Register error handler.
     *
     * @param Logger|null $logger
     * @param House|null $house
     */
    public static function register(Logger $logger = null, House $house = null)
    {
        set_error_handler(array(__CLASS__, 'handle'));
        error_reporting(E_ALL | E_STRICT);
        self::$logger = $logger;
        self::$house = $house;
    }
}