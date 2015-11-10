<?php
/*
 * This file is part of the Disqontrol package.
 *
 * (c) Webtrh s.r.o. <info@webtrh.cz>
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
     * The job must define the queue it should be sent to
     *
     * @param JobInterface $job
     *
     * @return bool Result of adding the job
     */
    public function add(JobInterface $job);
}
