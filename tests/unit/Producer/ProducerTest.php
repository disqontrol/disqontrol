<?php
/*
 * This file is part of the Disqontrol package.
 *
 * (c) Mediaplus.cz s.r.o. <info@mediaplus.cz>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Disqontrol\Producer;

use Disqontrol\Disque\AddJob;
use Mockery as m;
use Disqontrol\Job\Job;
use Disqontrol\Configuration\Configuration;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Disqontrol\Event\JobAddBeforeEvent;
use Disqontrol\Event\JobAddAfterEvent;

class ProducerTest extends \PHPUnit_Framework_TestCase
{
    const JOB_ID = 'jobid';
    const JOB_BODY = 'body';
    const JOB_QUEUE = 'queue';
    const JOB_PROCESS_TIMEOUT = 120;
    const JOB_LIFETIME = 21600;
    const DELAY = 2;

    public function tearDown()
    {
        m::close();
    }

    public function testAddingAJob()
    {
        $producer = $this->createProducer();

        $job = new Job('body', 'queue');
        $result = $producer->add($job);

        $this->assertTrue($result);
        $this->assertSame($job->getId(), self::JOB_ID);
    }

    public function testAddingADelayedJob()
    {
        $job = new Job('body', 'queue');
        $addJob = m::mock(AddJob::class)
            ->shouldReceive('add')
            ->with($job, self::DELAY, self::JOB_PROCESS_TIMEOUT, self::JOB_LIFETIME)
            ->andReturn(self::JOB_ID)
            ->getMock();

        $producer = $this->createProducer($addJob);

        $result = $producer->add($job, self::DELAY);

        $this->assertTrue($result);
        $this->assertSame($job->getId(), self::JOB_ID);
    }

    public function testFailureAddingAJob()
    {
        $addJob = m::mock(AddJob::class)
            ->shouldReceive('add')
            ->andReturn(false)
            ->getMock();

        $producer = $this->createProducer($addJob);

        $job = new Job('body', 'queue');
        $result = $producer->add($job);

        $this->assertFalse($result);
    }

    public function testProducerSetsJobMetadata()
    {
        $job = new Job('body', 'queue');
        $producer = $this->createProducer();

        $this->assertNull($job->getJobLifetime());
        $producer->add($job);
        $this->assertSame(self::JOB_LIFETIME, $job->getJobLifetime());
    }

    public function testAddingAJobToUndefinedQueue()
    {
        $queue = 'undefined-queue';

        $logger = m::mock(LoggerInterface::class)
            ->shouldReceive('debug')
            ->once()
            ->getMock();

        $producer = $this->createProducer(null, $logger);

        $job = new Job('body', $queue);
        $result = $producer->add($job);
        $this->assertTrue($result);
    }

    private function createProducer($addJob = null, $logger = null)
    {
        $config = m::mock(Configuration::class)
            ->shouldReceive('getJobProcessTimeout')
            ->andReturn(self::JOB_PROCESS_TIMEOUT)
            ->shouldReceive('getJobLifetime')
            ->andReturn(self::JOB_LIFETIME)
            ->shouldReceive('getQueuesConfig')
            ->andReturn([self::JOB_QUEUE => 'queue options'])
            ->getMock();

        $eventDispatcher = m::mock(EventDispatcher::class)
            ->shouldReceive('dispatch')
            ->with(anything(), JobAddBeforeEvent::class)
            ->once()
            ->shouldReceive('dispatch')
            ->with(anything(), JobAddAfterEvent::class)
            ->once()
            ->getMock();

        if ( ! isset($addJob)) {
            $addJob = m::mock(AddJob::class)
                ->shouldReceive('add')
                ->andReturn(self::JOB_ID)
                ->getMock();
        }

        if ( ! isset($logger)) {
            $logger = m::mock(LoggerInterface::class)
                ->shouldNotReceive('debug')
                ->getMock();
        }

        $p = new Producer($config, $eventDispatcher, $addJob, $logger);

        return $p;
    }
}
