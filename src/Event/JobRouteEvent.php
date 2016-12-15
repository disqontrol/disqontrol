<?php
/*
 * This file is part of the Disqontrol package.
 *
 * (c) Mediaplus.cz s.r.o. <info@mediaplus.cz>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Disqontrol\Event;

use Disqontrol\Job\JobInterface;
use Symfony\Component\EventDispatcher\Event;
use Disqontrol\Router\WorkerDirectionsInterface;

/**
 * Information about the JOB_ROUTE event dispatched from JobRouter
 *
 * If any listener sets WorkerDirections in the event, JobRouter won't call
 * any Routes anymore and will use these directions immediately, instead.
 *
 * @author Martin Patera <mzstic@gmail.com>
 * @author Martin Schlemmer
 */
class JobRouteEvent extends Event
{
    /**
     * Job that is to be routed
     *
     * @var JobInterface
     */
    private $job;

    /**
     * WorkerDirections set by an event listener
     *
     * @var WorkerDirectionsInterface|null
     */
    private $workerDirections;

    /**
     * @param JobInterface $job
     */
    public function __construct(JobInterface $job)
    {
        $this->job = $job;
    }

    /**
     * Get the job that will be routed
     *
     * @return JobInterface
     */
    public function getJob()
    {
        return $this->job;
    }

    /**
     * Set WorkerDirections for use by the JobRouter
     *
     * @param WorkerDirectionsInterface $directions
     */
    public function setWorkerDirections(WorkerDirectionsInterface $directions)
    {
        $this->workerDirections = $directions;
    }

    /**
     * Get the WorkerDirections (if any) set by an event listener
     *
     * @return WorkerDirectionsInterface|null
     */
    public function getWorkerDirections()
    {
        return $this->workerDirections;
    }
}
