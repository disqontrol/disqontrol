<?php
/*
* This file is part of the Disqontrol package.
*
* (c) Mediaplus.cz s.r.o. <info@mediaplus.cz>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Disqontrol\Test\Integration;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Disqontrol\Disqontrol;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Disqontrol\DisqontrolApplication as App;
use Disqontrol\Configuration\ConfigDefinition as Config;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Config\Definition\Processor;
use Disqontrol\Worker\WorkerFactoryCollection;
use Psr\Log\NullLogger;
use Disque\Client;
use Disqontrol\Dispatcher\Call\Cli\ProcessFactory;
use Mockery as m;
use Disqontrol\Job\Job;
use Disqontrol\Dispatcher\Call\Cli\NullProcess;

/**
 * Test the synchronous producer skips the queue
 */
class SynchronousProducerTest extends \PHPUnit_Framework_TestCase
{
    const SERVICE_SYNCHRONOUS_PRODUCER = 'synchronous_producer';

    /**
     * The current instance of the service container
     *
     * @var ContainerInterface
     */
    private $container;

    public function setUp()
    {
        // Do not reuse the container between tests
        $this->container = null;
    }
    
    public function tearDown()
    {
        m::close();
    }

    public function testJobDispatchedImmediately()
    {
        // Declare this test variable now. It will be populated inside the mock
        // and asserted later
        $command = '';

        $process = m::mock(NullProcess::class)
            ->makePartial()
            ->shouldReceive('start')
            ->once()
            ->shouldReceive('checkTimeout')
            ->once()
            ->shouldReceive('isRunning')
            ->atLeast()->times(1)
            ->shouldReceive('isSuccessful')
            ->atLeast()->times(1)
            ->andReturn(true)
            ->getMock();
        $processFactory = m::mock(ProcessFactory::class)
            ->shouldReceive('create')
            ->with(
                m::on(
                    function ($cmd) use (&$command) {
                        $command = $cmd;

                        return true;
                    }
                ),
                m::any()
            )
            ->andReturn($process)
            ->getMock();

        $container = $this->createContainer($processFactory);
        $synchronousProducer = $container->get(self::SERVICE_SYNCHRONOUS_PRODUCER);

        $queue = $this->getQueueName();
        $job = new Job('body', $queue);

        $result = $synchronousProducer->add($job);

        $this->assertTrue($result);

        $config = $container->get('configuration');
        $workerCommand = $config->getWorkerDirections($queue);

        $this->assertStringStartsWith($workerCommand, $command);
    }

    public function testFailedJobThrownAway()
    {
        $process = m::mock(NullProcess::class)
            ->makePartial()
            ->shouldReceive('start')
            ->once()
            ->shouldReceive('checkTimeout')
            ->once()
            ->shouldReceive('isRunning')
            ->atLeast()->times(1)
            ->shouldReceive('isSuccessful')
            ->atLeast()->times(1)
            ->andReturn(false)
            ->getMock();
        $processFactory = m::mock(ProcessFactory::class)
            ->shouldReceive('create')
            ->andReturn($process)
            ->getMock();

        $logger = m::mock(NullLogger::class)
            ->shouldReceive('error')
            ->once()
            ->getMock();

        $container = $this->createContainer($processFactory, $logger);
        $synchronousProducer = $container->get(self::SERVICE_SYNCHRONOUS_PRODUCER);

        $queue = $this->getQueueName();
        $job = new Job('body', $queue);

        $result = $synchronousProducer->add($job);

        $this->assertFalse($result);

    }

    /**
     * @param $processFactory
     * @param $logger
     *
     * @return ContainerInterface
     */
    private function createContainer(
        $processFactory = null,
        $logger = null
    ) {
        $container = new ContainerBuilder();

        $configDir = App::getRootDir() . Disqontrol::APP_CONFIG_DIR_PATH;
        $loader = new YamlFileLoader($container, new FileLocator($configDir));
        $loader->load(Disqontrol::SERVICES_FILE);

        if (empty($configParams)) {
            $configParams = $this->loadConfiguration();
        }
        $configFactory = $container->get('configuration_factory');
        $configFactory->setConfigArray($configParams);

        // Set mocked services

        // Disque shouldn't be called at all
        $disque = m::mock(Client::class);
        $disque->shouldNotReceive('ackJob');
        $disque->shouldNotReceive('nack');
        $container->set(JobDispatcherTest::SERVICE_DISQUE, $disque);

        if (empty($processFactory)) {
            $processFactory = m::mock(ProcessFactory::class);
        }
        $container->set(JobDispatcherTest::SERVICE_PROCESS_FACTORY, $processFactory);

        if (empty($logger)) {
            $logger = new NullLogger();
        }
        $container->set(JobDispatcherTest::SERVICE_LOGGER, $logger);

        $workerFactoryCollection = new WorkerFactoryCollection();
        $disqontrol = m::mock(Disqontrol::class)
            ->shouldReceive('getWorkerFactoryCollection')
            ->andReturn($workerFactoryCollection)
            ->getMock();
        $container->set(JobDispatcherTest::SERVICE_DISQONTROL, $disqontrol);

        $container->compile();
        $this->container = $container;

        return $container;
    }

    /**
     * Load configuration from the example config file
     *
     * @return array Processed config parameters
     */
    private function loadConfiguration()
    {
        $configArray = Yaml::parse(file_get_contents(ConfigurationTest::CONFIG_FILE));

        $processor = new Processor();
        $processedParams = $processor->processConfiguration(
            new Config(),
            [$configArray]
        );

        $configParams = $processedParams[Config::DISQONTROL];

        return $configParams;
    }

    /**
     * Get the name of one of the queues defined in the example configuration
     *
     * There are four right now, each configured a bit differently.
     *
     * @param int $queueIndex 0 - 3
     *
     * @return string Queue name
     */
    private function getQueueName($queueIndex = 0)
    {
        $config = $this->container->get('configuration');
        $queuesConfig = $config->getQueuesConfig();
        $i = 0;
        foreach ($queuesConfig as $queueName => $queueConfig) {
            if ($i === $queueIndex) {
                return $queueName;
            }
        }
    }

}
