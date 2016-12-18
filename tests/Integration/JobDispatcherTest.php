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

use Disqontrol\DisqontrolApplication as App;
use Disqontrol\Configuration\ConfigDefinition as Config;
use Disqontrol\Dispatcher\Call\Cli\NullProcess;
use Disqontrol\Disqontrol;
use Disqontrol\Event\JobRouteEvent;
use Disqontrol\Job\Job;
use Disqontrol\Router\SimpleRoute;
use Disqontrol\Router\WorkerDirections;
use Disqontrol\Worker\WorkerFactoryCollection;
use Disqontrol\Worker\WorkerType;
use Psr\Log\NullLogger;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;
use Disqontrol\Configuration\ConsoleCommandsCompilerPass;
use Disqontrol\Configuration\FailureStrategiesCompilerPass;
use Symfony\Component\EventDispatcher\DependencyInjection\RegisterListenersPass;
use Symfony\Component\DependencyInjection\Definition;
use Disque\Client;
use Mockery as m;
use Disqontrol\Dispatcher\Call\Cli\ProcessFactory;
use RuntimeException;
use InvalidArgumentException;
use Disqontrol\Event\Events;

/**
 * * @method static UnsupportedWorkerType UNSUPPORTED()
 */
class UnsupportedWorkerType extends WorkerType
{
    const UNSUPPORTED = 'unsupported';
}

class JobDispatcherTest extends \PHPUnit_Framework_TestCase
{
    const JOB_BODY = 'body';
    /**
     * This must be the same as the first queue in the example config
     */
    const JOB_QUEUE = 'registration_email';
    const JOB_ID = 'jobid';
    const WORKER_ADDRESS = 'worker address';

    const PARAMETER_NAME = 'param';
    const PARAMETER_VALUE = 'param_value';

    /**
     * Names of services in the service container
     */
    const SERVICE_DISQUE = 'disque';
    const SERVICE_DISQONTROL = 'disqontrol';
    const SERVICE_CALL_FACTORY = 'call_factory';
    const SERVICE_LOGGER = 'logger';
    const SERVICE_PROCESS_FACTORY = 'process_factory';

    /**
     * The current instance of the service container
     *
     * @var Container
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

    // JobRouterFactory tests
    public function testUnknownWorkerType()
    {
        $configParams = $this->loadConfiguration();
        // Remove the line with the correct worker type
        unset($configParams[Config::QUEUES][self::JOB_QUEUE][Config::WORKER][Config::WORKER_TYPE]);
        // ... and replace it with an unsupported worker type
        $configParams[Config::QUEUES][self::JOB_QUEUE][Config::WORKER]
            [Config::WORKER_TYPE] = UnsupportedWorkerType::UNSUPPORTED;

        $container = $this->createContainer($configParams);

        $this->expectException(InvalidArgumentException::class);
        // Just asking the container for the job dispatcher should throw an exception
        $container->get('job_dispatcher');
    }

    /**
     * Test that worker parameters from the config file are added to the worker
     * call
     *
     * @todo Finish after we support a call with parameters
     *       Right now only CLI call works and it doesn't support extra parameters
     */
    public function testWorkerParameters()
    {
        $configParams = $this->loadConfiguration();
        // Add parameters to the worker
        $configParams[Config::QUEUES][self::JOB_QUEUE][Config::WORKER] = [
            Config::WORKER_TYPE => WorkerType::HTTP,
            Config::HTTP_WORKER_ADDRESS => self::WORKER_ADDRESS,
            self::PARAMETER_NAME => self::PARAMETER_VALUE
        ];

        $process = m::mock(NullProcess::class)
            ->makePartial()
            ->shouldReceive('isSuccessful')
            ->andReturn(true)
            ->getMock();

        // Test variable that we will fill from inside the mock and assert later
        $command = '';

        $processFactory = m::mock(ProcessFactory::class)
            ->shouldReceive('create')
            ->with(m::on(function ($cmd) use (&$command) {
                $command = $cmd;
                return true;
            }), anything())
            ->andReturn($process)
            ->getMock();

        $disque = m::mock(Client::class)
            ->shouldReceive('ackJob')
            ->with(self::JOB_ID)
            ->getMock();

        $container = $this->createContainer($configParams, $disque, $processFactory);

        $queue = $this->getQueueName();
        $job = new Job('body', $queue, self::JOB_ID);

        $dispatcher = $container->get('job_dispatcher');
        $dispatcher->dispatch([$job]);

        // Asserts go here
        // $this->assertContains(self::PARAMETER_NAME, $command);
        // $this->assertContains(self::PARAMETER_VALUE, $command);
    }

