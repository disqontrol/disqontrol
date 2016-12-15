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
use Disqontrol\Dispatcher\Call\CallInterface;
use Disqontrol\Exception\JobRouterException;

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
 * The router then turns the Directions into a Call object, which can
 * make the actual call and understands what constitutes success or failure.
 * 
 * @author Martin Schlemmer
 */
interface JobRouterInterface
{
    /**
     * Add a route to the job router
     * 
     * Routes report the queues they support by themselves. Thus there can be
     * multiple routes for one job queue. In that case the router will ask
     * all of them until it gets the worker directions.
     *
     * Later added routes will be asked first.
     *
     * @param RouteInterface $route The route which decides the worker responsible
     */
    public function addRoute(RouteInterface $route);
    
    /**
     * Decides how the job should be called
     * 
     * The return value is a call with all the necessary parameters and
     * dependencies just ready to go - but not called yet.
     * 
     * @param JobInterface $job The job that should be routed to a worker
     *
     * @return CallInterface Full description of the worker call
     *
     * @throws JobRouterException If a worker couldn't be assigned
     */
    public function getCall(JobInterface $job);
}
