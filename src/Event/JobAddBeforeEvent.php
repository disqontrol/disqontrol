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

use Symfony\Component\EventDispatcher\Event;
use Disqontrol\Job\JobInterface;

/**
 * Information about the JOB_ADD_BEFORE event
 *
 * Dispatched from Producer before adding a new job.
 * Any changes to the properties in this event will reflect back
 * in the producer. If you change the job properties or the delay, the job will
 * be enqueued with the new values.
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
     * @param JobInterface $job
     * @param int          $delay
     */
    public function __construct(JobInterface $job, $delay) {
        $this->job = $job;
        $this->setDelay($delay);
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
}
