<?php
/*
* This file is part of the Disqontrol package.
*
* (c) Webtrh s.r.o. <info@webtrh.cz>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Disqontrol\Disque;

use Disqontrol\Configuration\Configuration;
use Disqontrol\Job\Job;
use Disqontrol\Job\JobFactory;
use Disque\Client;
use Psr\Log\NullLogger;
use Mockery as m;
use Psr\Log\LoggerInterface;
use Exception;
use Disqontrol\Test\Helper\JobFactoryCreator;

class FailJobTest extends \PHPUnit_Framework_TestCase
{
    const JOB_ID = 'job_id';
    const DELAY = 5;
    // Lifetime must be higher than the delay
    const LIFETIME = 999;
    const PROCESS_TIMEOUT = 123;
    const MAX_RETRIES = 10;
    const RETRIES = 5;
    const FAILURE_QUEUE = 'failure_queue';

    public function tearDown()
    {
        m::close();
    }

    // The next six methods test all three paths through FailJob::nack() and
    // their respective failures
    public function testNack()
    {
        $job = new Job('body', 'queue', self::JOB_ID);
        $job->setCreationTime(time());
        $job->setJobLifetime(999);

        $disque = m::mock(Client::class)
            ->shouldReceive('nack')
            ->with(self::JOB_ID)
            ->once()
            ->getMock();

        $failJob = $this->createFailJob($disque);

        $failJob->nack($job, 0);
    }

    public function testFailedNack()
    {
        $job = new Job('body', 'queue', self::JOB_ID);
        $job->setCreationTime(time());
        $job->setJobLifetime(999);

        $disque = m::mock(Client::class)
            ->shouldReceive('nack')
            ->with(self::JOB_ID)
            ->andThrow(Exception::class)
            ->once()
            ->getMock();
        $logger = m::mock(LoggerInterface::class)
            ->shouldReceive('error')
            ->once()
            ->getMock();

        $failJob = $this->createFailJob($disque, null, null, $logger);

        $this->assertFalse($failJob->nack($job, 0));
    }

    public function testDelayedNack()
    {
        $job = new Job('body', 'queue', self::JOB_ID);
        $job->setCreationTime(time() - 6);
        $job->setJobLifetime(self::LIFETIME);
        $job->setProcessTimeout(self::PROCESS_TIMEOUT);

        $addJob = m::mock(AddJob::class)
            ->shouldReceive('add')
            ->with(anything(), self::DELAY, self::PROCESS_TIMEOUT, m::on(function ($lifetime) {
                return $lifetime < self::LIFETIME;
            }))
            ->once()
            ->getMock();

        $failJob = $this->createFailJob(null, $addJob);

        $failJob->nack($job, self::DELAY);
    }

    public function testFailedDelayedNack()
    {
        $job = new Job('body', 'queue', self::JOB_ID);
        $job->setCreationTime(time() - 6);
        $job->setJobLifetime(self::LIFETIME);
        $job->setProcessTimeout(self::PROCESS_TIMEOUT);

        $addJob = m::mock(AddJob::class)
            ->shouldReceive('add')
            ->andReturn(false)
            ->once()
            ->getMock();
        $logger = m::mock(LoggerInterface::class)
            ->shouldReceive('error')
            ->once()
            ->getMock();

        $failJob = $this->createFailJob(null, $addJob, null, $logger);

        $this->assertFalse($failJob->nack($job, self::DELAY));
    }

    public function testTimedOutJobMovesToFailureQueueInsteadOfNack()
    {
        $job = new Job('body', 'queue');
        $job->setCreationTime(strtotime('1970-01-01'));
        $job->setJobLifetime(1);

        $disque = m::mock(Client::class)
            ->shouldReceive('ackJob')
            ->once()
            ->getMock();
        $addJob = m::mock(AddJob::class)
            ->shouldReceive('add')
            ->with(m::on(function($job) {
                return $job->getQueue() === self::FAILURE_QUEUE;
            }), anything(), anything(), anything())
            ->once()
            ->getMock();
        $config = m::mock(Configuration::class)
            ->shouldReceive('getFailureQueue')
            ->andReturn(self::FAILURE_QUEUE)
            ->once()
            ->shouldReceive('getMaxRetries')
            ->andReturn(self::MAX_RETRIES)
            ->getMock();

        $failJob = $this->createFailJob($disque, $addJob, $config);

        $failJob->nack($job, 0);
    }

    public function testJobWithLongNackDelayMovesToFailureQueue()
    {
        $job = new Job('body', 'queue');
        $job->setCreationTime(time());
        $job->setJobLifetime(self::LIFETIME);

        $disque = m::mock(Client::class)
            ->shouldReceive('ackJob')
            ->once()
            ->getMock();
        $addJob = m::mock(AddJob::class)
            ->shouldReceive('add')
            ->with(m::on(function($job) {
                return $job->getQueue() === self::FAILURE_QUEUE;
            }), anything(), anything(), anything())
            ->once()
            ->getMock();
        $config = m::mock(Configuration::class)
            ->shouldReceive('getFailureQueue')
            ->andReturn(self::FAILURE_QUEUE)
            ->once()
            ->shouldReceive('getMaxRetries')
            ->andReturn(self::MAX_RETRIES)
            ->getMock();

        $failJob = $this->createFailJob($disque, $addJob, $config);

        $failJob->nack($job, self::LIFETIME * 10);
    }

    public function testJobWithNoRetriesLeftMovesToFailureQueueInsteadOfNack()
    {
        $job = new Job('body', 'queue');
        $job->setJobLifetime(self::LIFETIME);
        $job->setPreviousRetryCount(self::RETRIES);

        $disque = m::mock(Client::class)
            ->shouldReceive('ackJob')
            ->once()
            ->getMock();
        $addJob = m::mock(AddJob::class)
            ->shouldReceive('add')
            ->with(m::on(function($job) {
                return $job->getQueue() === self::FAILURE_QUEUE;
            }), anything(), anything(), anything())
            ->once()
            ->getMock();
        $config = m::mock(Configuration::class)
            ->shouldReceive('getFailureQueue')
            ->andReturn(self::FAILURE_QUEUE)
            ->once()
            ->shouldReceive('getMaxRetries')
            ->andReturn(self::RETRIES - 1)
            ->getMock();

        $failJob = $this->createFailJob($disque, $addJob, $config);

        $failJob->nack($job, 0);

    }

    public function testMoveToFailureQueue()
    {
        $job = new Job('body', 'queue', self::JOB_ID);
        $job->setProcessTimeout(self::PROCESS_TIMEOUT);

        $disque = m::mock(Client::class)
            ->shouldReceive('ackJob')
            ->with(self::JOB_ID)
            ->once()
            ->getMock();
        $addJob = m::mock(AddJob::class)
            ->shouldReceive('add')
            ->with(m::on(function($job) {
                return $job->getQueue() === self::FAILURE_QUEUE;
            }), anything(), self::PROCESS_TIMEOUT, Configuration::MAX_ALLOWED_JOB_LIFETIME)
            ->andReturn(true)
            ->once()
            ->getMock();
        $config = m::mock(Configuration::class)
            ->shouldReceive('getFailureQueue')
            ->andReturn(self::FAILURE_QUEUE)
            ->once()
            ->getMock();

        $failJob = $this->createFailJob($disque, $addJob, $config);

        $this->assertTrue($failJob->moveToFailureQueue($job));
    }

    /**
     * Test that the move goes on even if the first part, the ACK, fails
     *
     * It logs the error but goes on with the move anyway.
     */
    public function testPartiallyFailedMoveToFailureQueue()
    {
        $job = new Job('body', 'queue', self::JOB_ID);
        $job->setProcessTimeout(self::PROCESS_TIMEOUT);

        $disque = m::mock(Client::class)
            ->shouldReceive('ackJob')
            ->with(self::JOB_ID)
            ->andThrow(Exception::class)
            ->once()
            ->getMock();
        $addJob = m::mock(AddJob::class)
            ->shouldReceive('add')
            ->with(m::on(function($job) {
                return $job->getQueue() === self::FAILURE_QUEUE;
            }), anything(), self::PROCESS_TIMEOUT, Configuration::MAX_ALLOWED_JOB_LIFETIME)
            ->andReturn(true)
            ->once()
            ->getMock();
        $config = m::mock(Configuration::class)
            ->shouldReceive('getFailureQueue')
            ->andReturn(self::FAILURE_QUEUE)
            ->once()
            ->getMock();
        $logger = m::mock(LoggerInterface::class)
            ->shouldReceive('error')
            ->once()
            ->shouldReceive('info')
            ->getMock();

        $failJob = $this->createFailJob($disque, $addJob, $config, $logger);

        $this->assertTrue($failJob->moveToFailureQueue($job));
    }

    public function testFailedMoveToFailureQueue()
    {
        $job = new Job('body', 'queue');
        $job->setProcessTimeout(self::PROCESS_TIMEOUT);

        $disque = m::mock(Client::class)
            ->shouldReceive('ackJob')
            ->once()
            ->getMock();
        $addJob = m::mock(AddJob::class)
            ->shouldReceive('add')
            ->with(anything(), anything(), self::PROCESS_TIMEOUT, Configuration::MAX_ALLOWED_JOB_LIFETIME)
            ->andReturn(false)
            ->once()
            ->getMock();
        $config = m::mock(Configuration::class)
            ->shouldReceive('getFailureQueue')
            ->once()
            ->getMock();
        $logger = m::mock(LoggerInterface::class)
            ->shouldReceive('critical')
            ->once()
            ->getMock();

        $failJob = $this->createFailJob($disque, $addJob, $config, $logger);

        $this->assertFalse($failJob->moveToFailureQueue($job));
    }

    private function createFailJob($disque = null, $addJob = null, $config = null, $logger = null)
    {
        if (is_null($disque)) {
            $disque = $this->getMockBuilder(Client::class)
                ->disableOriginalConstructor()
                ->getMock();
        }
        if (is_null($addJob)) {
            $addJob = $this->getMockBuilder(AddJob::class)
                ->disableOriginalConstructor()
                ->getMock();
        }
        $jobFactory = JobFactoryCreator::create();
        if (is_null($config)) {
            $config = m::mock(Configuration::class)
                ->shouldReceive('getFailureQueue')
                ->andReturn(self::FAILURE_QUEUE)
                ->shouldReceive('getMaxRetries')
                ->andReturn(self::MAX_RETRIES)
                ->mock();
        }
        if (is_null($logger)) {
            $logger = new NullLogger();
        }

        $failJob = new FailJob(
            $disque,
            $addJob,
            $jobFactory,
            $config,
            $logger
        );

        return $failJob;
    }
}
