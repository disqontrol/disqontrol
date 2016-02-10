<?php
/*
 * This file is part of the Disqontrol package.
 *
 * (c) Webtrh s.r.o. <info@webtrh.cz>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Disqontrol\Test\Integration;

use Symfony\Component\Config\Definition\Processor;
use Disqontrol\Configuration\ConfigDefinition as Config;
use Disqontrol\Configuration\Configuration;
use Symfony\Component\Yaml\Yaml;

/**
 * Test the loading and parsing of the example configuration file
 */
class ConfigurationTest extends \PHPUnit_Framework_TestCase
{
    const CONFIG_FILE = 'disqontrol.yml.dist';
    const TEST_LOG_PATH = 'foo/bar';
    const TEST_JOB_LIFETIME = 321;
    const UNDEFINED_QUEUE = 'fooqueuebar123';

    /**
     * @var array Freshly loaded config parameters
     */
    private $configParams;

    protected function setUp()
    {
        $configArray = Yaml::parse(file_get_contents(self::CONFIG_FILE));

        $processor = new Processor();
        $processedParams = $processor->processConfiguration(
            new Config(),
            [$configArray]
        );
        $this->assertTrue(
            is_array($processedParams[Config::DISQONTROL]) and
            ! empty($processedParams[Config::DISQONTROL])
        );

        $this->configParams = $processedParams[Config::DISQONTROL];
    }

    /**
     * Test just the loading of the configuration
     */
    public function testLoadConfiguration()
    {
        $configParams = $this->configParams;
        $this->assertTrue(is_array($configParams) and ! empty($configParams));

        $disqontrolConfig = new Configuration($configParams);
        $this->assertArraySubset($configParams, $disqontrolConfig->getWholeConfig());
    }

    /**
     * Change a parameter before instantiating the config object
     */
    public function testSimpleConfigParameters()
    {
        $configParams = $this->configParams;
        $configParams[Config::LOG_DIR] = self::TEST_LOG_PATH;

        $disqontrolConfig = new Configuration($configParams);
        $this->assertSame($disqontrolConfig->getLogDir(), self::TEST_LOG_PATH);
    }

    /**
     * Change a fallback parameter and test whether it's used correctly
     */
    public function testAddedFallbackParameters()
    {
        $configParams = $this->configParams;
        // Grab the first queue name
        $queue = current(array_keys($configParams[Config::QUEUES]));
        // Unset a parameter of the first queue
        unset($configParams[Config::QUEUES][$queue][Config::MAX_JOB_LIFETIME]);
        // Set our fallback value
        $configParams[Config::QUEUE_DEFAULTS][Config::MAX_JOB_LIFETIME]
            = self::TEST_JOB_LIFETIME;

        $disqontrolConfig = new Configuration($configParams);

        // Now check if the fallback parameter was added correctly to the queue
        $queueConfig = $disqontrolConfig->getQueuesConfig()[$queue];
        $this->assertSame($queueConfig[Config::MAX_JOB_LIFETIME], self::TEST_JOB_LIFETIME);

        // And check if the object can return the value for undefined queues, too
        $this->assertSame(
            $disqontrolConfig->getMaxJobLifetime(self::UNDEFINED_QUEUE),
            self::TEST_JOB_LIFETIME
        );
    }
}
