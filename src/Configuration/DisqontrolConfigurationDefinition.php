<?php
namespace Disqontrol\Configuration;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Definition of Disqontrol configuration.
 *
 * For more information about the configuration see docs and the sample
 * configuration file.
 *
 * @author Martin Patera <mzstic@gmail.com>
 */
class DisqontrolConfigurationDefinition implements ConfigurationInterface
{
    const DISQONTROL = 'disqontrol';
    const LOG_DIR = 'log_dir';
    const CACHE_DIR = 'cache_dir';

    const DISQUE = 'disque';
    const HOST = 'host';
    const PORT = 'port';
    const PASSWORD = 'password';
    const CONNECTION_TIMEOUT = 'connection_timeout';
    const RESPONSE_TIMEOUT = 'response_timeout';

    const QUEUE_DEFAULTS = 'queue_defaults';
    const FAILURE_QUEUE = 'failure_queue';
    const MAX_JOB_PROCESS_TIME = 'max_job_process_time';
    const MAX_JOB_LIFETIME = 'max_job_lifetime';

    const QUEUES = 'queues';

    const CONSUMER_DEFAULTS = 'consumer_defaults';
    const MIN_PROCESSES = 'min_processes';
    const MAX_PROCESSES = 'max_processes';
    const AUTOSCALE = 'autoscale';
    const JOB_BATCH = 'job_batch';
    const CONSUMERS = 'consumers';

    const WORKER = 'worker';

    /** Default values */
    const LOG_DIR_DEFAULT = 'app/log';
    const CACHE_DIR_DEFAULT = 'app/cache';
    const MAX_JOB_PROCESS_TIME_DEFAULT = 600;
    const MAX_JOB_LIFETIME_DEFAULT = 172800;
    const MIN_PROCESSES_DEFAULT = 2;
    const MAX_PROCESSES_DEFAULT = 5;
    const AUTOSCALE_DEFAULT = true;
    const JOB_BATCH_DEFAULT = 10;

    /**
     * Get the definition of whole configuration tree
     *
     * @return TreeBuilder
     */
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder();
        $rootNode = $builder->root(self::DISQONTROL);

        $rootNode
            ->children()
                ->arrayNode(self::DISQONTROL)
                    ->children()
                        ->scalarNode(self::LOG_DIR)
                            ->defaultValue(self::LOG_DIR_DEFAULT)
                            ->info('The log directory')
                        ->end()
                        ->scalarNode(self::CACHE_DIR)
                            ->defaultValue(self::CACHE_DIR_DEFAULT)
                            ->info('The cache directory')
                        ->end()
                        ->append($this->addDisqueNode())
                        ->append($this->addQueueDefaultsNode())
                        ->append($this->addQueuesNode())
                        ->append($this->addConsumerDefaultsNode())
                        ->append($this->addConsumersNode())
                    ->end()
                ->end()
            ->end();

        return $builder;
    }

    /**
     * Define a Disque node
     *
     * @return NodeDefinition
     */
    private function addDisqueNode()
    {
        $builder = new TreeBuilder();
        $node = $builder->root(self::DISQUE);
        $node
            ->info('Configure the connection to Disque.')
            ->isRequired()
            ->requiresAtLeastOneElement()
            ->prototype('array')
                ->children()
                    ->scalarNode(self::HOST)->end()
                    ->scalarNode(self::PORT)->end()
                    ->scalarNode(self::PASSWORD)
                        ->defaultValue(null)
                    ->end()
                    ->integerNode(self::CONNECTION_TIMEOUT)
                        ->defaultValue(null)
                    ->end()
                    ->integerNode(self::RESPONSE_TIMEOUT)
                        ->defaultValue(null)
                    ->end()
                ->end()
            ->end();
        return $node;
    }

    /**
     * Define the node for queue defaults
     *
     * @return NodeDefinition
     */
    private function addQueueDefaultsNode()
    {
        $builder = new TreeBuilder();
        $node = $builder->root(self::QUEUE_DEFAULTS);

        $node
            ->children()
                ->scalarNode(self::FAILURE_QUEUE)->end()
                ->integerNode(self::MAX_JOB_PROCESS_TIME)
                    ->defaultValue(self::MAX_JOB_PROCESS_TIME_DEFAULT)
                    ->min(0)
                ->end()
                ->integerNode(self::MAX_JOB_LIFETIME)
                    ->defaultValue(self::MAX_JOB_LIFETIME_DEFAULT)
                    ->min(0)
                ->end()
            ->end();

        return $node;
    }

    /**
     * Define the queues node
     *
     * @return NodeDefinition
     */
    private function addQueuesNode()
    {
        $builder = new TreeBuilder();
        $node = $builder->root(self::QUEUES);

        $node
            ->isRequired()
            ->requiresAtLeastOneElement()
            ->prototype('array')
                ->children()
                    ->append($this->addWorkerNode())
                    ->scalarNode(self::FAILURE_QUEUE)->end()
                    ->integerNode(self::MAX_JOB_PROCESS_TIME)
                        ->min(0)
                    ->end()
                    ->integerNode(self::MAX_JOB_LIFETIME)
                        ->min(0)
                    ->end()
                ->end();

	    return $node;
    }

    /**
     * Define the node for consumer defaults
     *
     * @return NodeDefinition
     */
    private function addConsumerDefaultsNode()
    {
        $builder = new TreeBuilder();
        $node = $builder->root(self::CONSUMER_DEFAULTS);

        $node
            ->children()
                ->integerNode(self::MIN_PROCESSES)
                    ->defaultValue(self::MIN_PROCESSES_DEFAULT)
                    ->min(1)
                ->end()
                ->integerNode(self::MAX_PROCESSES)
                    ->defaultValue(self::MAX_PROCESSES_DEFAULT)
                    ->min(1)
                ->end()
                ->booleanNode(self::AUTOSCALE)
                    ->defaultValue(self::AUTOSCALE_DEFAULT)
                ->end()
                ->integerNode(self::JOB_BATCH)
                    ->defaultValue(self::JOB_BATCH_DEFAULT)
                    ->min(1)
                ->end()
            ->end();

        return $node;
    }

    /**
     * Define the consumers node
     *
     * @return NodeDefinition
     */
    private function addConsumersNode()
    {
        $builder = new TreeBuilder();
        $node = $builder->root(self::CONSUMERS);

        $node
            ->prototype('array')
                ->children()
                    ->arrayNode(self::QUEUES)
                        ->isRequired()
                        ->prototype('scalar')
                        ->end()
                    ->end()
                    ->integerNode(self::MIN_PROCESSES)
                        ->min(1)
                        ->end()
                    ->integerNode(self::MAX_PROCESSES)
                        ->min(1)
                    ->end()
                    ->booleanNode(self::AUTOSCALE)->end()
                    ->integerNode(self::JOB_BATCH)
                        ->min(1)
                    ->end()
                ->end()
            ->end();

        return $node;
    }

    /**
     * Define the worker node
     *
     * @return NodeDefinition
     */
    private function addWorkerNode()
    {
        $builder = new TreeBuilder();
        $node = $builder->root(self::WORKER);

        $node
            ->prototype('variable')
            ->end();

        return $node;
    }


}
