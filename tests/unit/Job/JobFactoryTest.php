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

use Disqontrol\Test\Helper\JobFactoryCreator;

class JobFactoryTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var JobFactory
     */ 
    private $factory;
    
    protected function setUp()
    {
        $this->factory = JobFactoryCreator::create();
    }
    
    public function testInstance()
    {
        $this->assertInstanceOf(JobFactory::class, $this->factory);
    }
}
