<?php
/*
* This file is part of the Disqontrol package.
*
* (c) Webtrh s.r.o. <info@webtrh.cz>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/
namespace Disqontrol\Logger;

use Disqontrol\Scheduler\CrontabEntry;

/**
 * A helper for creating log and exception messages
 *
 * @author Martin Schlemmer
 */
class MessageFormatter
{
    /**
     * Log messages
     */
    const JOB_DETAILS = 'Job %s body: %s';
    const JOB_ADDED = 'Added a job %s to the queue "%s"';
    const JOB_PROCESS_FAILURE = 'Failed to process job %s from queue "%s". %s';
    const JOB_PROCESSED = 'Job %s from the queue "%s" was successfully processed';
    const JOB_FAILED_COMPLETELY = 'Failed to process job %s from queue "%s" %d times, moved to the failure queue "%s". %s';
    const FAILED_TO_MOVE_JOB_TO_FAILURE_QUEUE = 'Failed to move job %s from queue "%s" to its failure queue "%s". The job is lost';
    const FAILED_TO_NACK = 'Failed to NACK job %s in queue "%s". %s. It will be requeued automatically after it times out (%ds).';
    const JOB_NACKED = 'NACKed job %s in queue "%s" with a delay %ds';
    const JOB_MOVED_TO_FAILURE_QUEUE = 'Moved job %s from queue "%s" to its failure queue "%s"';
    const FAILED_TO_REMOVE_JOB_FROM_SOURCE_QUEUE = 'When moving job %s from queue "%s" to queue "%s", it couldn\'t be removed from the source queue. It might exist in both queues at once.';
    const FAILED_TO_ACK = 'Failed to ACK job %s in queue "%s". %s. It will be processed again after it times out.';
    const FAILED_TO_UNMARSHAL_JOB = 'Failed to unmarshal job coming from Disque. %2$s Job data: %1$s';
    const RECEIVED_TERMINATE_SIGNAL = '%s received SIGINT/SIGTERM signal, shutting down';
    const ADDED_MISSING_TIME_DATA = 'Added missing time data to job %s: Creation time: %d, job lifetime: %d';
    const JOB_REACHED_RETRY_LIMIT = 'Job %s has reached retry limit (%d)';
    const JOB_OUT_OF_TIME = 'Job %s has run out of time (%ds)';
    const STARTING_CONSUMER_PROCESS = 'Starting a consumer process with the command: %s';
    const SUPERVISOR_SPAWNED_PROCESS_GROUP = 'Supervisor spawned a consumer process group for queues %s';
    const SUPERVISOR_SPAWNED_DEFAULT_PROCESS_GROUP = 'Supervisor spawned a default consumer process group for queues %s';
    const SCHEDULER_RUNS_JOB = 'Scheduler is running %s';
    const ISOLATED_PHP_WORKER_FAILED = 'The PHP worker %s failed when processing job %s in a separate process. %s';

    /**
     * Exception messages
     */
    const FAILED_UNMARSHAL_INCOMPLETE_RESPONSE = 'Failed to unmarshal an incomplete GETJOB response';
    const FAILED_SERIALIZE_JOB_BODY = 'Failed to serialize job body. %s';
    const FAILED_DESERIALIZE_JOB_BODY = 'Failed to deserialize job body. %s';
    const UNKNOWN_WORKER_TYPE = 'Unknown worker type %s in the configuration file';
    const JOB_WORKER_NOT_FOUND = 'Cannot find a proper worker for job %s coming from queue "%s"';
    const PHP_JOB_WORKER_NOT_FOUND = 'Cannot find a PHP worker with the name "%s"';
    const PHP_JOB_WORKER_FROM_CONFIGURATION_NOT_FOUND = 'The configuration defines a PHP worker "%s" which hasn\'t been found
in the WorkerFactoryCollection when instantiating Disqontrol.';
    const UNDEFINED_QUEUES_IN_CONSUMER_CONFIG = 'Consumers are configured for undefined queues: %s';
    const MISSING_CRONTAB_PATH = 'Add a path to the crontab file';
    const FILE_NOT_FOUND = 'The file "%s" was not found';
    const WORKER_COMMAND_MISSING_PARAMETERS = 'The %s command is missing one or more required parameters (queue, body, metadata)';

