<?php
/*
 * This file is part of the Disqontrol package.
 *
 * (c) Webtrh s.r.o. <info@webtrh.cz>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Disqontrol\Job\Marshaller;

use Disqontrol\Job\JobFactory;
use Disque\Command\Response\JobsResponse AS Response;
use Disque\Command\Response\JobsWithQueueResponse AS QueueResponse;
use Disque\Command\Response\JobsWithCountersResponse AS Counters;
use Webmozart\Json\JsonEncoder;
use Webmozart\Json\JsonDecoder;
use Exception;
use RuntimeException;
use InvalidArgumentException;

/**
 * {@inheritdoc}
 *
 * @author Martin Schlemmer
 */
class JobMarshaller implements MarshallerInterface
{
    /**
     * @var JobFactory
     */
    private $jobFactory;

    /**
     * @var JsonEncoder
     */
    protected $encoder;

    /**
     * @var JsonDecoder
     */
    protected $decoder;

    /**
     * @param JobFactory $jobFactory
     */
    public function __construct(JobFactory $jobFactory)
    {
        $this->jobFactory = $jobFactory;
        $this->encoder = new JsonEncoder();
        $this->decoder = new JsonDecoder();
        $this->decoder->setObjectDecoding(JsonDecoder::ASSOC_ARRAY);
    }

    /**
     * {@inheritdoc}
     */
    public function marshal($jobBody)
    {
        return $this->serialize($jobBody);
    }

    /**
     * {@inheritdoc}
     */
    public function unmarshal(array $getJobResponse)
    {
        if ( ! $this->disqueResponseIsValid($getJobResponse)) {
            throw new InvalidArgumentException(
                'Cannot unmarshal an incomplete GETJOB response'
            );
        }

        $jobBody = $this->deserialize($getJobResponse[Response::KEY_BODY]);
        $queue = $getJobResponse[QueueResponse::KEY_QUEUE];
        $jobId = $getJobResponse[Response::KEY_ID];

        $nacks = isset($getJobResponse[Counters::KEY_NACKS])
            ? $getJobResponse[Counters::KEY_NACKS] : 0;
        $additionalDeliveries = isset($getJobResponse[Counters::KEY_ADDITIONAL_DELIVERIES])
            ? $getJobResponse[Counters::KEY_ADDITIONAL_DELIVERIES] : 0;

        return $this->jobFactory->createJobFromDisque(
            $jobBody,
            $queue,
            $jobId,
            $nacks,
            $additionalDeliveries
        );
    }

    /**
     * Serialize a job body for Disque
     *
     * @param array|string $jobBody
     *
     * @return string Serialized job body
     *
     * @throws RuntimeException
     */
    protected function serialize($jobBody)
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
     * Deserialize a job body from Disque
     *
     * @param string $jobBody
     *
     * @return array|string Deserialized job body
     *
     * @throws RuntimeException
     */
    protected function deserialize($jobBody)
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

    /**
     * Check if the response from the Disque GETJOB command is valid
     *
     * The array must have all the basic keys - Job ID, body and queue.
     *
     * @param array $disqueResponse
     *
     * @return bool
     */
    private function disqueResponseIsValid($disqueResponse)
    {
        if ( ! empty($disqueResponse[Response::KEY_ID])
            and ! empty($disqueResponse[Response::KEY_BODY])
            and ! empty($disqueResponse[QueueResponse::KEY_QUEUE])
        ) {
            return true;
        }

        return false;
    }
}
