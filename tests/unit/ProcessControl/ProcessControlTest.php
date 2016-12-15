<?php
/*
* This file is part of the Disqontrol package.
*
* (c) Mediaplus.cz s.r.o. <info@mediaplus.cz>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Disqontrol\ProcessControl;

class ProcessControlTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProcessControl
     */
    private $processControl;
    
    protected function setUp()
    {
        $this->processControl = new ProcessControl();
    }
    
    public function testInstance()
    {
        $this->assertInstanceOf(ProcessControl::class, $this->processControl);
    }

    public function testHasPcntl()
    {
        $this->assertFalse(is_null($this->processControl->hasPcntl()));
    }
}