    // JobRouter tests
    public function testRouteFromEvent()
    {
        // Test variables that we will fill from inside the mock and assert later
        $command = '';
        $timeout = 0;

        $process = m::mock(NullProcess::class)
            ->makePartial()
            ->shouldReceive('isSuccessful')
                ->andReturn(true)
            ->getMock();
        
        $processFactory = m::mock(ProcessFactory::class)
            ->shouldReceive('create')
                ->with(m::on(function ($cmd) use (&$command) {
                    $command = $cmd;
                    return true;
                }), m::on(function ($tmt) use (&$timeout) {
                    $timeout = $tmt;
                    return true;
                }))
                ->andReturn($process)
            ->getMock();

        $disque = m::mock(Client::class)
            ->shouldReceive('ackJob')
                ->with(self::JOB_ID)
            ->getMock();

        $container = $this->createContainer([], $disque, $processFactory);

        $listener = function (JobRouteEvent $event) {
            $directions = new WorkerDirections(WorkerType::COMMAND(), self::WORKER_ADDRESS);
            $event->setWorkerDirections($directions);
            $event->stopPropagation();
        };

        $eventDispatcher = $container->get('event_dispatcher');
        $highPriority = 9999;
        $eventDispatcher->addListener(Events::JOB_ROUTE, $listener, $highPriority);

        $queue = $this->getQueueName();
        $job = new Job('body', $queue, self::JOB_ID);

        $dispatcher = $container->get('job_dispatcher');
        $dispatcher->dispatch([$job]);

        // The actual command starts with the worker address and adds extra
        // arguments to it. Those are not important here.
        $this->assertStringStartsWith(self::WORKER_ADDRESS, $command);

        $expectedTimeout = $container->get('configuration')->getJobProcessTimeout($queue);
        $this->assertSame($expectedTimeout, $timeout);
    }

    /**
     * There is no route defined for the job
     */
    public function testWorkerNotFound()
    {
        $logger = m::mock(NullLogger::class)
            ->shouldReceive('error')
            ->once()
            ->getMock();

        $container = $this->createContainer([], null, null, $logger);

        $unknownQueue = 'unknown queue foobar x';
        $job = new Job('body', $unknownQueue);

        $dispatcher = $container->get('job_dispatcher');
        $dispatcher->dispatch([$job]);
    }

    /**
     * CallFactory throws an exception because it doesn't support the worker type
     */
    public function testUnsupportedWorkerType()
    {
        $logger = m::mock(NullLogger::class)
            ->shouldReceive('error')
            ->once()
            ->getMock();

        $container = $this->createContainer([], null, null, $logger);

        $queue = 'foobar queue';

        $badDirections = new WorkerDirections(UnsupportedWorkerType::UNSUPPORTED(), 'address');
        $route = new SimpleRoute([$queue], $badDirections);
        $jobRouter = $container->get('job_router');
        $jobRouter->addRoute($route);

        $job = new Job('body', $queue);

        $dispatcher = $container->get('job_dispatcher');
        $dispatcher->dispatch([$job]);
    }

    // CliCall / ProcessFactory tests
    /**
     * Test that the whole process fails with an uncaught exception if proc_open
     * is not installed.
     */
    public function testProcessFactoryThrowsException()
    {
        $processFactory = m::mock(ProcessFactory::class)
            ->shouldReceive('create')
                ->andThrow(new RuntimeException('proc_open is not installed'))
            ->getMock();

        $container = $this->createContainer([], null, $processFactory);

        $queue = $this->getQueueName();
        $job = new Job('body', $queue);

        $dispatcher = $container->get('job_dispatcher');

        $this->expectException(RuntimeException::class);
        $dispatcher->dispatch([$job]);
    }

    // Dispatcher tests
    /**
     * Test that Calls are called properly:
     * - started ->call() = Process::start()
     * - checked for timeout ->checkTimeout() = Process::checkTimeout()
     * - checked if running ->isRunning() = Process::isRunning()
     * - checked for result ->wasSuccessful() = Process::isSuccessful at least once
     */
    public function testCallsCalledProperly()
    {
        $disque = m::mock(Client::class)
            ->shouldReceive('ackJob')
            ->with(self::JOB_ID)
            ->once()
            ->getMock();

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
            ->andReturn($process)
            ->getMock();

        $container = $this->createContainer([], $disque, $processFactory);

        $dispatcher = $container->get('job_dispatcher');

        $queue = $this->getQueueName();
        $job = new Job('body', $queue, self::JOB_ID);

        $dispatcher->dispatch([$job]);
    }

    /**
     * This is the base case, the happy path if everything works
     */
    public function testAckSuccessfulJob()
    {
        $disque = m::mock(Client::class)
            ->shouldReceive('ackJob')
                ->with(self::JOB_ID)
                ->once()
            ->getMock();

        $process = $this->mockSuccessfulProcess();
        $processFactory = m::mock(ProcessFactory::class)
            ->shouldReceive('create')
                ->andReturn($process)
            ->getMock();

        $container = $this->createContainer([], $disque, $processFactory);

        $dispatcher = $container->get('job_dispatcher');

        $queue = $this->getQueueName();
        $job = new Job('body', $queue, self::JOB_ID);

        $dispatcher->dispatch([$job]);
    }

