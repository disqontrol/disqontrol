<?php
/*
 * This file is part of the Disqontrol package.
 *
 * (c) Mediaplus.cz s.r.o. <info@mediaplus.cz>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Disqontrol\Producer;

use Disqontrol\Job\JobInterface;

/**
 * Disqontrol producer interface
 *
 * A producer can send jobs to the queue for asynchronous processing.
 *
 * @author Martin Schlemmer
 */
interface ProducerInterface
{
    /**
     * Add a job to the queue
     *
     * The job must define the queue it should be sent to.
     *
     * The method has a side effect - if adding the job to Disque succeeds,
     * the method assigns a job ID to the job, so the caller can work with it
     * further.
     *
     * @param JobInterface $job
     * @param int          $delay How many seconds should the job be delayed?
     *
     * @return bool Result of adding the job
     */
    public function add(JobInterface $job, $delay = 0);
}
