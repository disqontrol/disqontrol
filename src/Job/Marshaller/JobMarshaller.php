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
use Disqontrol\Job\Serializer\SerializerInterface;
use Disque\Command\Response\JobsResponse AS Response;
use Disque\Command\Response\JobsWithQueueResponse AS QueueResponse;
use Disque\Command\Response\JobsWithCountersResponse AS Counters;
use InvalidArgumentException;

/**
 * {@inheritdoc}
 *
 * Job Marshaller ignores exceptions thrown from the Serializer, because they
 * should be handled on a higher level. Don't forget to catch them.
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
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @param JobFactory          $jobFactory
     * @param SerializerInterface $serializer
     */
    public function __construct(
        JobFactory $jobFactory,
        SerializerInterface $serializer
    ) {
        $this->jobFactory = $jobFactory;
        $this->serializer = $serializer;
    }

    /**
     * {@inheritdoc}
     */
    public function marshal($jobBody)
    {
        return $this->serializer->serialize($jobBody);
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

        $jobBody = $this->serializer->deserialize($getJobResponse[Response::KEY_BODY]);
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
