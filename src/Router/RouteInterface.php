<?php
/*
* This file is part of the Disqontrol package.
*
* (c) Mediaplus.cz s.r.o. <info@mediaplus.cz>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Disqontrol\Router;

use Disqontrol\Job\JobInterface;

/**
 * A job route interface
 * 
 * A job route decides how a job should be called.
 * It can simply return a configuration entry, or it can contain a more
 * complicated logic, for example if you use queues that contain more job types.
 * 
 * An example of multi-worker queues would be e.g. queues named after priority
 * instead of their purpose - 'high, med, low'.
 * 
 * The route decision is returned in the form of a value object Directions.
 * It contains the type of the worker, its address (URL/command/worker name)
 * and more parameters if needed (arguments, HTTP headers).
 * 
 * @author Martin Schlemmer
 */
interface RouteInterface
{
    /**
     * Get worker directions based on the job
     * 
     * Worker directions contain the type of the job, its address and parameters
     * 
     * @param JobInterface $job
     *
     * @return null|WorkerDirectionsInterface Worker directions or null
     */
    public function getDirections(JobInterface $job);

    /**
     * Get the names of queues supported by this route
     *
     * @return string[] Names of supported queues
     */
    public function getSupportedQueues();

    /**
     * Can the route decide about a job going to this particular queue?
     *
     * @param string $queue
     *
     * @return bool
     */
    public function supportsQueue($queue);
}
