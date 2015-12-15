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
