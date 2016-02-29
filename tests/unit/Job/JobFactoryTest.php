<?php
/*
* This file is part of the Disqontrol package.
*
* (c) Webtrh s.r.o. <info@webtrh.cz>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Disqontrol\Job;

class JobFactoryTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var JobFactory
     */ 
    private $factory;
    
    protected function setUp()
    {
        $this->factory = new JobFactory();
    }
    
    public function testInstance()
    {
        $this->assertInstanceOf(JobFactory::class, $this->factory);
    }

    public function TestCloningJob()
    {
        $body = 'body';
        $queue = 'queue';
        $id = 'id';
        $nacks = 2;
        $additionalDeliveries = 3;

        $job = new Job($body, $queue, $id, $nacks, $additionalDeliveries);

        $newJob = $this->factory->cloneFailedJob($job);

        $this->assertSame($body, $newJob->getBody());
        $this->assertSame($queue, $newJob->getQueue());
        $this->assertSame($id, $newJob->getOriginalId());
        $this->assertNull($newJob->getId());
        $totalRetries = $nacks + $additionalDeliveries + 1;
        $this->assertSame($totalRetries, $newJob->getRetryCount());
    }
}
