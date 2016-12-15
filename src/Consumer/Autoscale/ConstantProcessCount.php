<?php
/*
* This file is part of the Disqontrol package.
*
* (c) Mediaplus.cz s.r.o. <info@mediaplus.cz>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Disqontrol\Consumer\Autoscale;

/**
 * An autoscaling algorithm that always returns a constant number
 *
 * @author Martin Schlemmer
 */
class ConstantProcessCount implements AutoscaleAlgorithmInterface
{
    /**
     * @var int Minimum number of consumer processes in the group
     */
    private $processCount;

    /**
     * @param int $processCount The target number of processes
     */
    public function __construct($processCount)
    {
        $this->processCount = $this->atLeastOne($processCount);
    }

    /**
     * @inheritdoc
     */
    public function calculateProcessCount($currentProcessCount)
    {
        return $this->processCount;
    }

    /**
     * Ensure that the value is an integer with a value of at least 1
     *
     * @param mixed $value
     *
     * @return int $value
     */
    private function atLeastOne($value)
    {
        return max(1, (int) $value);
    }
}
