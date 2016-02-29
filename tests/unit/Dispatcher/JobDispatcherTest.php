<?php
/*
* This file is part of the Disqontrol package.
*
* (c) Webtrh s.r.o. <info@webtrh.cz>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Disqontrol\Dispatcher;

use Disqontrol\Dispatcher\Call\AbstractCall;
use Disqontrol\Dispatcher\Failure\FailureStrategyCollection;
use Disqontrol\Job\Job;
use Disqontrol\Job\JobInterface;
use Disqontrol\Logger\JobLogger;
use Disqontrol\Worker\WorkerType;
use Mockery as m;
use Psr\Log\NullLogger;
use Disqontrol\Dispatcher\Failure\FailureStrategyInterface;
use Disqontrol\Dispatcher\Call\CallInterface;
use Disqontrol\Router\JobRouterInterface;
use Psr\Log\LoggerInterface;
use Disqontrol\Exception\JobRouterException;
use Exception;
use Disque\Client;

class JobDispatcherTest extends \PHPUnit_Framework_TestCase
{
    const JOB_BODY_1 = 'b1';
    const JOB_BODY_2 = 'b2';
    const QUEUE = 'queue';

    /**
     * @var JobInterface[] The job processed at the end of the dispatch
     *
     * Either by a failure strategy in JobDispatcher::handleFailure(),
     * or logged in handleSuccess()
     *
     * The variable is cleared before each test
     */
    private $processedJobs = array();
    private $successfulJobs = array();
    private $failedJobs = array();

    public function setUp()
    {
        $this->processedJobs = array();
        $this->successfulJobs = array();
        $this->failedJobs = array();
    }

    public function tearDown()
    {
        m::close();
    }

    public function testInstance()
    {
        $call = $this->mockCall();
        $router = $this->mockRouter([$call]);
        $failures = $this->mockFailureStrategyCollection();
        $logger = new NullLogger();
        $dispatcher = $this->createDispatcher($router, $failures, $logger);
        $this->assertInstanceOf(JobDispatcher::class, $dispatcher);
    }

    public function testSkipUnroutableJobs()
    {
        $unroutableJob = new Job(self::JOB_BODY_1, self::QUEUE);
        $routableJob = new Job(self::JOB_BODY_2, self::QUEUE);
        $call = $this->mockCall($routableJob);
        $calls = [
            new JobRouterException(),
            $call
        ];
        $router = $this->mockRouter($calls);
        $failures = $this->mockFailureStrategyCollection();
        $logger = $this->mockLogger();

        $dispatcher = $this->createDispatcher($router, $failures, $logger);
        $dispatcher->dispatch([$unroutableJob, $routableJob]);

        $this->assertCount(1, $this->successfulJobs);
        $this->assertSame($routableJob, $this->successfulJobs[0]);
    }

    /**
     * Check that non-blocking jobs are called first, even if they are submitted
     * later in the batch
     */
    public function testOrderNonblockingBeforeBlockingCalls()
    {
        $earlierJob = new Job(self::JOB_BODY_1, self::QUEUE);
        $success = true;
        $type = WorkerType::PHP();
        $blockingCall = $this->mockCall($earlierJob, $success, $type);

        $laterJob = new Job(self::JOB_BODY_2, self::QUEUE);
        $type = WorkerType::CLI();
        $nonblockingCall = $this->mockCall($laterJob, $success, $type);

        $router = $this->mockRouter([$blockingCall, $nonblockingCall]);
        $failures = $this->mockFailureStrategyCollection();
        $logger = $this->mockLogger();

        $dispatcher = $this->createDispatcher($router, $failures, $logger);
        $dispatcher->dispatch([$earlierJob, $laterJob]);

        $this->assertSame($laterJob, $this->successfulJobs[0]);
        $this->assertSame($earlierJob, $this->successfulJobs[1]);
    }

    /**
     * Check that a successful job is handled by handleSuccess()
     */
    public function testHandleSuccessfulCall()
    {
        $job = new Job('body', 'queue');
        $call = $this->mockCall($job);
        $router = $this->mockRouter([$call]);
        $failures = $this->mockFailureStrategyCollection();
        $logger = $this->mockLogger();

        $dispatcher = $this->createDispatcher($router, $failures, $logger);
        $dispatcher->dispatch([$job]);

        $this->assertSame($job, $this->successfulJobs[0]);
    }

    /**
     * Check that a failed job is handled by a failure strategy
     */
    public function testHandleFailedCall()
    {
        $job = new Job('body', 'queue');
        $success = false;
        $call = $this->mockCall($job, $success);
        $router = $this->mockRouter([$call]);
        $failures = $this->mockFailureStrategyCollection();
        $logger = new NullLogger();

        $dispatcher = $this->createDispatcher($router, $failures, $logger);
        $dispatcher->dispatch([$job]);

        $this->assertSame($job, $this->failedJobs[0]);

    }

    /**
     * Check a combination of the above tests
     * - Unroutable jobs
     * - A mix of blocking and nonblocking jobs
     * - A mix of successful and failed calls
     */
    public function testAllOfTheAbove()
    {
        // Generate randomly unroutable jobs, blocking or non-blocking,
        // and failing calls
        $jobCount = rand(10, 20);
        for ($i = 0; $i < $jobCount; $i++) {
            $job = new Job('body' . $i, 'queue', $i);
            $jobs[$i] = $job;
            $call = $this->mockRandomCallOrException($job);
            $calls[$i] = $call;
        }

        $router = $this->mockRouter($calls);
        $failures = $this->mockFailureStrategyCollection();
        $logger = $this->mockLogger();

        $dispatcher = $this->createDispatcher($router, $failures, $logger);
        $dispatcher->dispatch($jobs);

        $this->assertSame(
            $jobCount,
            count($this->successfulJobs) + count($this->failedJobs)
        );

        // Check that jobs have been handled according to their success or failure
        foreach($jobs as $i => $job) {
            $call = $calls[$i];
            if ($call instanceof Exception or ! $call->wasSuccessful()) {
                $this->assertContains($job, $this->failedJobs);
            } else {
                $this->assertContains($job, $this->successfulJobs);
            }
        }

        // The second half of this method tests whether the calls were ordered
        // according to them being blocking/non-blocking. It arguably tests
        // the implementation of JobDispatcher, not its logic, but I'll leave
        // it here for now. When it breaks, feel free to delete it.
        if (empty($this->successfulJobs)) {
            // The next section only concerns successful jobs. If we don't have
            // any, return early
            return;
        }

        // Find the first successful job coming from a blocking call
        $found = false;
        foreach ($this->successfulJobs as $j => $successJob) {
            $i = $successJob->getId();
            if ($calls[$i]->isBlocking()) {
                $found = true;
                break;
            }
        }

        if ($found === false) {
            // No blocking calls found among the successful jobs
            // Failed jobs are not ordered by blocking, nothing else to test
            return;
        }

        // $j is the index of the first blocking successful job. All successful
        // jobs before this one must be non-blocking, all following successful
        // jobs must be blocking. This assumption works, because all test calls
        // are completed immediately (isRunning() returns false). This helps us
        // check the order of calls in the test, but the ordering is not
        // guaranteed in production, with real calls.

        $nonblockingJobs = array();
        if (0 < $j) {
            $nonblockingJobs = array_slice($this->successfulJobs, 0, $j);
        }
        $blockingJobs = array_slice($this->successfulJobs, $j);

        foreach ($nonblockingJobs as $nonblockingJob) {
            // We stored the job/call index in the job ID at the beginning
            $i = $nonblockingJob->getId();
            $this->assertFalse($calls[$i]->isBlocking());
        }

        foreach ($blockingJobs as $blockingJob) {
            $i = $blockingJob->getId();
            $this->assertTrue($calls[$i]->isBlocking());
        }
    }

    /**
     * Mock a JobRouter
     *
     * It will return calls and throw exceptions in the order they are
     * in the array
     *
     * @param CallInterface[] $calls Calls the router will return in order
     *                               If the call is an exception, it will be thrown
     *
     * @return JobRouterInterface (mocked)
     */
    private function mockRouter(array $calls)
    {
        $callIndex = 0;
        $router = m::mock(JobRouterInterface::class)
            ->shouldReceive('getCall')
                ->andReturnUsing(function ($job) use ($calls, &$callIndex) {
                    $call = $calls[$callIndex];
                    $callIndex++;

                    if ($call instanceof Exception) {
                        throw $call;
                    }

                    return $call;
                })
            ->getMock();

        return $router;
    }

    /**
     * Mock a failure strategy collection
     *
     * @return FailureStrategyCollection (mocked)
     */
    private function mockFailureStrategyCollection()
    {
        $strategy = m::mock(FailureStrategyInterface::class)
            ->shouldReceive('handleFailure')
            ->with(m::on(function($call) {
                $job = $call->getJob();
                $this->failedJobs[] = $job;
                $this->processedJobs[] = $job;
                return true;
            }))
            ->mock();

        $collection = m::mock(FailureStrategyCollection::class)
            ->shouldReceive('getFailureStrategy')
            ->with(anything())
            ->andReturn($strategy)
            ->mock();

        return $collection;
    }

    /**
     * Mock a logger for a successful job handling
     *
     * @return LoggerInterface (mocked)
     */
    private function mockLogger()
    {
        $logger = m::mock(LoggerInterface::class)
            ->shouldReceive('info')
            ->with(anything(), m::on(function($context) {
                $job = $context[JobLogger::JOB_INDEX];
                $this->successfulJobs[] = $job;
                $this->processedJobs[] = $job;
                return true;
            }))
            ->shouldReceive('error')
            ->with(anything(), m::on(function($context) {
                $job = $context[JobLogger::JOB_INDEX];
                $this->failedJobs[] = $job;
                $this->processedJobs[] = $job;
                return true;
            }))
            ->getMock();

        return $logger;
    }

    /**
     * Create a new instance of JobDispatcher
     *
     * @param $router
     * @param $failures
     * @param $logger
     *
     * @return JobDispatcherInterface
     */
    private function createDispatcher($router, $failures, $logger)
    {
        $disque = m::mock(Client::class)
            ->shouldReceive('ackJob')
            ->getMock();

        return new JobDispatcher($router, $disque, $failures, $logger);
    }

    /**
     * Return a random exception or a mocked call
     *
     * @param JobInterface $job
     *
     * @return CallInterface|JobRouterException
     */
    private function mockRandomCallOrException(JobInterface $job)
    {
        $successThrow = rand(1, 8);
        if ($successThrow <= 1) {
            return new JobRouterException();
        }
        $success = ($successThrow < 6) ? true : false;
        $types = [
            WorkerType::CLI(),
            WorkerType::PHP(),
            WorkerType::HTTP(),
            WorkerType::PHP_CLI()
        ];
        shuffle($types);
        $type = $types[0];

        return $this->mockCall($job, $success, $type);
    }
    
    
    /**
     * Mock a Call
     *
     * @param JobInterface $job
     * @param bool         $success
     * @param mixed        $type
     *
     * @return CallInterface (mocked)
     */
    private function mockCall(JobInterface $job = null, $success = true, $type = null)
    {
        $call = m::mock(AbstractCall::class)
            ->shouldReceive('getType')
                ->andReturn($type)
            ->shouldReceive('call')
            ->shouldReceive('checkTimeout')
            ->shouldReceive('isRunning')
                ->andReturn(false)
            ->shouldReceive('wasSuccessful')
                ->andReturn($success)
            ->shouldReceive('getJob')
                ->andReturn($job)
            ->shouldReceive('isBlocking')
                ->passthru()
            ->mock();
        
        return $call;
    }
}
