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
use Disqontrol\Disque\FailJob;
use Vivait\Backoff\Strategies\ExponentialBackoffStrategy;

/**
 * This failure handler retries all failed jobs with an ever longer delay
 *
 * Exponential backoff is an error handling strategy for network applications.
 * https://cloud.google.com/storage/docs/exponential-backoff
 *
 * Using it in job error handling was inspired by a similar functionality in Sidekiq
 * https://github.com/mperham/sidekiq/wiki/Error-Handling#automatic-job-retry
 *
 * This failure strategy will retry the job
 * - 2 times in the first minute
 * - 5 times in the first hour
 * - 7 times in the first 24 hours
 * - following about one retry a day
 * - for a total of 37 retries in 30 days
 *
 * Because Disque doesn't support NACK with delay right now, we must ACK
 * the job as if it were successful and create a new one with the same parameters.
 *
 * In order to remember the total number of retries, we'll use the job metadata.
 *
 * @author Martin Schlemmer
 */
class RetryWithExponentialBackoff implements FailureStrategyInterface
{
    /**
     * @var FailJob
     */
    private $failJob;

    /**
     * A class for calculating the exponential backoff
     *
     * @var ExponentialBackoffStrategy
     */
    private $backoff;

    /**
     * @param FailJob                    $failJob
     * @param ExponentialBackoffStrategy $backoff
     */
    public function __construct(
        FailJob $failJob,
        ExponentialBackoffStrategy $backoff
    ) {
        $this->failJob = $failJob;
        $this->backoff = $backoff;
    }

    /**
     * {@inheritdoc}
     */
    public function handleFailure(CallInterface $call)
    {
        $job = $call->getJob();
        $errorMessage = $call->getErrorMessage();
        $this->failJob->logError($job, $errorMessage);

        $retries = $job->getRetryCount();
        $delay = $this->backoff->getDelay($retries);

        $this->failJob->nack($job, $delay);
    }
}