    /**
     * Helper template
     */
    const TWO_JOB_IDS = '%s (now %s)';

    /**
     * Format a message with job details
     *
     * @param string $jobId
     * @param string $serializedJobBody
     * @param string $originalJobId
     *
     * @return string A message containing job details
     */
    public static function jobDetails($jobId, $serializedJobBody, $originalJobId = '')
    {
        return sprintf(
            self::JOB_DETAILS,
            self::formatJobId($jobId, $originalJobId),
            $serializedJobBody
        );
    }

    /**
     * Added a job
     *
     * @param string $jobId
     * @param string $queue
     * @param string $originalJobId
     *
     * @return string A message about the added job
     */
    public static function jobAdded($jobId, $queue, $originalJobId = '')
    {
        return sprintf(
            self::JOB_ADDED,
            self::formatJobId($jobId, $originalJobId),
            $queue
        );
    }

    /**
     * NACKed a job
     *
     * @param string $jobId
     * @param string $queue
     * @param int    $delay
     * @param string $originalJobId
     *
     * @return string A message about the added job
     */
    public static function jobNacked($jobId, $queue, $delay, $originalJobId = '')
    {
        return sprintf(
            self::JOB_NACKED,
            self::formatJobId($jobId, $originalJobId),
            $queue,
            $delay
        );
    }

    /**
     * Failed to unmarshal an incomplete Disque response
     *
     * @return string
     */
    public static function failedUnmarshal()
    {
        return self::FAILED_UNMARSHAL_INCOMPLETE_RESPONSE;
    }

    /**
     * Failed to serialize a job body
     *
     * @param string $message
     *
     * @return string
     */
    public static function failedSerialize($message)
    {
        return sprintf(self::FAILED_SERIALIZE_JOB_BODY, $message);
    }

    /**
     * Failed to deserialize a job body
     *
     * @param string $message
     *
     * @return string
     */
    public static function failedDeserialize($message)
    {
        return sprintf(self::FAILED_DESERIALIZE_JOB_BODY, $message);
    }

    /**
     * Failed to process a job
     *
     * @param string $jobId
     * @param string $queue
     * @param string $message
     * @param string $originalJobId
     *
     * @return string
     */
    public static function failedProcessJob($jobId, $queue, $message, $originalJobId = '')
    {
        return sprintf(
            self::JOB_PROCESS_FAILURE,
            self::formatJobId($jobId, $originalJobId),
            $queue,
            $message
        );
    }

    /**
     * Job failed too many times, moving it to the failure queue
     *
     * @param string $jobId
     * @param string $queue
     * @param int    $retryCount   How many times the job was retried
     * @param string $failureQueue Where the job has been moved
     * @param string $message
     * @param string $originalJobId
     *
     * @return string
     */
    public static function givenUpOnJob($jobId, $queue, $retryCount, $failureQueue, $message, $originalJobId = '')
    {
        return sprintf(
            self::JOB_FAILED_COMPLETELY,
            self::formatJobId($jobId, $originalJobId),
            $queue,
            $retryCount,
            $failureQueue,
            $message
        );
    }

    /**
     * Moved a job to its failure queue
     *
     * @param string $jobId
     * @param string $queue
     * @param string $failureQueue Where the job has been moved
     * @param string $originalJobId
     *
     * @return string
     */
    public static function movedJobToFailureQueue($jobId, $queue, $failureQueue, $originalJobId = '')
    {
        return sprintf(
            self::JOB_MOVED_TO_FAILURE_QUEUE,
            self::formatJobId($jobId, $originalJobId),
            $queue,
            $failureQueue
        );
    }
    /**
     * Job was successfully processed
     *
     * @param string $jobId
     * @param string $queue
     * @param string $originalJobId
     *
     * @return string
     */
    public static function jobProcessed($jobId, $queue, $originalJobId = '')
    {
        return sprintf(
            self::JOB_PROCESSED,
            self::formatJobId($jobId, $originalJobId),
            $queue
        );
    }

    /**
     * Failed to NACK a job
     *
     * @param string $jobId
     * @param string $queue
     * @param string $message
     * @param int    $processTimeout
     * @param string $originalJobId
     *
     * @return string
     */
    public static function failedToNack($jobId, $queue, $message, $processTimeout, $originalJobId = '')
    {
        return sprintf(
            self::FAILED_TO_NACK,
            self::formatJobId($jobId, $originalJobId),
            $queue,
            $message,
            $processTimeout
        );
    }

