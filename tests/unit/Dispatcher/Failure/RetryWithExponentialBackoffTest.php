<?php
/*
* This file is part of the Disqontrol package.
*
* (c) Webtrh s.r.o. <info@webtrh.cz>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Disqontrol\Dispatcher\Failure;

use Disqontrol\Disque\FailJob;
use Disqontrol\Job\Job;
use Disque\Queue\JobInterface;
use Mockery as m;
use Disqontrol\Dispatcher\Call\CallInterface;

class RetryWithExponentialBackoffTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Temporary variable for a test of delays
     *
     * @var int
     */
    private $firstJobDelay = 0;
    private $secondJobDelay = 0;

    public function tearDown()
    {
        m::close();
    }

    public function testInstance()
    {
        $retry = $this->createRetry();
        $this->assertInstanceOf(RetryWithExponentialBackoff::class, $retry);
    }

    /**
     * Test that a job with a few retries has a delay higher than zero
     * And that a job with many retries has a delay higher than the first job
     */
    public function testEverLongerDelay()
    {
        $firstJob = new Job('body', 'queue');
        $firstJob->setPreviousRetryCount(2);
        $call = $this->wrapJobInCall($firstJob);

        $failJob = m::mock(FailJob::class)
            ->shouldReceive('logError')
            ->shouldReceive('nack')
            ->with(anything(), m::on(function ($firstDelay) {
                $this->firstJobDelay = $firstDelay;
                return true;
            }))
            ->mock();

        $retry = $this->createRetry($failJob);
        $retry->handleFailure($call);

        // The first job's delay should be higher than 0
        $this->assertTrue(0 < $this->firstJobDelay);

        $secondJob = new Job('body', 'queue');
        $secondJob->setPreviousRetryCount(10);
        $call = $this->wrapJobInCall($secondJob);

        $failJob = m::mock(FailJob::class)
            ->shouldReceive('logError')
            ->shouldReceive('nack')
            ->with(anything(), m::on(function ($secondDelay) {
                $this->secondJobDelay = $secondDelay;
                return true;
            }))
            ->mock();

        $retry = $this->createRetry($failJob);
        $retry->handleFailure($call);

        // The second job's delay should be higher than the first job's delay
        $this->assertTrue($this->firstJobDelay < $this->secondJobDelay);
    }

    private function createRetry($failJob = null)
    {
        if (is_null($failJob)) {
            $failJob = m::mock(FailJob::class);
        }

        $backoff = new ExponentialBackoff();

        $retry = new RetryWithExponentialBackoff($failJob, $backoff);

        return $retry;
    }

    private function wrapJobInCall(JobInterface $job)
    {
        $call = m::mock(CallInterface::class)
            ->shouldReceive('getErrorMessage')
                ->andReturn('')
            ->shouldReceive('getJob')
                ->andReturn($job)
                ->once()
            ->mock();

        return $call;
    }
}
