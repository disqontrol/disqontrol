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

use Disqontrol\Job\JobInterface;
use Disqontrol\Dispatcher\Call\WorkerCallInterface;

/**
 * A job router interface
 * 
 * Job router decides to what worker the job should go and how the worker
 * should be called.
 *
 * It has a collection of Routes indexed by the queue name. When a job arrives,
 * from a queue, the corresponding Route is asked for a decision about the worker.
 * 
 * The final product of a Route is a value object Directions, containing the type
 * of the call and all parameters necessary to make the actual call - URL and
 * its HTTP method, or a command, or a worker name etc.
 *
 * The router then turns the Directions into a WorkerCall object, which can
 * make the actual call and understands what constitutes success or failure.
 * 
 * @author Martin Schlemmer
 */
interface JobRouterInterface
{
    /**
     * Set a route for a queue
     * 
     * Queues are identified by their names (eg. 'email-registration').
     * There can be only one route for a queue. The function overwrites a Route
     * if one for the given queue name already exists.
     * 
     * @param string         $queueName The name of the queue the route is meant for 
     * @param RouteInterface $route     The route which decides the worker responsible
     */
    public function setRoute($queueName, RouteInterface $route);
    
    /**
     * Decides how the job should be called
     * 
     * The return value is a call with all the necessary parameters and
     * dependencies just ready to go - but not called yet.
     * 
     * @param JobInterface $job The job that should be routed to a worker
     *
     * @return WorkerCallInterface Full description of the worker call
     */
    public function getCall(JobInterface $job);
}
