<?php
/*
 * This file is part of the Disqontrol package.
 *
 * (c) Webtrh s.r.o. <info@webtrh.cz>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Disqontrol\Router;

use Disqontrol\Worker\WorkerType;

class WorkerDirectionsTest extends \PHPUnit_Framework_TestCase
{
    /** @var WorkerDirections */
    private $directions;

    private $workerType;
    private $address = 'some_address';
    private $params = [
        'key-1' => 'value 1',
        'key-2' => 'value 2'
    ];

    protected function setUp()
    {
        $this->workerType = WorkerType::HTTP();
        
        $this->directions = new WorkerDirections(
            $this->workerType,
            $this->address,
            $this->params
        );
    }

    public function testInstance()
    {
        $this->assertInstanceOf(WorkerDirections::class, $this->directions);
    }

    public function testGetWorkerType()
    {
        $this->assertSame($this->workerType, $this->directions->getType());
    }

    public function testGetAddress()
    {
        $this->assertSame($this->address, $this->directions->getAddress());
    }

    public function testGetParams()
    {

        $this->assertSame($this->params, $this->directions->getParameters());
    }

}
