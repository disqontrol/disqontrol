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

use Disqontrol\Producer\Producer;
use Psr\Log\LoggerInterface;
use Disqontrol\Logger\MessageFormatter as Msg;

/**
 * Check if any jobs from the Disqontrol crontab are due, and run them
 *
 * @author Martin Schlemmer
 */
class Scheduler
{
    /**
     * @var CrontabParser
     */
    private $crontabParser;

    /**
     * @var Producer
     */
    private $producer;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Scheduler constructor.
     *
     * @param CrontabParser   $crontabParser
     * @param Producer        $producer
     * @param LoggerInterface $logger
     */
    public function __construct(
        CrontabParser $crontabParser,
        Producer $producer,
        LoggerInterface $logger
    ) {
        $this->crontabParser = $crontabParser;
        $this->producer = $producer;
        $this->logger = $logger;
    }

    /**
     * Parse the crontab and add any jobs that must run now
     *
     * @param string $crontab
     */
    public function scheduleJobs($crontab)
    {
        $crontabEntries = $this->crontabParser->parse($crontab);

        foreach ($crontabEntries as $crontabEntry) {
            if ($crontabEntry->getCronExpression()->isDue()) {
                $this->logger->debug(Msg::schedulerRunsJob($crontabEntry));
                $this->producer->add($crontabEntry->getJob());
            }
        }
    }
}
