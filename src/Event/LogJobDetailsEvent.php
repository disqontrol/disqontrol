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

/**
 * An event dispatched whenever anyone logs a message and attaches a Job object
 * Dispatched from JobLogger
 *
 * @author Martin Schlemmer
 */
class LogJobDetailsEvent extends Event
{
    /**
     * @var string The log message
     */
    private $message;

    /**
     * @var int The log level as defined in Monolog\Logger
     */
    private $level;

    /**
     * @var JobInterface The job belonging to the message
     */
    private $job;

    /**
     * @var array The whole message context
     */
    private $context;

    /**
     * @param string       $message
     * @param int          $level
     * @param JobInterface $job
     * @param array        $context
     */
    public function __construct(
        $message,
        $level,
        JobInterface $job,
        array $context = []
    ) {
        $this->message = $message;
        $this->level = $level;
        $this->job = $job;
        $this->context = $context;
    }

    /**
     * Get the log message
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Get the log level
     *
     * @return int
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * Get the job object belonging to the log message
     *
     * @return JobInterface
     */
    public function getJob()
    {
        return $this->job;
    }

    /**
     * Get the message context
     *
     * @return array
     */
    public function getContext()
    {
        return $this->context;
    }

}
