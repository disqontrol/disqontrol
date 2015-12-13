<?php
/*
 * This file is part of the Disqontrol package.
 *
 * (c) Webtrh s.r.o. <info@webtrh.cz>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Disqontrol\Producer;

use Disqontrol\Job\Marshaller\JobMarshaller;
use Mockery as m;
use Disqontrol\Job\Job;
use Disqontrol\Configuration\Configuration;
use Disque\Connection\Response\ResponseException;
use Disqontrol\Job\JobFactory;
use Disque\Client;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Disqontrol\Event\JobAddBeforeEvent;
use Disqontrol\Event\JobAddAfterEvent;

class ProducerTest extends \PHPUnit_Framework_TestCase
{
    const JOB_ID = 'jobid';
    const JOB_BODY = 'body';
    const JOB_QUEUE = 'queue';
    const MAX_JOB_PROCESS_TIME = 120;
    const MAX_JOB_LIFETIME = 21600;
    const DELAY = 2;

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
        $producer = $this->createProducer(null, null, self::DELAY);

        $job = new Job('body', 'queue');
        $result = $producer->add($job, self::DELAY);

        $this->assertTrue($result);
        $this->assertSame($job->getId(), self::JOB_ID);
    }

    public function testFailureAddingAJob()
    {
        $disque = m::mock(Client::class)
            ->shouldReceive('addJob')
            ->andThrow(ResponseException::class)
            ->getMock();

        $logger = m::mock(NullLogger::class)
            ->shouldReceive('debug')
            ->shouldReceive('error')
            ->once()
            ->getMock();

        $producer = $this->createProducer($disque, $logger);

        $job = new Job('body', 'queue');
        $result = $producer->add($job);

        $this->assertFalse($result);
    }

    private function createProducer(
        $disque = null,
        $logger = null,
        $jobDelay = 0
    ) {
        if ( ! isset($disque)) {
            $options = [
                Producer::DISQUE_ADDJOB_DELAY => $jobDelay,
                Producer::DISQUE_ADDJOB_MAX_JOB_PROCESS_TIME => self::MAX_JOB_PROCESS_TIME,
                Producer::DISQUE_ADDJOB_MAX_JOB_LIFETIME => self::MAX_JOB_LIFETIME
            ];

            $disque = m::mock(Client::class)
                ->shouldReceive('addJob')
                ->with(self::JOB_QUEUE, anything(), $options)
                ->andReturn(self::JOB_ID)
                ->getMock();
        }

        $jobFactory = new JobFactory();
        $jobMarshaller = new JobMarshaller($jobFactory);

        $config = m::mock(Configuration::class)
            ->shouldReceive('getMaxJobProcessTime')
            ->andReturn(self::MAX_JOB_PROCESS_TIME)
            ->shouldReceive('getMaxJobLifetime')
            ->andReturn(self::MAX_JOB_LIFETIME)
            ->getMock();

        if ( ! isset($logger)) {
            $logger = new NullLogger();
        }

        $eventDispatcher = m::mock(EventDispatcher::class)
            ->shouldReceive('dispatch')
            ->with(anything(), JobAddBeforeEvent::class)
            ->once()
            ->shouldReceive('dispatch')
            ->with(anything(), JobAddAfterEvent::class)
            ->once()
            ->getMock();

        $p = new Producer(
            $disque,
            $jobMarshaller,
            $config,
            $logger,
            $eventDispatcher
        );

        return $p;
    }
}
