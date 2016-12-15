<?php
/*
 * This file is part of the Disqontrol package.
 *
 * (c) Mediaplus.cz s.r.o. <info@mediaplus.cz>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Disqontrol\Job;

use Disque\Queue\JobInterface as DisquePhpJobInterface;

/**
 * An interface for a job supporting metadata
 *
 * There are various reasons why one would want to add metadata to a job.
 * You might want to track the time the job was added, the time it was started,
 * the time it was retried, the number of times it has failed (well, this one's
 * tracked by Disque).
 * Generally metadata can be used to keep track of any number of system states
 * at various points in the job's lifetime.
 *
 * Using this interface promises that you can add metadata to - and read it from
 * the job.
 *
 * When adding metadata, keep in mind that the contents are going to be
 * serialized and unserialized between the PHP code and Disque.
 *
 * If you need to implement Disqontrol's job with metadata yourself, the whole
 * job is a two-member associative array that looks like this:
 *
 * [
 *      'body' => $body,
 *      'metadata' => $metadata
 * ]
 * where $metadata is an associative array.
 *
 * @author Martin Schlemmer
 */
interface JobInterface extends DisquePhpJobInterface
{
    /**
     * Add a single metadata value, overwrite if the key already exists
     *
     * @param int|string $key
     * @param mixed      $value
     */
    public function setMetadata($key, $value);
    
    /**
     * Check if the metadata defined by the key exists
     *
     * @param int|string $key
     *
     * @return bool Does the metadata exist?
     */
    public function hasMetadata($key);
    
    /**
     * Return the metadata value, or null if it doesn't exist
     *
     * @param int|string $key
     *
     * @return mixed|null The metadata value
     */
    public function getMetadata($key);

    /**
     * Return all job metadata
     *
     * @return array Job metadata
     */
    public function getAllMetadata();

    /**
     * Get the job body with metadata
     *
     * The inherited method getBody() returns just the job body, without
     * metadata. When communicating with Disque, use this method instead.
     *
     * @return array Job body with metadata
     */
    public function getBodyWithMetadata();

    /**
     * Get the total number of times this job has been retried for failure or timeout
     *
     * Under normal circumstances we can calculate the number of retries
     * from the number of NACKs and AdditionalDeliveries of the job in Disque.
     * However to implement a more complex system of retries, eg. with
     * an exponential backoff (an ever longer pause between retries), we must
     * create a new job in Disque, because right now it is not possible
     * to return a job back to the queue with a delay.
     * With the creation of a new job the number of NACKS and AddDeliveries
     * is lost in Disque.
     *
     * @see https://github.com/antirez/disque/issues/170
     *
     * In order to keep track of the total number of retries, we'll make use
     * of the metadata. Whenever we retry a job by creating a new one,
     * we'll save the number of retries so far in the metadata.
     *
     * This method should understand that the number of retries is the total
     * number of the NACK and add-deliveries counters in Disque summed with
     * the number from the metadata.
     *
     * @return int
     */
    public function getRetryCount();

    /**
     * Set the number of times this job has been retried so far
     *
     * @param int $retryCount The number of retries
     */
    public function setPreviousRetryCount($retryCount);

    /**
     * Get the original job ID
     *
     * Like in the case of getRetryCount() this is another case where we lose
     * information if we jump jobs because of the inability of Disque to NACK
     * a job with a delay.
     *
     * Let's keep the original job ID for monitoring purposes.
     * If this is the first "version" of the job (it hasn't been retried before)
     * the method returns the actual job ID.
     *
     * @return string The original job ID
     */
    public function getOriginalId();

    /**
     * Set the original job ID
     *
     * @param string $originalJobId
     */
    public function setOriginalId($originalJobId);

    /**
     * Get the timestamp of the moment the job was created
     *
     * This is used together with the job lifetime to determine if a failed job
     * can be retried or if it should be moved to the failure queue.
     *
     * @return int|null
     */
    public function getCreationTime();

    /**
     * Set the time the job was created - in UNIX timestamp
     *
     * @param int $creationTime The timestamp of the moment the job was created
     */
    public function setCreationTime($creationTime);

    /**
     * Get the job lifetime as set when creating the job for the first time
     *
     * Name in Disque: TTL
     *
     * @return int|null Job lifetime in seconds
     */
    public function getJobLifetime();

    /**
     * Set the job lifetime
     *
     * @param int $lifetime
     */
    public function setJobLifetime($lifetime);

    /**
     * Get the process timeout
     *
     * Name in Disque: RETRY
     *
     * @return int|null Timeout in seconds
     */
    public function getProcessTimeout();

    /**
     * Set the process timeout
     *
     * @param int $timeout
     */
    public function setProcessTimeout($timeout);
}
