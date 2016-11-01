<?php

namespace House\Helper;

class Config
{

    protected static $config = array();

    public static function get($name, $default = null)
    {
	return isset(self::$config[$name]) ? self::$config[$name] : $default;
    }

    public static function set($name, $value)
    {
	self::$config[$name] = $value;
    }

    public static function getAll()
    {
	return self::$config;
    }

    public function checkConfig($file)
    {
	// Todo: add some validations upon config
	// E.g.: Some mandatory fields

	return true;
    }

    protected function initialize()
    {
	if (!is_readable($config = __DIR__ . '/../../config.php'))
	{
	    throw (new \Exception("Unable to read configuration file"));
	}

	// validate
	return require $config;
    }

    public static function setup()
    {
        if (empty(self::$config))
	{
	    self::$config = self::initialize();
	}
    }
}
