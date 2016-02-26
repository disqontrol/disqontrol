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

    /**
     * Exception messages
     */
    const FAILED_UNMARSHAL_INCOMPLETE_RESPONSE = 'Failed to unmarshal an incomplete GETJOB response';
    const FAILED_SERIALIZE_JOB_BODY = 'Failed to serialize job body. %s';
    const FAILED_DESERIALIZE_JOB_BODY = 'Failed to deserialize job body. %s';

    /**
     * Format a message with job details
     *
     * @param string $jobId
     * @param string $serializedJobBody
     *
     * @return string A message containing job details
     */
    public static function jobDetails($jobId, $serializedJobBody)
    {
        return sprintf(self::JOB_DETAILS, $jobId, $serializedJobBody);
    }

    /**
     * Added a job
     *
     * @param string $jobId
     * @param string $queue
     *
     * @return string A message about the added job
     */
    public static function jobAdded($jobId, $queue)
    {
        return sprintf(self::JOB_ADDED, $jobId, $queue);
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
}


