<?php
/*
* This file is part of the Disqontrol package.
*
* (c) Webtrh s.r.o. <info@webtrh.cz>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Disqontrol\Job\Serializer;

use Webmozart\Json\JsonEncoder;
use Webmozart\Json\JsonDecoder;
use Exception;
use RuntimeException;

/**
 * {@inheritdoc}
 */
class JsonSerializer implements SerializerInterface {
    /**
     * @var JsonEncoder
     */
    protected $encoder;

    /**
     * @var JsonDecoder
     */
    protected $decoder;

    public function __construct()
    {
        $this->encoder = new JsonEncoder();
        $this->decoder = new JsonDecoder();
        $this->decoder->setObjectDecoding(JsonDecoder::ASSOC_ARRAY);
    }

    /**
     * {@inheritdoc}
     */
    public function serialize($jobBody)
    {
        try {
            $serializedBody = $this->encoder->encode($jobBody);
        } catch (Exception $e) {
            throw new RuntimeException(
                'Could not serialize job body. ' . $e->getMessage()
            );
        }

        return $serializedBody;
    }

    /**
     * {@inheritdoc}
     */
    public function deserialize($jobBody)
    {
        try {
            $deserializedBody = $this->decoder->decode($jobBody);
        } catch (Exception $e) {
            throw new RuntimeException(
                'Could not deserialize job body. ' . $e->getMessage()
            );
        }

        return $deserializedBody;
    }

}
