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
 * A factory for autoscaling algorithms used in ConsumerProcessGroups
 *
 * @author Martin Schlemmer
 */
class AutoscaleAlgorithmFactory
{
    /**
     * Create an algorithm that always returns a constant number
     *
     * @param int $processCount The number of processes the algorithm returns
     *
     * @return ConstantProcessCount
     */
    public function createConstantAlgorithm($processCount)
    {
        return new ConstantProcessCount($processCount);
    }
}
