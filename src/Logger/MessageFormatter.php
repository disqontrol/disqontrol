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
    const JOB_FAILED_COMPLETELY = 'Failed to process job %s from queue "%s" %i times, moved to the failure queue "%s". %s';
    const FAILED_TO_MOVE_JOB_TO_FAILURE_QUEUE = 'Failed to move job %s from queue "%s" to its failure queue "%s". The job is lost';
    const FAILED_TO_NACK = 'Failed to NACK job %s in queue %s. %s. It will be requeued automatically after it times out (%is).';
    const JOB_NACKED = 'NACKed job %s in queue %s';
    const JOB_MOVED_TO_FAILURE_QUEUE = 'Moved job %s from queue "%s" to its failure queue "%s"';
    const FAILED_TO_REMOVE_JOB_FROM_SOURCE_QUEUE = 'When moving job %s from queue "%s" to queue "%s", it couldn\'t be removed from the source queue. It might exist in both queues at once.';
    const FAILED_TO_ACK = 'Failed to ACK job %s in queue %s. %s. It will be processed again after it times out.';

    /**
     * Exception messages
     */
    const FAILED_UNMARSHAL_INCOMPLETE_RESPONSE = 'Failed to unmarshal an incomplete GETJOB response';
    const FAILED_SERIALIZE_JOB_BODY = 'Failed to serialize job body. %s';
    const FAILED_DESERIALIZE_JOB_BODY = 'Failed to deserialize job body. %s';
    const UNKNOWN_WORKER_TYPE = 'Unknown worker type %s in the configuration file';
    const JOB_WORKER_NOT_FOUND = 'Cannot find a proper worker for job %s coming from queue "%s"';
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
     * @param string $originalJobId
     *
     * @return string A message about the added job
     */
    public static function jobNacked($jobId, $queue, $originalJobId = '')
    {
        return sprintf(
            self::JOB_NACKED,
            self::formatJobId($jobId, $originalJobId),
            $queue
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