    /**
     * This is the most common failure case - the job fails and is NACKed
     *
     * The queue registration-email uses the retry-immediately failure strategy.
     * We expect the whole dispatch to call NACK at the end.
     */
    public function testProperFailureStrategy()
    {
        $disque = m::mock(Client::class)
            ->shouldReceive('nack')
                ->with(self::JOB_ID)
                ->once()
            ->getMock();

        $processFactory = m::mock(ProcessFactory::class)
            ->shouldReceive('create')
                ->andReturn(new NullProcess())
            ->getMock();

        $container = $this->createContainer([], $disque, $processFactory);

        $dispatcher = $container->get('job_dispatcher');

        $queue = $this->getQueueName();
        $job = new Job('body', $queue, self::JOB_ID);
        // The job must have a long enough lifetime, otherwise it's moved
        // to a failure queue instead of being NACKed.
        $job->setJobLifetime(60);

        $dispatcher->dispatch([$job]);
    }

    /**
     * Test that a job that is out of retries is moved to its failure queue
     */
    public function testJobOutOfRetries()
    {
        // Note: This must be equal to disqontrol.yml.dist
        $failureQueue = 'unsent-registration-emails';

        $disque = m::mock(Client::class)
            ->shouldReceive('ackJob')
                ->with(self::JOB_ID)
                ->once()
            ->shouldReceive('addJob')
                ->with($failureQueue, anything(), anything())
                ->once()
            ->getMock();

        $processFactory = m::mock(ProcessFactory::class)
            ->shouldReceive('create')
                ->andReturn(new NullProcess())
            ->getMock();

        $container = $this->createContainer([], $disque, $processFactory);

        $dispatcher = $container->get('job_dispatcher');

        $queue = $this->getQueueName();
        $job = new Job('body', $queue, self::JOB_ID);
        $job->setJobLifetime(60);

        $config = $container->get('configuration');
        $maxRetries = $config->getMaxRetries($queue);
        $job->setPreviousRetryCount($maxRetries + 1);

        $dispatcher->dispatch([$job]);
    }

    /**
     * Test that a job that is out of time is moved to its failure queue
     */
    public function testJobOutOfTime()
    {
        // Note: This must be equal to disqontrol.yml.dist
        $failureQueue = 'unsent-registration-emails';

        $disque = m::mock(Client::class)
            ->shouldReceive('ackJob')
                ->with(self::JOB_ID)
                ->once()
            ->shouldReceive('addJob')
                ->with($failureQueue, anything(), anything())
                ->once()
            ->getMock();

        $processFactory = m::mock(ProcessFactory::class)
            ->shouldReceive('create')
                ->andReturn(new NullProcess())
            ->getMock();

        $container = $this->createContainer([], $disque, $processFactory);

        $dispatcher = $container->get('job_dispatcher');

        $queue = $this->getQueueName();
        $job = new Job('body', $queue, self::JOB_ID);
        $job->setCreationTime(strtotime('1970-01-01'));
        $job->setJobLifetime(60);

        $dispatcher->dispatch([$job]);

    }

    /**
     * @param array $configParams
     * @param $disque
     * @param $processFactory
     * @param $logger
     *
     * @return ContainerInterface
     */
    private function createContainer(
        array $configParams = array(),
        $disque = null,
        $processFactory = null,
        $logger = null
    ) {
        $container = new ContainerBuilder();

        $configDir = App::getRootDir() . Disqontrol::APP_CONFIG_DIR_PATH;
        $loader = new YamlFileLoader($container, new FileLocator($configDir));
        $loader->load(Disqontrol::SERVICES_FILE);

        $container->addCompilerPass(new ConsoleCommandsCompilerPass());
        $container->addCompilerPass(new FailureStrategiesCompilerPass());

        $container->addCompilerPass(
            new RegisterListenersPass(
                Disqontrol::EVENT_DISPATCHER_SERVICE,
                Disqontrol::EVENT_LISTENER_TAG,
                Disqontrol::EVENT_SUBSCRIBER_TAG
            )
        );

        if (empty($configParams)) {
            $configParams = $this->loadConfiguration();
        }
        $configFactory = $container->get('configuration_factory');
        $configFactory->setConfigArray($configParams);

        // Set mocked services
        if (empty($disque)) {
            $disque = m::mock(Client::class);
        }
        $container->set(self::SERVICE_DISQUE, $disque);

        if (empty($processFactory)) {
            $processFactory = m::mock(ProcessFactory::class);
        }
        $container->set(self::SERVICE_PROCESS_FACTORY, $processFactory);

        if (empty($logger)) {
            $logger = new NullLogger();
        }
        $container->set(self::SERVICE_LOGGER, $logger);

        $workerFactoryCollection = new WorkerFactoryCollection();
        $disqontrol = m::mock(Disqontrol::class)
            ->shouldReceive('getWorkerFactoryCollection')
            ->andReturn($workerFactoryCollection)
            ->getMock();
        $container->set(self::SERVICE_DISQONTROL, $disqontrol);

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

    /**
     * @return NullProcess (mocked)
     */
    private function mockSuccessfulProcess()
    {
        $process = m::mock(NullProcess::class)
            ->makePartial()
            ->shouldReceive('isSuccessful')
                ->andReturn(true)
            ->getMock();

        return $process;
    }
}
