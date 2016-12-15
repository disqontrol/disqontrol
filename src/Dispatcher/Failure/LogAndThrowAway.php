<?php
/*
* This file is part of the Disqontrol package.
*
* (c) Mediaplus.cz s.r.o. <info@mediaplus.cz>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/
namespace Disqontrol\Dispatcher\Failure;

use Disqontrol\Dispatcher\Call\CallInterface;
use Psr\Log\LoggerInterface;
use Disqontrol\Logger\JobLogger;
use Disqontrol\Logger\MessageFormatter as Msg;

/**
 * Log the failure and throw away the job, don't move it to a failure queue
 *
 * This failure handler is meant for synchronous job dispatching. If the job
 * doesn't succeed, there is no reason to retry it.
 *
 * @author Martin Schlemmer
 */
class LogAndThrowAway implements FailureStrategyInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
    /**
     * {@inheritdoc}
     */
    public function handleFailure(CallInterface $call)
    {
        $job = $call->getJob();
        $errorMessage = $call->getErrorMessage();
        $context[JobLogger::JOB_INDEX] = $job;
        $this->logger->error(
            Msg::failedProcessJob(
                $job->getId(),
                $job->getQueue(),
                $errorMessage,
                $job->getOriginalId()
            ),
            $context
        );

        // Do not ACK or NACK the job. This strategy should be used only
        // in the synchronous mode, where there is no connection to Disque at all.
    }
}
