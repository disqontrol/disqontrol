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

use Disqontrol\Dispatcher\Call\Factory\CallFactoryInterface;
use Disqontrol\Event\Events;
use Disqontrol\Event\JobRouteEvent;
use Disqontrol\Exception\JobRouterException;
use Disqontrol\Job\JobInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Disqontrol\Logger\MessageFormatter;
use SplStack;

/**
 * Job router knows what worker a job should go to
 *
 * Parts of the job router include:
 * - WorkerDirections are directions to a worker (its URL, class name, command)
 * - Route knows the right directions for a set of queues
 * - Call knows how exactly a worker should be called and how the result should
 *   be interpreted
 * - CallFactory turns WorkerDirections into a Call
 *
 * The job router works in the following steps:
 *
 * 0. Register all known routes.
 *    This happens automatically when the JobRouter is created in the
 *    JobRouterFactory. The routes are parsed from the configuration.
 *    They can also be added manually at any point.
 * @see Disqontrol\Router\JobRouterFactory::addConfigurationRoutes()
 *
 * 1. Look for the right WorkerDirections
 * 1a. First dispatch a JOB_ROUTE event. If an event listener sets the
 *     WorkerDirections, go to step 2
 * 1b. If no event listener returned the WorkerDirections, ask all registered
 *     Routes whether they know the WorkerDirections for the particular queue.
 *
 * 2. Turn the WorkerDirections into a Call object
 *    This is the task of the CallFactory.
 *
 * 3. Return the Call (or throw an exception if something went wrong)
 *
 * @author Martin Schlemmer
 * @author Martin Patera <mzstic@gmail.com>
 */
class JobRouter implements JobRouterInterface
{
    /**
     * @var RouteInterface[] in SplStack (LIFO)
     * We need the collection of routes to behave like a stack (LIFO), not like
     * a queue (or a PHP array - FIFO), so that later registered routes
     * override the earlier ones.
     */
    protected $routes;

    /**
     * @var CallFactoryInterface
     */
    protected $callFactory;

    /**
     * @var EventDispatcher
     */
    protected $eventDispatcher;

    /**
     * @param CallFactoryInterface $callFactory
     * @param EventDispatcher      $eventDispatcher
     */
    public function __construct(
        CallFactoryInterface $callFactory,
        EventDispatcher $eventDispatcher
    ) {
        $this->callFactory = $callFactory;
        $this->eventDispatcher = $eventDispatcher;
        $this->routes = new SplStack();
    }

    /**
     * @inheritdoc
     */
    public function addRoute(RouteInterface $route)
    {
        $this->routes->push($route);
    }

    /**
     * @inheritdoc
     *
     * This method has three steps:
     *
     * 1. Dispatch the event and look if any event listener has set
     * WorkerDirections. If it has, use them.
     *
     * 2. If no event listener has set WorkerDirections, ask the routes.
     *
     * 3. If no route has produced WorkerDirections, we don't know what to do
     * with this job. Throw an exception and let a higher power fix it.
     */
    public function getCall(JobInterface $job)
    {
        $workerDirections = $this->getDirectionsFromEvent($job);

        if (empty($workerDirections)) {
            $workerDirections = $this->getDirectionsFromRoutes($job);
        }

        if ( ! empty($workerDirections)) {
            // Can throw a JobRouterException, in that case let it bubble up
            $workerCall = $this->callFactory->createCall($workerDirections, $job);

            return $workerCall;
        }

        throw new JobRouterException(
            MessageFormatter::jobWorkerNotFound($job->getId(), $job->getQueue(), $job->getOriginalId())
        );
    }

    /**
     * Dispatch the JOB_ROUTE event and ask it for WorkerDirections, if any
     *
     * @param JobInterface $job
     *
     * @return WorkerDirectionsInterface|null
     */
    private function getDirectionsFromEvent(JobInterface $job)
    {
        $event = new JobRouteEvent($job);
        $this->eventDispatcher->dispatch(Events::JOB_ROUTE, $event);
        $workerDirections = $event->getWorkerDirections();

        return $workerDirections;
    }

    /**
     * Ask all registered routes for WorkerDirections
     *
     * @param JobInterface $job
     *
     * @return WorkerDirectionsInterface|null
     */
    private function getDirectionsFromRoutes(JobInterface $job)
    {
        $queue = $job->getQueue();

        foreach ($this->routes as $route) {
            if ( ! $route->supportsQueue($queue)) {
                continue;
            }

            $workerDirections = $route->getDirections($job);

            if ( ! empty($workerDirections)) {
                return $workerDirections;
            }
        }

        return null;
    }
}
