<?php
namespace Disqontrol\Configuration;

use Disqontrol\Configuration\DisqontrolConfigurationDefinition as Config;

/**
 * The Disqontrol configuration wrapped in an object
 *
 * @author Martin Patera <mzstic@gmail.com>
 */
class DisqontrolConfiguration
{
    /**
     * Configuration array
     *
     * @var array
     */
    private $config;

    /**
     * @param array $configArray
     */
    public function __construct(array $configArray)
    {
        $this->config = $configArray;
        $this->setQueueDefaults();
        $this->setConsumerDefaults();
    }

    /**
     * Get the whole configuration
     *
     * @return array
     */
    public function getWholeConfig()
    {
        return $this->config;
    }

    /**
     * Get the log directory
     *
     * @return string
     */
    public function getLogDir()
    {
        return $this->config[Config::LOG_DIR];
    }

    /**
     * Get the cache directory
     *
     * @return string
     */
    public function getCacheDir()
    {
        return $this->config[Config::CACHE_DIR];
    }

    /**
     * Get Disque configuration
     *
     * @return array
     */
    public function getDisqueConfig()
    {
        return $this->config[config::DISQUE];
    }

    /**
     * Get queues configuration
     *
     * @return array
     */
    public function getQueuesConfig()
    {
        return $this->config[Config::QUEUES];
    }

    /**
     * Get default settings for queues
     *
     * @return array
     */
    public function getQueueDefaults()
    {
        return $this->config[Config::QUEUE_DEFAULTS];
    }

    /**
     * Get consumers configuration
     *
     * @return array
     */
    public function getConsumersConfig()
    {
        return $this->config[Config::CONSUMERS];
    }

    /**
     * Get default settings for consumers
     *
     * @return array
     */
    public function getConsumerDefaults()
    {
        return $this->config[Config::CONSUMER_DEFAULTS];
    }

    /**
     * Set default values to the configuration of consumers, unless set manually
     */
    private function setConsumerDefaults()
    {
        $this->setDefaults(Config::CONSUMERS, $this->getConsumerDefaults());
    }

    /**
     * Set default values to the configuration of queues, unless set manually
     */
    private function setQueueDefaults()
    {
        $this->setDefaults(Config::QUEUES, $this->getQueueDefaults());
    }

    /**
     * Set default values to the specified config group, unless set manually.
     *
     * @param string $configGroup
     * @param array $defaults
     */
    private function setDefaults($configGroup, array $defaults)
    {
        foreach ($this->config[$configGroup] as $key => $value) {
            $this->config[$configGroup][$key] = array_merge($defaults, $value);
        }
    }
}
