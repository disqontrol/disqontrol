<?php
/*
 * This file is part of the Disqontrol package.
 *
 * (c) Webtrh s.r.o. <info@webtrh.cz>
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
     * Get the job body with metadata
     *
     * The inherited method getBody() returns just the job body, without
     * metadata. When communicating with Disque, use this method instead.
     *
     * @return array Job body with metadata
     */
    public function getBodyWithMetadata();
}
