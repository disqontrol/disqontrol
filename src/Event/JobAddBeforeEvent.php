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
    protected $maxJobProcessTime;

    /**
     * @var int Maximum job lifetime in seconds
     */
    protected $maxJobLifetime;

    public function __construct(
        JobInterface $job,
        $delay,
        $maxJobProcessTime,
        $maxJobLifetime
    ) {
        $this->job = $job;
        $this->setDelay($delay);
        $this->setMaxJobProcessTime($maxJobProcessTime);
        $this->setMaxJobLifetime($maxJobLifetime);
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
    public function getMaxJobProcessTime()
    {
        return $this->maxJobProcessTime;
    }

    /**
     * Set the job max process time
     *
     * @param int $maxJobProcessTime
     */
    public function setMaxJobProcessTime($maxJobProcessTime)
    {
        $this->maxJobProcessTime = (int) $maxJobProcessTime;
    }

    /**
     * Get the job max lifetime
     *
     * @return int
     */
    public function getMaxJobLifetime()
    {
        return $this->maxJobLifetime;
    }

    /**
     * Set the job max lifetime
     *
     * @param int $maxJobLifetime
     */
    public function setMaxJobLifetime($maxJobLifetime)
    {
        $this->maxJobLifetime = (int) $maxJobLifetime;
    }
}