    /**
     * Failed to move the job to its failure queue. The job is lost.
     *
     * @param string $jobId
     * @param string $queue
     * @param string $failureQueue
     * @param string $originalJobId
     *
     * @return string
     */
    public static function failedToMoveJobToFailureQueue($jobId, $queue, $failureQueue, $originalJobId = '')
    {
        return sprintf(
            self::FAILED_TO_MOVE_JOB_TO_FAILURE_QUEUE,
            self::formatJobId($jobId, $originalJobId),
            $queue,
            $failureQueue
        );
    }

    /**
     * Failed to remove job from the source queue when moving it
     *
     * @param string $jobId
     * @param string $sourceQueue
     * @param string $targetQueue
     * @param string $originalJobId
     *
     * @return string
     */
    public static function failedToRemoveJobFromSourceQueue(
        $jobId,
        $sourceQueue,
        $targetQueue,
        $originalJobId = ''
    ) {
        return sprintf(
            self::FAILED_TO_REMOVE_JOB_FROM_SOURCE_QUEUE,
            self::formatJobId($jobId, $originalJobId),
            $sourceQueue,
            $targetQueue
        );
    }

    /**
     * Unknown worker type in the configuration
     *
     * @param string $type
     *
     * @return string
     */
    public static function unknownWorkerType($type)
    {
        return sprintf(self::UNKNOWN_WORKER_TYPE, $type);
    }

    /**
     * Job worker not found in the job router
     *
     * @param string $jobId
     * @param string $queue
     * @param string $originalJobId
     *
     * @return string
     */
    public static function jobWorkerNotFound($jobId, $queue, $originalJobId = '')
    {
        return sprintf(
            self::JOB_WORKER_NOT_FOUND,
            self::formatJobId($jobId, $originalJobId),
            $queue
        );
    }

    /**
     * A PHP worker was not found in the WorkerFactoryCollection
     *
     * @param string $workerName
     *
     * @return string
     */
    public static function phpJobWorkerNotFound($workerName)
    {
        return sprintf(self::PHP_JOB_WORKER_NOT_FOUND, $workerName);
    }

    /**
     * A PHP worker that is defined in the config was not registered during startup
     *
     * @param string $workerName
     *
     * @return string
     */
    public static function phpJobWorkerFromConfigurationNotFound($workerName)
    {
        return sprintf(self::PHP_JOB_WORKER_FROM_CONFIGURATION_NOT_FOUND, $workerName);
    }

    /**
     * Failed to NACK a job
     *
     * @param string $jobId
     * @param string $queue
     * @param string $message
     * @param string $originalJobId
     *
     * @return string
     */
    public static function failedToAck($jobId, $queue, $message, $originalJobId = '')
    {
        return sprintf(
            self::FAILED_TO_NACK,
            self::formatJobId($jobId, $originalJobId),
            $queue,
            $message
        );
    }

    /**
     * Failed to unmarshal job data coming from Disque
     *
     * @param string $jobData
     * @param string $reason
     *
     * @return string
     */
    public static function failedToUnmarshalJob($jobData, $reason = null)
    {
        return sprintf(self::FAILED_TO_UNMARSHAL_JOB, $jobData, $reason);
    }

    /**
     * Received a signal to terminate, shutting down
     *
     * @param string $receiver Who received the signal?
     *
     * @return string
     */
    public static function receivedTerminateSignal($receiver = 'Consumer')
    {
        return sprintf(self::RECEIVED_TERMINATE_SIGNAL, $receiver);
    }

    /**
     * Added missing job time data to a job
     *
     * @param string $jobId
     * @param string $creationTime
     * @param string $jobLifetime
     * @param string $originalJobId
     *
     * @return string
     */
    public static function addedJobTimeData($jobId, $creationTime, $jobLifetime, $originalJobId = '')
    {
        return sprintf(
            self::ADDED_MISSING_TIME_DATA,
            self::formatJobId($jobId, $originalJobId),
            $creationTime,
            $jobLifetime
        );
    }

