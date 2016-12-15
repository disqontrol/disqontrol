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
 * An interface for all autoscaling algorithms
 *
 * Autoscaling algorithms are used by ConsumerProcessGroups to adjust the number
 * of running processes according to the queue load.
 *
 * @package Disqontrol\Consumer\Autoscale
 */
interface AutoscaleAlgorithmInterface
{
    /**
     * Calculate the recommended number of consumer processes
     *
     * @param $currentProcessCount int How many processes are active now
     *
     * @return int
     */
    public function calculateProcessCount($currentProcessCount);
}
