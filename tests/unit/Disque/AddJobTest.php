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

use Disqontrol\Job\Job;
use Mockery as m;
use Disqontrol\Job\Marshaller\JobMarshaller;
use Disqontrol\Job\Serializer\JsonSerializer;
use Disqontrol\Job\JobFactory;
use Disque\Client;
use Psr\Log\NullLogger;
use Disque\Connection\Response\ResponseException;

class AddJobTest extends \PHPUnit_Framework_TestCase
{
    const JOB_ID = 'jobid';
    const JOB_BODY = 'body';
    const JOB_QUEUE = 'queue';
    const JOB_PROCESS_TIMEOUT = 120;
    const JOB_LIFETIME = 21600;

    public function tearDown()
    {
        m::close();
    }
    
    public function testInstance()
    {
        $addJob = $this->createAddJob();
        $this->assertInstanceOf(AddJob::class, $addJob);
    }

    public function testFailureAddingJob()
    {
        $disque = m::mock(Client::class)
            ->shouldReceive('addJob')
            ->andThrow(ResponseException::class)
            ->getMock();

        $logger = m::mock(NullLogger::class)
            ->shouldReceive('error')
            ->once()
            ->getMock();

        $addJob = $this->createAddJob($disque, $logger);

        $job = new Job('body', 'queue');
        $result = $addJob->add($job, 0, 1, 1);

        $this->assertFalse($result);
    }

    private function createAddJob($disque = null, $logger = null, $jobDelay = 0)
    {
        if ( ! isset($disque)) {
            $options = [
                AddJob::DISQUE_ADDJOB_DELAY => $jobDelay,
                AddJob::DISQUE_ADDJOB_JOB_PROCESS_TIMEOUT => self::JOB_PROCESS_TIMEOUT,
                AddJob::DISQUE_ADDJOB_JOB_LIFETIME => self::JOB_LIFETIME
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

        if ( ! isset($logger)) {
            $logger = new NullLogger();
        }

        return new AddJob($disque, $jobMarshaller, $logger);
    }
}
