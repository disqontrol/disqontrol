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
 * Information about the JOB_ADD_AFTER event
 *
 * Dispatched from Producer after adding a job
 *
 * @author Martin Schlemmer
 */
class JobAddAfterEvent extends Event
{
    /**
     * @var \Disqontrol\Job\JobInterface
     */
    protected $job;

    /**
     * @var bool Result of adding the job
     */
    protected $result;

    /**
     * @param JobInterface $job
     */
    public function __construct(JobInterface $job)
    {
        $this->job = $job;
    }

    /**
     * Get the job added to Disque
     *
     * @return \Disqontrol\Job\JobInterface
     */
    public function getJob()
    {
        return $this->job;
    }

    /**
     * Get the result of adding the job to Disque
     *
     * @return boolean
     */
    public function getResult()
    {
        return $this->result;
    }
}
