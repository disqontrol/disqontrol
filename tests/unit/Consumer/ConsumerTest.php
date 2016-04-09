<?php
/*
* This file is part of the Disqontrol package.
*
* (c) Webtrh s.r.o. <info@webtrh.cz>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Disqontrol\Consumer;

use Disqontrol\Dispatcher\JobDispatcherInterface;
use Disqontrol\Job\Marshaller\MarshallerInterface;
use Disqontrol\Job\Serializer\JsonSerializer;
use Disqontrol\ProcessControl\ProcessControl;
use Disque\Client;
use Mockery as m;
use Psr\Log\NullLogger;

class ConsumerTest extends \PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }
    
    public function testInstance()
    {
        $consumer = $this->createConsumer();
        $this->assertInstanceOf(Consumer::class, $consumer);
    }

    /**
     * In the following tests it's enough that the consumer returns - anything.
     */
    public function testConsumerInBurstModeWithoutJobsExits()
    {
        $consumer = $this->createConsumer();
        $queues = [];
        $jobBatch = 10;
        $burstMode = true;

        $consumerReturned = is_null($consumer->listen($queues, $jobBatch, $burstMode));
        $this->assertTrue($consumerReturned);
    }

    /**
     * Test that the consumer exits the main loop if it receives a terminate
     * signal at the beginning of the main loop
     */
    public function testConsumerExitsAfterReceivingSignal()
    {
        $disque = m::mock(Client::class)
            ->shouldReceive('getJob')
            ->never()
            ->getMock();
        $consumer = $this->createConsumer($disque);

        $consumer->terminate();
        $queues = [];
        $jobBatch = 10;
        $burstMode = false;

        $consumerReturned = is_null($consumer->listen($queues, $jobBatch, $burstMode));
        $this->assertTrue($consumerReturned);
    }

    private function createConsumer(
        $disque = null,
        $marshaller = null,
        $dispatcher = null,
        $processControl = null
    ) {
        if (is_null($disque)) {
            $disque = $this->mockDisque();
        }
        if (is_null($marshaller)) {
            $marshaller = $this->mockMarshaller();
        }
        if (is_null($dispatcher)) {
            $dispatcher = $this->mockDispatcher();
        }
        if (is_null($processControl)) {
            $processControl = $this->mockProcessControl();
        }

        $serializer = new JsonSerializer();
        $logger = new NullLogger();

        $consumer = new Consumer($disque, $marshaller, $dispatcher, $processControl, $serializer, $logger);
        return $consumer;
    }

    /**
     * Mock the Disque client
     *
     * @param mixed $getJobResult
     *
     * @return Client (mocked)
     */
    private function mockDisque($getJobResult = null)
    {
        $disque = m::mock(Client::class)
            ->shouldReceive('getJob')
            ->andReturn($getJobResult)
            ->getMock();

        return $disque;
    }

    /**
     * Mock the JobMarshaller
     *
     * @param array $jobs Values the marshaller should return on each call
     *
     * @return MarshallerInterface (mocked)
     */
    private function mockMarshaller($jobs = array())
    {
        $marshaller = m::mock(MarshallerInterface::class)
            ->shouldReceive('unmarshal')
            ->andReturnUsing(function ($jobData) use ($jobs) {
                static $jobIndex = 0;
                $job = $jobs[$jobIndex];
                $jobIndex++;

                if ($job instanceof Exception) {
                    throw $job;
                }

                return $job;
            })
            ->getMock();

        return $marshaller;
    }

    private function mockDispatcher()
    {
        $dispatcher = m::mock(JobDispatcherInterface::class)
            ->shouldReceive('dispatch')
            ->shouldReceive('terminate')
            ->getMock();;

        return $dispatcher;
    }

    private function mockProcessControl()
    {
        $pc = m::mock(ProcessControl::class)
            ->shouldReceive('registerSignalHandler')
            ->shouldReceive('checkForSignals')
            ->getMock();

        return $pc;
    }

}
