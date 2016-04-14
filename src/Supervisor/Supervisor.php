<?php
/*
* This file is part of the Disqontrol package.
*
* (c) Webtrh s.r.o. <info@webtrh.cz>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Disqontrol\Supervisor;

use Disqontrol\Configuration\Configuration;
use Disqontrol\Consumer\Process\ConsumerProcessGroup;
use Disqontrol\Consumer\Process\ProcessGroupFactory;
use Disqontrol\Exception\ConfigurationException;
use Disqontrol\Logger\MessageFormatter;
use Disqontrol\ProcessControl\ProcessControl;
use Psr\Log\LoggerInterface;

/**
 * Start and manage consumer processes
 *
 * - Read all queues from the configuration
 * - Assign a consumer to each queue, either from the Consumer section or a default one
 * - Start all required consumers
 * - Check periodically if
 *   - All consumers run
 *   - The queues are not clogging, in that case start more consumers
 *
 * - In case Supervisor must restart or stop, stop all consumers too
 *
 * @author Martin Schlemmer
 */
class Supervisor
{
    /**
     * How long a pause before checking on consumers?
     *
     * 5000000 microseconds = 5 seconds
     */
    const LOOP_PAUSE = 5000000;

    /**
     * @var Configuration
     */
    private $config;

    /**
     * @var ProcessControl
     */
    private $processControl;

    /**
     * @var ProcessGroupFactory
     */
    private $processGroupFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ConsumerProcessGroup[]
     */
    private $consumerProcesses;

    /**
     * @param Configuration       $config
     * @param ProcessControl      $processControl
     * @param ProcessGroupFactory $processGroupFactory
     * @param LoggerInterface     $logger
     */
    public function __construct(
        Configuration $config,
        ProcessControl $processControl,
        ProcessGroupFactory $processGroupFactory,
        LoggerInterface $logger
    ) {
        $this->config = $config;
        $this->processControl = $processControl;
        $this->processGroupFactory = $processGroupFactory;
        $this->logger = $logger;
    }

    /**
     * The main Supervisor loop
     */
    public function run()
    {
        $this->init();

        while (true) {
            $this->processControl->checkForSignals();
            $this->checkOnConsumers();

            usleep(self::LOOP_PAUSE);
        }
    }
    
    /**
     * A callback for the SIGTERM interrupt
     *
     * Stop all consumer processes. This has two steps:
     * - First we send SIGTERM to the processes of all process groups
     * - Then we enforce the termination of each process, one by one
     */
    public function terminate()
    {
        $this->logger->debug(MessageFormatter::receivedTerminateSignal('Supervisor'));

        foreach ($this->consumerProcesses as $processGroup) {
            $processGroup->sendStopSignal();
        }

        foreach ($this->consumerProcesses as $processGroup) {
            $processGroup->stopCompletely();
        }

        die();
    }
    
    /**
     * Start all consumer processes
     *
     * @throws ConfigurationException
     */
    private function init()
    {
        $this->registerSignalHandlers();
        $this->checkUndefinedQueues();

        $this->consumerProcesses = $this->createConfiguredConsumers();

        $defaultConsumer = $this->createDefaultConsumer();
        if (isset($defaultConsumer)) {
            $this->consumerProcesses[] = $defaultConsumer;
        }

        if (empty($this->consumerProcesses)) {
            die('Supervisor has nothing to do');
        }

    }
    
    /**
     * Register signal handlers that listen for process signals
     */
    private function registerSignalHandlers()
    {
        $this->processControl->registerSignalHandler(
            [SIGINT, SIGTERM],
            [$this, 'terminate']
        );
    }
    
    /**
     * Check that all consumers are OK and behaving
     *
     * - Start consumers so that at least min_processes of them are running
     *   in each group
     * - If some have died, replace them with new ones
     * - If the demand is high, start more of them, up to max_processes
     */
    private function checkOnConsumers()
    {
        foreach ($this->consumerProcesses as $i => $processGroup) {
            $processGroup->checkOnConsumers();
        }
    }

    /**
     * Check if there are consumers with undefined queues
     *
     * Check if the user has configured a consumer (in the "consumer" section)
     * for a queue that is not defined in the configuration (in the "queues"
     * section). It would be a problem to start such a consumer, because after
     * reserving a job from Disque, it wouldn't know what to do with it.
     * Jobs from such a misconfigured queue could be under certain circumstances
     * lost completely.
     *
     * If there are consumers with undefined queues, we want to fail quickly
     * and loudly before the Supervisor starts.
     *
     * @throws ConfigurationException
     */
    private function checkUndefinedQueues()
    {
        if ($this->config->hasUndefinedQueues()) {
            throw new ConfigurationException(
                MessageFormatter::undefinedQueuesInConsumerConfig(
                    $this->config->getUndefinedQueues()
                )
            );
        }
    }

    /**
     * Create consumers explicitly defined in the configuration
     *
     * @return ConsumerProcessGroup[]
     */
    private function createConfiguredConsumers()
    {
        $consumers = array();

        foreach ($this->config->getConsumersConfig() as $i => $consumerConfig) {
            $consumers[] = $this->createConsumerGroupFromConfig($i);
        }

        return $consumers;
    }

    /**
     * Create a consumer group for queues without explicit consumer configuration
     *
     * Create one consumer process group for all queues that are defined
     * in the "queues" config section but don't have an entry in the "consumers"
     * section.
     *
     * The assumption is that if the user didn't bother to create a consumer
     * definition, the queues are not very busy and we can roll all of them
     * into one consumer group.
     *
     * @return ConsumerProcessGroup|null
     */
    private function createDefaultConsumer()
    {
        $queuesWithDefaultConsumer = $this->config->getQueuesWithDefaultConsumer();

        if (empty($queuesWithDefaultConsumer)) {
            return null;
        }

        $consumerGroup = $this->processGroupFactory->create(
            $queuesWithDefaultConsumer,
            $this->config->getDefaultConsumerMinProcesses(),
            $this->config->getDefaultConsumerMaxProcesses(),
            $this->config->getDefaultConsumerJobBatch()
        );

        $this->logger->debug(
            MessageFormatter::supervisorSpawnedDefaultProcessGroup($queuesWithDefaultConsumer)
        );

        return $consumerGroup;
    }
    
    /**
     * Create a ConsumerProcessGroup for a consumer config entry nr. X
     *
     * Consumers in the configuration have no user-defined key, so we identify
     * them just by their zero-based position in the config file.
     *
     * @param int $consumerIndex
     *
     * @return ConsumerProcessGroup
     */
    private function createConsumerGroupFromConfig($consumerIndex)
    {
        $queues = $this->config->getConsumerQueues($consumerIndex);

        $processGroup = $this->processGroupFactory->create(
            $queues,
            $this->config->getConsumerMinProcesses($consumerIndex),
            $this->config->getConsumerMaxProcesses($consumerIndex),
            $this->config->getConsumerJobBatch($consumerIndex)
        );

        $this->logger->debug(
            MessageFormatter::supervisorSpawnedProcessGroup($queues)
        );

        return $processGroup;

    }
}
