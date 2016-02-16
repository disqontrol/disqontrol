<?php
/*
 * This file is part of the Disqontrol package.
 *
 * (c) Webtrh s.r.o. <info@webtrh.cz>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Disqontrol\Job\Marshaller;

use Disqontrol\Job\Job;
use Disqontrol\Job\JobFactory;
use Disqontrol\Job\JobInterface;
use Disque\Command\Response\JobsResponse AS Response;
use Disque\Command\Response\JobsWithQueueResponse AS QueueResponse;
use Disque\Command\Response\JobsWithCountersResponse AS Counters;

class JobMarshallerTest extends \PHPUnit_Framework_TestCase
{
    const ID = 'id';
    const QUEUE = 'queue';
    const BODY = 'body';
    const METADATA = 'metadata';
    const ADDITIONAL_DELIVERIES = 123;
    const NACKS = 321;

    private $bodyWithMetadata = [
        Job::KEY_BODY => self::BODY,
        Job::KEY_METADATA => self::METADATA
    ];

    /**
     * @var JobMarshaller
     */
    private $jm;

    public function setUp()
    {
        $jobFactory = new JobFactory();
        $this->jm = new JobMarshaller($jobFactory);
    }

    public function testInstance()
    {
        $this->assertInstanceOf(MarshallerInterface::class, $this->jm);
    }

    public function testMarshalAndUnmarshalAJob()
    {
        $jobBody = $this->jm->marshal($this->bodyWithMetadata);

        $disqueResponse = [
            Response::KEY_ID => self::ID,
            QueueResponse::KEY_QUEUE => self::QUEUE,
            Response::KEY_BODY => $jobBody
        ];

        $job = $this->jm->unmarshal($disqueResponse);

        $this->assertInstanceOf(JobInterface::class, $job);
        $this->assertSame($job->getBody(), self::BODY);
        $this->assertSame($job->getAllMetadata(), self::METADATA);
    }

    public function testMarshalAndUnmarshalAJobWithCounters()
    {
        $jobBody = $this->jm->marshal($this->bodyWithMetadata);

        $disqueResponse = [
            Response::KEY_ID => self::ID,
            QueueResponse::KEY_QUEUE => self::QUEUE,
            Response::KEY_BODY => $jobBody,
            Counters::KEY_ADDITIONAL_DELIVERIES => self::ADDITIONAL_DELIVERIES,
            Counters::KEY_NACKS => self::NACKS
        ];

        $job = $this->jm->unmarshal($disqueResponse);

        $this->assertInstanceOf(JobInterface::class, $job);
        $this->assertSame($job->getAdditionalDeliveries(), self::ADDITIONAL_DELIVERIES);
        $this->assertSame($job->getNacks(), self::NACKS);
    }

    public function testUnmarshalIncompleteResponse()
    {
        $disqueResponse = [
            Response::KEY_ID => self::ID,
            QueueResponse::KEY_QUEUE => self::QUEUE,
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->jm->unmarshal($disqueResponse);
    }
}
