<?php
/*
* This file is part of the Disqontrol package.
*
* (c) Webtrh s.r.o. <info@webtrh.cz>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/
namespace Disqontrol\Logger;

use Disqontrol\Event\Events;
use Disqontrol\Event\LogJobDetailsEvent;
use Disqontrol\Job\Job;
use Disqontrol\Job\Serializer\JsonSerializer;
use Monolog\Logger;
use Psr\Log\NullLogger;
use Psr\Log\LoggerInterface;
use Mockery as m;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class JobLoggerTest extends \PHPUnit_Framework_TestCase
{
    const JOB_BODY = 'foo body bar';
    const JOB_QUEUE = 'job queue';
    const JOB_ID = 'jobid';
    const MESSAGE = 'message';

    public function tearDown()
    {
        m::close();
    }

    public function testInstance()
    {
        $logger = $this->createJobLogger();
        $this->assertInstanceOf(JobLogger::class, $logger);
    }

    /**
     * Test that all methods in the JobLogger call the appropriate method
     * in the Monolog\Logger
     */
    public function testCorrectCalls()
    {
        $calls = ['emergency', 'alert', 'critical', 'error', 'warning',
            'notice', 'info', 'debug'];

        foreach ($calls as $call) {
            // The $call() should be called in Monolog\Logger...
            $monolog = $this->createMonologLogger($call);
            $logger = $this->createJobLogger($monolog);
            // ... when I call $call() in the JobLogger
            $logger->$call('');
        }

        // The log() method has a different signature, test it here
        $monolog = $this->createMonologLogger('log');
        $logger = $this->createJobLogger($monolog);
        $logger->log(Logger::ALERT, '');
    }

    public function testLogExtraDetails()
    {
        $job = new Job(self::JOB_BODY, self::JOB_QUEUE, self::JOB_ID);

        $eventDispatcher = m::mock(EventDispatcher::class)
            ->shouldReceive('dispatch')
            // Check that the dispatched event contains the job, a proper
            // error level and the original message
            ->with(
                Events::LOG_JOB_DETAILS,
                m::on(
                    function ($event) use ($job) {
                        return ($event instanceof LogJobDetailsEvent and $event->getJob() === $job
                            and $event->getLevel() === Logger::ERROR
                            and $event->getMessage() === self::MESSAGE);
                    }
                )
            )
            ->once()
            ->getMock();

        $context[JobLogger::JOB_INDEX] = $job;

        $monolog = m::mock(NullLogger::class)
            ->shouldReceive('debug')
            // Check that the message sent to debug() contains both the job ID
            // and the job body
            ->with(
                m::on(
                    function ($message) {
                        return (strpos($message, self::JOB_BODY) !== false
                            and strpos($message, self::JOB_ID) !== false);
                    }
                ),
                $context
            )
            ->once()
            ->andReturn(true)
            ->shouldReceive('error')
            ->getMock();

        $logger = $this->createJobLogger($monolog, $eventDispatcher);
        $logger->error(self::MESSAGE, $context);
    }

    /**
     * @param LoggerInterface          $monologLogger
     * @param EventDispatcherInterface $eventDispatcher
     *
     * @return JobLogger
     */
    private function createJobLogger(
        LoggerInterface $monologLogger = null,
        EventDispatcherInterface $eventDispatcher = null
    ) {
        if (empty($monologLogger)) {
            $monologLogger = $this->getMockBuilder(Logger::class)
                ->setConstructorArgs(['name'])
                ->getMock();
        }

        $serializer = new JsonSerializer();

        if (empty($eventDispatcher)) {
            $eventDispatcher = new EventDispatcher();
        }

        $logger = new JobLogger($monologLogger, $serializer, $eventDispatcher);

        return $logger;
    }

    /**
     * Create a monolog logger that expects one call to the given method
     *
     * @param string $expectedCall The name of the method that should be called
     *
     * @return LoggerInterface
     */
    private function createMonologLogger($expectedCall)
    {
        $monolog = m::mock(NullLogger::class)
            ->shouldReceive($expectedCall)
            ->once()
            ->andReturn(true)
            ->getMock();

        return $monolog;
    }
}
