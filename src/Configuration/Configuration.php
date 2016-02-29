<?php
/*
 * This file is part of the Disqontrol package.
 *
 * (c) Webtrh s.r.o. <info@webtrh.cz>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Disqontrol\Configuration;

use Disqontrol\Configuration\ConfigDefinition as Config;
use Disqontrol\Dispatcher\Failure\FailureStrategyInterface;

/**
 * The Disqontrol configuration wrapped in an object
 *
 * @author Martin Patera <mzstic@gmail.com>
 * @author Martin Schlemmer
 */
class Configuration
{
    /**
     * Maximum allowed job TTL. This is defined in Disque.
     * See comments in disqontrol.yml.dist
     */
    const MAX_ALLOWED_JOB_LIFETIME = 3932100;

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
     * Get the maximum number a failed job in the given queue can be retried
     *
     * @param string $queue
     *
     * @return int The maximum number of retries for the given queue
     */
    public function getMaxRetries($queue)
    {
        return $this->getQueueParameterOrDefault($queue, Config::MAX_RETRIES);
    }

    /**
     * Get the failure queue for completely failed jobs for the given queue
     *
     * @param string $queue
     *
     * @return string Failure queue
     */
    public function getFailureQueue($queue)
    {
        return $this->getQueueParameterOrDefault($queue, Config::FAILURE_QUEUE);
    }

    /**
     * Get the name of the failure strategy for the given queue
     *
     * @param string $queue
     *
     * @return string Failure strategy name
     */
    public function getFailureStrategyName($queue)
    {
        return $this->getQueueParameterOrDefault($queue, Config::FAILURE_STRATEGY);
    }


    /**
     * Get max job process time from the configuration for the given queue
     *
     * @param string $queue
     *
     * @return int Max job process time in seconds
     */
    public function getJobProcessTimeout($queue)
    {
        return $this->getQueueParameterOrDefault($queue, Config::JOB_PROCESS_TIMEOUT);
    }

    /**
     * Get max job lifetime from the configuration for the given queue
     *
     * @param string $queue
     *
     * @return int Max job lifetime in seconds
     */
    public function getJobLifetime($queue)
    {
        return $this->getQueueParameterOrDefault($queue, Config::JOB_LIFETIME);
    }

    /**
     * Get the maximum allowed job lifetime as defined in Disque
     *
     * @return int Max job lifetime in seconds
     */
    public function getMaxAllowedJobLifetime()
    {
        return self::MAX_ALLOWED_JOB_LIFETIME;
    }

    /**
     * Get a config parameter for a queue, or a default value if it's not defined
     *
     * @param string $queue
     * @param string $parameterName
     *
     * @return mixed
     */
    private function getQueueParameterOrDefault($queue, $parameterName)
    {
        if (isset($this->config[Config::QUEUES][$queue][$parameterName])) {
            return $this->config[Config::QUEUES][$queue][$parameterName];
        }

        return $this->config[Config::QUEUE_DEFAULTS][$parameterName];
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
     * @param array  $defaults
     */
    private function setDefaults($configGroup, array $defaults)
    {
        foreach ($this->config[$configGroup] as $key => $value) {
            $this->config[$configGroup][$key] = array_merge($defaults, $value);
        }
    }
}
