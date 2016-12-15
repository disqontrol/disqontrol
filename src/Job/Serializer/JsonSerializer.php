<?php
/*
* This file is part of the Disqontrol package.
*
* (c) Mediaplus.cz s.r.o. <info@mediaplus.cz>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Disqontrol\Job\Serializer;

use Disqontrol\Logger\MessageFormatter;
use Webmozart\Json\JsonEncoder;
use Webmozart\Json\JsonDecoder;
use Exception;
use InvalidArgumentException;

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
            throw new InvalidArgumentException(
                MessageFormatter::failedSerialize($e->getMessage())
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
            throw new InvalidArgumentException(
                MessageFormatter::failedDeserialize($e->getMessage())
            );
        }

        return $deserializedBody;
    }

}
