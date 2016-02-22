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
use Disqontrol\Job\Serializer\JsonSerializer;
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
    const JOB_PROCESS_TIMEOUT = 120;
    const JOB_LIFETIME = 21600;
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
                Producer::DISQUE_ADDJOB_JOB_PROCESS_TIMEOUT => self::JOB_PROCESS_TIMEOUT,
                Producer::DISQUE_ADDJOB_JOB_LIFETIME => self::JOB_LIFETIME
            ];

            $disque = m::mock(Client::class)
                ->shouldReceive('addJob')
                ->with(self::JOB_QUEUE, anything(), $options)
                ->andReturn(self::JOB_ID)
                ->getMock();
        }

        $jobFactory = new JobFactory();
        $serializer = new JsonSerializer();
        $jobMarshaller = new JobMarshaller($jobFactory, $serializer);

        $config = m::mock(Configuration::class)
            ->shouldReceive('getJobProcessTimeout')
            ->andReturn(self::JOB_PROCESS_TIMEOUT)
            ->shouldReceive('getJobLifetime')
            ->andReturn(self::JOB_LIFETIME)
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