    /**
     * Job has reached its retry limit
     *
     * @param string $jobId
     * @param int    $retries
     * @param string $originalJobId
     *
     * @return string
     */
    public static function jobReachedRetryLimit($jobId, $retries, $originalJobId = '')
    {
        return sprintf(
            self::JOB_REACHED_RETRY_LIMIT,
            self::formatJobId($jobId, $originalJobId),
            $retries
        );
    }

    /**
     * Job is out of time (its lifetime is up)
     *
     * @param string $jobId
     * @param int    $lifetime
     * @param string $originalJobId
     *
     * @return string
     */
    public static function jobOutOfTime($jobId, $lifetime, $originalJobId = '')
    {
        return sprintf(
            self::JOB_OUT_OF_TIME,
            self::formatJobId($jobId, $originalJobId),
            $lifetime
        );
    }

    /**
     * There are consumers configured for undefined queues
     *
     * @param array $queues
     *
     * @return string
     */
    public static function undefinedQueuesInConsumerConfig(array $queues)
    {
        return sprintf(
            self::UNDEFINED_QUEUES_IN_CONSUMER_CONFIG,
            implode(', ', $queues)
        );
    }

    /**
     * Starting a consumer process
     *
     * @param string $cmd The consumer command
     *
     * @return string
     */
    public static function startingConsumerProcess($cmd)
    {
        return sprintf(self::STARTING_CONSUMER_PROCESS, $cmd);
    }

    /**
     * Supervisor spawned a consumer process group
     *
     * @param string[] $queues
     *
     * @return string
     */
    public static function supervisorSpawnedProcessGroup(array $queues)
    {
        $queues = implode(', ', $queues);
        return sprintf(self::SUPERVISOR_SPAWNED_PROCESS_GROUP, $queues);
    }

    /**
     * Supervisor spawned a default consumer process group
     *
     * @param string[] $queues
     *
     * @return string
     */
    public static function supervisorSpawnedDefaultProcessGroup(array $queues)
    {
        $queues = implode(', ', $queues);
        return sprintf(self::SUPERVISOR_SPAWNED_DEFAULT_PROCESS_GROUP, $queues);
    }

    /**
     * Scheduler runs a job
     *
     * @param CrontabEntry|string $crontabEntry
     *
     * @return string
     */
    public static function schedulerRunsJob($crontabEntry)
    {
        return sprintf(self::SCHEDULER_RUNS_JOB, $crontabEntry);
    }

    /**
     * Missing the path to the crontab file
     *
     * @return string
     */
    public static function missingCrontabPath()
    {
        return self::MISSING_CRONTAB_PATH;
    }

    /**
     * File not found
     *
     * @param string $path
     *
     * @return string
     */
    public static function fileNotFound($path)
    {
        return sprintf(self::FILE_NOT_FOUND, $path);
    }

    /**
     * The isolated PHP worker failed when processing a job
     *
     * @param string $workerName
     * @param string $jobId
     * @param string $errMsg
     *
     * @return string
     */
    public static function isolatedPhpWorkerFailed($workerName, $jobId, $errMsg)
    {
        return sprintf(
            self::ISOLATED_PHP_WORKER_FAILED,
            $workerName,
            $jobId,
            $errMsg
        );
    }

    /**
     * The worker command is missing parameters queue, body, metadata
     *
     * @param string $commandName
     *
     * @return string
     */
    public static function workerCommandMissingParameters($commandName)
    {
        return sprintf(self::WORKER_COMMAND_MISSING_PARAMETERS, $commandName);
    }

    /**
     * Format a job ID for the log message
     *
     * Because of a missing Disque functionality, jobs can change their IDs.
     * @see explanation for Disqontrol\Job\Job::KEY_ORIGINAL_ID
     * In that case we want to log both the original as well as the current ID.
     *
     * This method takes care of formatting the ID (or IDs) for all messages.
     * It is written in such a way that if Disque ever starts supporting the
     * missing features and jobs retain their IDs, all method signatures can
     * stay the same and the log messages will automatically start showing just
     * the one ID as intended.
     *
     * @param string $currentJobId
     * @param string $originalJobId
     *
     * @return string
     */
    private static function formatJobId($currentJobId, $originalJobId = '')
    {
        if (empty($originalJobId) or $currentJobId === $originalJobId) {
            return $currentJobId;
        }

        return sprintf(self::TWO_JOB_IDS, $originalJobId, $currentJobId);
    }
}
