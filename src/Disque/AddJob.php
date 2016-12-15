<?php
/*
* This file is part of the Disqontrol package.
*
* (c) Mediaplus.cz s.r.o. <info@mediaplus.cz>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/
namespace Disqontrol\Disque;

use Disqontrol\Job\Marshaller\MarshallerInterface;
use Disque\Client;
use Psr\Log\LoggerInterface;
use Disqontrol\Job\JobInterface;
use RuntimeException;
use Disqontrol\Logger\MessageFormatter;
use Disque\Connection\Response\ResponseException;
use Disqontrol\Logger\JobLogger;

/**
 * Wrap the ADDJOB call to Disque with an easier interface
 *
 * @author Martin Schlemmer
 */
class AddJob
{
    /**
     * @var string Constants for the disque-php method Client::AddJob()
     */
    const DISQUE_ADDJOB_DELAY = 'delay';
    const DISQUE_ADDJOB_JOB_PROCESS_TIMEOUT = 'retry';
    const DISQUE_ADDJOB_JOB_LIFETIME = 'ttl';

    /**
     * @var Client A client for communicating with Disque
     */
    private $disque;

    /**
     * @var MarshallerInterface Serializer for the job body
     */
    private $marshaller;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Client                   $disque
     * @param MarshallerInterface      $marshaller
     * @param LoggerInterface          $logger
     */
    public function __construct(
        Client $disque,
        MarshallerInterface $marshaller,
        LoggerInterface $logger
    ) {
        $this->disque = $disque;
        $this->marshaller = $marshaller;
        $this->logger = $logger;
    }

    /**
     * Send a job to Disque
     *
     * @param JobInterface $job               The job to add
     * @param int          $delay             Job delay in seconds
     * @param int          $jobProcessTimeout Maximum job process time
     * @param int          $jobLifetime       Maximum job lifetime
     *
     * @return string|bool Job ID The ID assigned to the job by Disque, or false
     */
    public function add(
        JobInterface $job,
        $delay,
        $jobProcessTimeout,
        $jobLifetime
    ) {
        $errorContext[JobLogger::JOB_INDEX] = $job;

        try {
            $jobBody = $this->marshaller->marshal($job->getBodyWithMetadata());
        } catch (RuntimeException $e) {
            $this->logger->error($e->getMessage(), $errorContext);

            return false;
        }

        $queue = $job->getQueue();
        $options = [
            self::DISQUE_ADDJOB_DELAY => $delay,
            self::DISQUE_ADDJOB_JOB_PROCESS_TIMEOUT => $jobProcessTimeout,
            self::DISQUE_ADDJOB_JOB_LIFETIME => $jobLifetime
        ];

        try {
            $jobId = $this->disque->addJob(
                $queue,
                $jobBody,
                $options
            );

            $this->logger->info(
                MessageFormatter::jobAdded($jobId, $queue, $job->getOriginalId())
            );

        } catch (ResponseException $e) {
            $jobId = false;

            $this->logger->error($e->getMessage(), $errorContext);
        }

        return $jobId;
    }
}
