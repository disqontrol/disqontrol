<?php
/*
* This file is part of the Disqontrol package.
*
* (c) Mediaplus.cz s.r.o. <info@mediaplus.cz>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Disqontrol\Consumer;

/**
 * A long-running process that listens to the queue and processes new jobs
 * 
 * @author Martin Schlemmer
 */
interface ConsumerInterface
{
    /**
     * Start listening to the queues
     *
     * @param array $queueNames Names of the queues to listen to
     * @param int   $jobBatch   The number of jobs to fetch from Disque at once
     * @param bool  $burstMode  Should the consumer exit if there are no jobs?
     */
    public function listen(array $queueNames, $jobBatch, $burstMode);

    /**
     * Terminate the consumer and dispatcher
     *
     * - Wait for started job worker calls to return a result
     * - Don't start new calls
     * - Return unprocessed jobs to the queue
     * - Then exit
     */
    public function terminate();
}
