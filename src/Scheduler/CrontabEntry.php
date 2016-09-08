<?php
/*
* This file is part of the Disqontrol package.
*
* (c) Webtrh s.r.o. <info@webtrh.cz>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Disqontrol\Scheduler;

use Cron\CronExpression;
use Disqontrol\Job\JobInterface;

/**
 * One row (entry) in the Disqontrol crontab describing the time and the job
 *
 * @author Martin Schlemmer
 */
class CrontabEntry
{
    /**
     * The cron expression describing when the job should run
     *
     * @var CronExpression
     */
    private $cronExpression;

    /**
     * The job that should run at the given time
     *
     * @var JobInterface
     */
    private $job;

    /**
     * CrontabEntry constructor.
     *
     * @param CronExpression $cronExpression
     * @param JobInterface   $job
     */
    public function __construct(
        CronExpression $cronExpression,
        JobInterface $job
    ) {
        $this->cronExpression = $cronExpression;
        $this->job = $job;
    }

    /**
     * @return CronExpression
     */
    public function getCronExpression()
    {
        return $this->cronExpression;
    }

    /**
     * @return JobInterface
     */
    public function getJob()
    {
        return $this->job;
    }

    public function __toString()
    {
        return $this->cronExpression->getExpression(). ' '
        . $this->job->getQueue() . ' '
        . $this->job->getBody();
    }
}
