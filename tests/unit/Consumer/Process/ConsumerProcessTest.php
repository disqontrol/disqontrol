<?php
/*
* This file is part of the Disqontrol package.
*
* (c) Webtrh s.r.o. <info@webtrh.cz>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Disqontrol\Consumer\Process;

class ConsumerProcessTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ConsumerProcess
     */
    private $cp;
    
    protected function setUp()
    {
        $this->cp = new ConsumerProcess('foo');
    }
    
    public function testInstance()
    {
        $this->assertInstanceOf(ConsumerProcess::class, $this->cp);
    }

    public function testBurstMode()
    {
        $burstMode = true;
        $this->cp->setBurstMode($burstMode);
        $this->assertEquals($burstMode, $this->cp->isInBurstMode());
    }
}
