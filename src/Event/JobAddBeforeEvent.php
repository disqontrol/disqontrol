<?php
/*
 * This file is part of the Disqontrol package.
 *
 * (c) Webtrh s.r.o. <info@webtrh.cz>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Disqontrol\Event;

use Symfony\Component\EventDispatcher\Event;
use Disqontrol\Job\JobInterface;

/**
 * Information about the JOB_ADD_BEFORE event
 *
 * Dispatched from Producer before adding a job.
 * Any changes to the properties in this event will reflect back
 * in the producer. If you change the job queue or the delay, the job will be
 * enqueued with the new values.
 *
 * @author Martin Schlemmer
 */
class JobAddBeforeEvent extends Event
{
    /**
     * @var \Disqontrol\Job\JobInterface
     */
    protected $job;

    /**
     * @var int Job delay in seconds
     */
    protected $delay;

    /**
     * @var int Maximum job process time in seconds
     */
    protected $jobProcessTimeout;

    /**
     * @var int Maximum job lifetime in seconds
     */
    protected $jobLifetime;

    /**
     * @param JobInterface $job
     * @param int          $delay
     * @param int          $jobProcessTimeout
     * @param int          $jobLifetime
     */
    public function __construct(
        JobInterface $job,
        $delay,
        $jobProcessTimeout,
        $jobLifetime
    ) {
        $this->job = $job;
        $this->setDelay($delay);
        $this->setJobProcessTimeout($jobProcessTimeout);
        $this->setJobLifetime($jobLifetime);
    }

    /**
     * Get the job to be added to Disque
     *
     * @return \Disqontrol\Job\JobInterface
     */
    public function getJob()
    {
        return $this->job;
    }

    /**
     * Get the delay of the job to be added to Disque
     *
     * @return int
     */
    public function getDelay()
    {
        return $this->delay;
    }

    /**
     * Set the delay of the job to be added to Disque
     *
     * @param int $delay
     */
    public function setDelay($delay)
    {
        $this->delay = (int) $delay;
    }

    /**
     * Get the job max process time
     *
     * @return int
     */
    public function getJobProcessTimeout()
    {
        return $this->jobProcessTimeout;
    }

    /**
     * Set the job max process time
     *
     * @param int $jobProcessTimeout
     */
    public function setJobProcessTimeout($jobProcessTimeout)
    {
        $this->jobProcessTimeout = (int) $jobProcessTimeout;
    }

    /**
     * Get the job max lifetime
     *
     * @return int
     */
    public function getJobLifetime()
    {
        return $this->jobLifetime;
    }

    /**
     * Set the job max lifetime
     *
     * @param int $jobLifetime
     */
    public function setJobLifetime($jobLifetime)
    {
        $this->jobLifetime = (int) $jobLifetime;
    }
}
