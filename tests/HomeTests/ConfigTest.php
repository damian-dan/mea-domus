<?php

namespace HomeTests;
use House\Config;


/**
 * Class ConfigTest
 * @package HomeTests
 */
class ConfigTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Config
     */
    protected $config;

    public function setUp()
    {
        $this->config = new Config(__DIR__ . '/fixtures/dummyConfig.yml');
    }

    public function testConfigParsesCorrectly()
    {
        $dummyConfig = [
            "test" => "test",
            "array" => [
                "var1",
                "var2"
            ]
        ];
        $this->assertTrue($this->config->all() === $dummyConfig);
    }

    public function testConfigCorrectSingleValue()
    {
        $this->assertTrue($this->config->get('test') === 'test');

        $this->assertTrue($this->config->get('array') === ["var1", "var2"]);
    }
}
