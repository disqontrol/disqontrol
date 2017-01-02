<?php
/*
 * This file is part of the Disqontrol package.
 *
 * (c) Mediaplus.cz s.r.o. <info@mediaplus.cz>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Disqontrol\Configuration;

use Disqontrol\Configuration\ConfigDefinition as Config;
use Disqontrol\Worker\WorkerType;

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
     * Queues in consumer configuration that are undefined in queues config
     *
     * @var array
     */
    private $undefinedQueues;

    /**
     * @var string A path to the current bootstrap file
     */
    private $bootstrapFilePath;

    /**
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        $this->setQueueDefaults();
        $this->setConsumerDefaults();

        $this->undefinedQueues = array_diff(
            $this->getQueuesWithConfiguredConsumers(),
            array_keys($this->config[Config::QUEUES])
        );
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
     * Get the worker configuration for a queue
     *
     * @param string $queue
     *
     * @return array Worker configuration
     */
    public function getWorker($queue)
    {
        return $this->config[Config::QUEUES][$queue][Config::WORKER];
    }

    /**
     * Get the type of the worker of a queue
     *
     * @param string $queue
     *
     * @return string The queue's worker type
     */
    public function getWorkerType($queue)
    {
        return $this->getWorker($queue)[Config::WORKER_TYPE];
    }

    /**
     * Get the directions for the worker of a queue
     *
     * This means either of these parameters:
     * command
     * address
     * name
     *
     * @param string $queue
     *
     * @return string Directions
     */
    public function getWorkerDirections($queue)
    {
        $worker = $this->getWorker($queue);

        $possibleKeys = [
            Config::COMMAND_WORKER_COMMAND,
            Config::HTTP_WORKER_ADDRESS,
            Config::PHP_WORKER_NAME,
        ];

        foreach ($possibleKeys as $possibleKey) {
            if ( ! empty($worker[$possibleKey])) {
                return $worker[$possibleKey];
            }
        }
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
     * Get the names of the queues supported by a consumer
     *
     * @param int $consumerIndex
     *
     * @return array
     */
    public function getConsumerQueues($consumerIndex)
    {
        return $this->getConsumerParameterOrDefault($consumerIndex, Config::QUEUES);
    }

    /**
     * Get the consumer minimum processes
     *
     * @param int $consumerIndex
     *
     * @return int
     */
    public function getConsumerMinProcesses($consumerIndex)
    {
        return $this->getConsumerParameterOrDefault($consumerIndex, Config::MIN_PROCESSES);
    }

    /**
     * Return the default minimum count of consumer process
     *
     * @return int
     */
    public function getDefaultConsumerMinProcesses()
    {
        return $this->config[Config::CONSUMER_DEFAULTS][Config::MIN_PROCESSES];
    }

    /**
     * Get the consumer maximum processes
     *
     * @param int $consumerIndex
     *
     * @return int
     */
    public function getConsumerMaxProcesses($consumerIndex)
    {
        return $this->getConsumerParameterOrDefault($consumerIndex, Config::MAX_PROCESSES);
    }

    /**
     * Return the default maximum count of consumer process
     *
     * @return int
     */
    public function getDefaultConsumerMaxProcesses()
    {
        return $this->config[Config::CONSUMER_DEFAULTS][Config::MAX_PROCESSES];
    }

    /**
     * Get the job batch of a consumer
     *
     * @param int $consumerIndex
     *
     * @return int
     */
    public function getConsumerJobBatch($consumerIndex)
    {
        return $this->getConsumerParameterOrDefault($consumerIndex, Config::JOB_BATCH);
    }

    /**
     * Return the default consumer job batch
     *
     * @return int
     */
    public function getDefaultConsumerJobBatch()
    {
        return $this->config[Config::CONSUMER_DEFAULTS][Config::JOB_BATCH];
    }

    /**
     * Get the names of all queues that have their explicitly defined consumer
     *
     * @return string[]
     */
    public function getQueuesWithConfiguredConsumers()
    {
        $queuesWithConfiguredConsumers = array();
        foreach ($this->config[Config::CONSUMERS] as $i => $consumer) {
            $queuesWithConfiguredConsumers = array_merge(
                $queuesWithConfiguredConsumers,
                $this->getConsumerQueues($i)
            );
        }

        return $queuesWithConfiguredConsumers;
    }

    /**
     * Get the names of all queues that don't have an explicitly defined consumer
     *
     * These queues will all use the default consumer process.
     *
     * @return string[]
     */
    public function getQueuesWithDefaultConsumer()
    {
        $queuesWithConsumers = $this->getQueuesWithConfiguredConsumers();
        $allQueues = array_keys($this->getQueuesConfig());

        $queuesWithDefaultConsumer = array_diff(
            $allQueues,
            $queuesWithConsumers
        );

        return $queuesWithDefaultConsumer;
    }

    /**
     * Check if the configuration contains a consumer for an undefined queue
     *
     * @return bool
     */
    public function hasUndefinedQueues()
    {
        return ! empty($this->undefinedQueues);
    }

    /**
     * Get all queues that have a consumer but are undefined
     *
     * @return array
     */
    public function getUndefinedQueues()
    {
        return $this->undefinedQueues;
    }

    /**
     * @return string
     */
    public function getBootstrapFilePath()
    {
        return $this->bootstrapFilePath;
    }

    /**
     * @param string $bootstrapFilePath
     */
    public function setBootstrapFilePath($bootstrapFilePath)
    {
        $this->bootstrapFilePath = $bootstrapFilePath;
    }

    /**
     * Get all PHP workers defined in the configuration
     *
     * @return string[] Array with the workers' names
     */
    public function getPhpWorkers()
    {
        $phpWorkers = array();

        foreach ($this->getQueuesConfig() as $queue) {
            $worker = $queue[Config::WORKER];
            foreach ($worker as $key => $value) {
                if ($key === Config::WORKER_TYPE) {

                    $workerType = str_replace('-', '_', $value);
                    $phpWorkerTypes = [
                        WorkerType::INLINE_PHP_WORKER,
                        WorkerType::ISOLATED_PHP_WORKER
                    ];

                    if ( ! in_array($workerType, $phpWorkerTypes)) {
                        // This is not a PHP worker
                        break;
                    }
                }

                $possibleAddressKeys = [
                    Config::COMMAND_WORKER_COMMAND,
                    Config::HTTP_WORKER_ADDRESS,
                    Config::PHP_WORKER_NAME,
                ];
                if (in_array($key, $possibleAddressKeys)) {
                    $phpWorkers[] = $value;
                }
            }
        }

        return $phpWorkers;
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
     * Get a config parameter for a consumer, or a default value if it's not defined
     *
     * @param string $consumerIndex
     * @param string $parameterName
     *
     * @return mixed
     */
    private function getConsumerParameterOrDefault($consumerIndex, $parameterName)
    {
        if (isset($this->config[Config::CONSUMERS][$consumerIndex][$parameterName])) {
            return $this->config[Config::CONSUMERS][$consumerIndex][$parameterName];
        }

        return $this->config[Config::CONSUMER_DEFAULTS][$parameterName];
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
