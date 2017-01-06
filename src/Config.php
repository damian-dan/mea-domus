<?php
declare(strict_types=1);

namespace House;

use Symfony\Component\Yaml\Exception\ParseException;
use House\Exception\InvalidConfigurationPathException;
use House\Exception\InvalidConfigurationException;
use Symfony\Component\Yaml\Yaml;
use Dflydev\DotAccessData\Data;

/**
 * Class Config
 * @package House
 */
class Config
{
    /**
     * @var string
     */
    protected $configFile;

    /**
     * @var Data
     */
    protected $config = null;

    /**
     * Config constructor.
     * @param string $configFile
     */
    public function __construct(string $configFile)
    {
        $this->configFile = $configFile;
    }

    /**
     * @param $key
     * @param null $default
     * @return array|mixed|null
     */
    public function get($key, $default = null)
    {
        return $this->getConfig()->get($key, $default);
    }

    /**
     * @param $key
     * @param $value
     * @return $this
     */
    public function set($key, $value)
    {
        $this->getConfig()->set($key, $value);

        return $this;
    }

    /**
     * @return array
     */
    public function all() : array
    {
        return $this->getConfig()->export();
    }

    /**
     * @return Data
     */
    protected function getConfig() : Data
    {
        if (null === $this->config) {
            $this->config = $this->loadConfig();
        }
        return $this->config;
    }

    /**
     * @return Data
     */
    protected function loadConfig() : Data
    {
        if (!realpath($this->configFile)) {
            throw new InvalidConfigurationPathException(
                sprintf('No configuration file was found at the path %s', $this->configFile)
            );
        }

        if (is_dir($this->configFile)) {
            throw new InvalidConfigurationPathException(
                sprintf('Path %s points to a directory and not to a valid configuration file', $this->configFile)
            );
        }

        if (!is_readable($this->configFile)) {
            throw new InvalidConfigurationPathException(
                sprintf('Configuration file %s is not readable', $this->configFile)
            );
        }

        if (!$content = @file_get_contents($this->configFile)) {
            throw new InvalidConfigurationPathException(
                sprintf('Configuration file %s is not readable', $this->configFile)
            );
        }

        if (!strlen(trim($content))) {
            throw new InvalidConfigurationException(sprintf('Configuration file %s is empty', $this->configFile));
        }

        $parser = new Yaml();

        try {

            return new Data($parser->parse($content));

        } catch (ParseException $e) {
            throw new InvalidConfigurationException(
                sprintf('The configuration could not be parsed. Failed with message "%s"', $e->getMessage()),
                $e->getCode(),
                $e
            );
        }
    }
}
