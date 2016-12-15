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

use Disque\Queue\BaseJob;

/**
 * @see JobInterface
 *
 * @author Martin Schlemmer
 */
class Job extends BaseJob implements JobInterface
{
    /**
     * @var array Job metadata
     */
    protected $metadata = array();
    
    /**
     * Array keys when storing the job in Disque or fetching it from there
     */
    const KEY_BODY = 'body';
    const KEY_METADATA = 'metadata';

    /**
     * Metadata keys for remembering original job information when jumping jobs
     *
     * Sometimes we have to create a new job when we would actually like to keep
     * the old one and just delay or update it. The new job has the original body,
     * it is in fact the same job from the view of the caller.
     * In Disque, however, we lose the original job ID and the retry count.
     *
     * We work around this loss of information by storing the original information
     * in the metadata of the new job.
     *
     * For a more detailed explanation
     * @see Disqontrol\Job\JobInterface::getRetryCount()
     */
    const KEY_RETRIES = 'retries';
    const KEY_ORIGINAL_ID = 'original-id';
    const KEY_CREATION_TIME = 'creation-time';
    const KEY_JOB_LIFETIME = 'job-lifetime';
    const KEY_PROCESS_TIMEOUT = 'process-timeout';

    /**
     * This is a stricter constructor requiring both the job body and its queue
     *
     * A job must always know its body and the queue it belongs to.
     * We need the queue in the Producer so let's make both properties required
     * during the construction. This will guide everyone using this class.
     *
     * All other parameters, including the ID, come later in the job's
     * lifetime and thus must stay optional.
     *
     * @param mixed  $body                 The job body
     * @param string $queue                The queue the job belongs to
     * @param string $id                   The job ID
     * @param string $queue                Name of the queue the job belongs to
     * @param int    $nacks                The number of NACKs
     * @param int    $additionalDeliveries The number of additional deliveries
     */
    public function __construct(
        $body,
        $queue,
        $id = null,
        $nacks = 0,
        $additionalDeliveries = 0
    ) {
        // Send everything except the body to the parent constructor
        $ignoredBody = null;
        parent::__construct($ignoredBody, $id, $queue, $nacks, $additionalDeliveries);

        // The body can contain metadata, so we have to set it ourselves
        $this->setBody($body);
    }
    
    /**
     * @inheritdoc
     */
    public function setMetadata($key, $value)
    {
        $this->metadata[$key] = $value;
    }
    
    /**
     * @inheritdoc
     */
    public function hasMetadata($key)
    {
        return isset($this->metadata[$key]);
    }
    
    /**
     * @inheritdoc
     */
    public function getMetadata($key)
    {
        if ( ! $this->hasMetadata($key)) {
            return null;
        }
        
        return $this->metadata[$key];
    }

    /**
     * {@inheritdoc}
     */
    public function getAllMetadata()
    {
        return $this->metadata;
    }
    
    /**
     * @inheritdoc
     */
    public function getBodyWithMetadata()
    {
        return [
            self::KEY_BODY => $this->body,
            self::KEY_METADATA => $this->metadata
        ];
    }
    
    /**
     * This method can separate the job body from the metadata
     *
     * @inheritdoc
     */
    public function setBody($body)
    {
        if ($this->isBodyWithMetadata($body)) {
            $this->body = $body[self::KEY_BODY];
            $this->metadata = $body[self::KEY_METADATA];
        } else {
            parent::setBody($body);
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function getRetryCount()
    {
        $previousRetries = $this->getMetadata(self::KEY_RETRIES);
        if (empty($previousRetries)) {
            $previousRetries = 0;
        }

        $disqueRetries = $this->getNacks() + $this->getAdditionalDeliveries();

        return ($previousRetries + $disqueRetries);
    }

    /**
     * {@inheritdoc}
     */
    public function setPreviousRetryCount($retryCount)
    {
        $this->setMetadata(self::KEY_RETRIES, (int) $retryCount);
    }

    /**
     * {@inheritdoc}
     */
    public function getOriginalId()
    {
        if ($this->hasMetadata(self::KEY_ORIGINAL_ID)) {
            return $this->getMetadata(self::KEY_ORIGINAL_ID);
        }

        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function setOriginalId($originalJobId)
    {
        $this->setMetadata(self::KEY_ORIGINAL_ID, $originalJobId);
    }

    /**
     * {@inheritdoc}
     */
    public function getCreationTime()
    {
        return $this->getMetadata(self::KEY_CREATION_TIME);
    }

    /**
     * {@inheritdoc}
     */
    public function setCreationTime($creationTime)
    {
        $this->setMetadata(self::KEY_CREATION_TIME, $creationTime);
    }

    /**
     * {@inheritdoc}
     */
    public function getJobLifetime()
    {
        return $this->getMetadata(self::KEY_JOB_LIFETIME);
    }

    /**
     * {@inheritdoc}
     */
    public function setJobLifetime($lifetime)
    {
        $this->setMetadata(self::KEY_JOB_LIFETIME, $lifetime);
    }

    /**
     * {@inheritdoc}
     */
    public function getProcessTimeout()
    {
        return $this->getMetadata(self::KEY_PROCESS_TIMEOUT);
    }

    /**
     * {@inheritdoc}
     */
    public function setProcessTimeout($timeout)
    {
        $this->setMetadata(self::KEY_PROCESS_TIMEOUT, $timeout);
    }

    /**
     * Check if the incoming job body is our body with metadata
     *
     * For a description of a job with metadata
     * @see JobInterface
     *
     * @param mixed $body
     *
     * @return bool
     */
    private function isBodyWithMetadata($body)
    {
        if (is_array($body) and count($body) === 2
            and array_key_exists(self::KEY_BODY, $body)
            and array_key_exists(self::KEY_METADATA, $body)) {
            return true;
        }

        return false;
    }
}
