<?php
/*
 * This file is part of the Disqontrol package.
 *
 * (c) Mediaplus.cz s.r.o. <info@mediaplus.cz>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Disqontrol\Job;

class JobTest extends \PHPUnit_Framework_TestCase
{
    private $key = 'key';
    private $value = 'value';

    private $key2 = 'key2';
    private $value2 = 'value2';

    private $body = 'body';
    private $metadata;
    private $bodyWithMetadata;

    protected function setUp()
    {
        $this->j = new Job($this->body, 'queue');
        $this->metadata = [
            $this->key => $this->value,
            $this->key2 => $this->value2
        ];
        $this->bodyWithMetadata = [
            Job::KEY_BODY => $this->body,
            Job::KEY_METADATA => $this->metadata
        ];
    }

    public function testInstance()
    {
        $this->assertInstanceOf(JobInterface::class, $this->j);
    }

    public function testHasNoMetadata()
    {
        $this->assertEmpty($this->j->getAllMetadata());
    }

    public function testHasSomeMetadata()
    {
        $this->j->setMetadata($this->key, $this->value);
        $this->assertTrue($this->j->hasMetadata($this->key));
    }

    public function testSetGetMetadata()
    {
        $this->j->setMetadata($this->key, $this->value);
        $this->assertSame($this->value, $this->j->getMetadata($this->key));
    }

    public function testHasMoreMetadata()
    {
        foreach($this->metadata as $key => $value) {
            $this->j->setMetadata($key, $value);
        }
        $this->assertArraySubset($this->metadata, $this->j->getAllMetadata());
    }

    public function testOverwriteMetadata()
    {
        $this->j->setMetadata($this->key, $this->value);
        $this->j->setMetadata($this->key, $this->body);
        $this->assertSame($this->body, $this->j->getMetadata($this->key));
    }

    public function testSetGetBody()
    {
        $this->j->setBody($this->body);
        $this->assertSame($this->body, $this->j->getBody());
    }

    public function testSetGetBodyWithoutMetadata()
    {
        $this->j->setBody($this->body);
        $bodyWithMetadata = $this->j->getBodyWithMetadata();
        $this->assertEmpty($bodyWithMetadata[Job::KEY_METADATA]);
    }

    public function testSetBodyWithMetadataRightBody()
    {
        $this->j->setBody($this->bodyWithMetadata);
        $this->assertSame($this->body, $this->j->getBody());
    }

    public function testSetBodyWithMetadataMetadataExist()
    {
        $this->j->setBody($this->bodyWithMetadata);
        $this->assertTrue($this->j->hasMetadata($this->key));
    }

    public function testSetBodyWithMetadataRightMetadata()
    {
        $this->j->setBody($this->bodyWithMetadata);
        $this->assertSame($this->value, $this->j->getMetadata($this->key));
    }

    public function testBodyAndMetadataPersistBetweenInstances()
    {
        $this->j->setBody($this->bodyWithMetadata);

        $originalBody = $this->j->getBodyWithMetadata();
        $newJob = new Job($originalBody, 'queue');

        $this->assertSame($originalBody, $newJob->getBodyWithMetadata());
    }

    public function testRetryCountEqualToDisqueCount()
    {
        $nacks = 2;
        $this->j->setNacks($nacks);
        $addDeliveries = 3;
        $this->j->setAdditionalDeliveries($addDeliveries);

        $totalRetries = $nacks + $addDeliveries;
        $this->assertSame($totalRetries, $this->j->getRetryCount());
    }

    public function testRetryCountIncludesPreviousRetries()
    {
        $nacks = 2;
        $this->j->setNacks($nacks);
        $addDeliveries = 3;
        $this->j->setAdditionalDeliveries($addDeliveries);
        $retryCount = 4;
        $this->j->setPreviousRetryCount($retryCount);

        $totalRetries = $nacks + $addDeliveries + $retryCount;
        $this->assertSame($totalRetries, $this->j->getRetryCount());
    }

    public function testSetGetOriginalJobId()
    {
        $originalId = 'origid';
        $this->j->setOriginalId($originalId);
        $this->assertSame($originalId, $this->j->getOriginalId());
    }
}
