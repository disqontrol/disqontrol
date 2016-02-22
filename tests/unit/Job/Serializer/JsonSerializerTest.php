<?php
/*
* This file is part of the Disqontrol package.
*
* (c) Webtrh s.r.o. <info@webtrh.cz>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Disqontrol\Job\Serializer;

class JsonSerializerTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var JsonSerializer
     */ 
    private $serializer;
    
    protected function setUp()
    {
        $this->serializer = new JsonSerializer();
    }
    
    public function testInstance()
    {
        $this->assertInstanceOf(JsonSerializer::class, $this->serializer);
    }

    public function testDeserializingReturnsArray()
    {
        $serializedString = $this->serializer->serialize(['test']);
        $this->assertTrue(is_array($this->serializer->deserialize($serializedString)));
    }
}
