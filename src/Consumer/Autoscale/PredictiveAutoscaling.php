<?php
/*
* This file is part of the Disqontrol package.
*
* (c) Mediaplus.cz s.r.o. <info@mediaplus.cz>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Disqontrol\Consumer\Autoscale;

use Disque\Client;

/**
 * Watch the number of jobs and predict the required number of consumer processes
 *
 * @author Martin Schlemmer
 */
class PredictiveAutoscaling implements AutoscaleAlgorithmInterface
{
    /**
     * @var string[] Names of the queues the consumer group listens to
     */
    private $queues;

    /**
     * @var int Minimum number of consumer processes in the group
     */
    private $minProcessCount;

    /**
     * @var int Maximum number of consumer processes in the group
     */
    private $maxProcessCount;

    /**
     * @var Client
     */
    private $disque;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var array Measurements
     * [(string)queues => [timestamp => jobCount, ...]]
     */
    private $measurements = array();

    public function __construct(
        array $queues,
        $minProcessCount,
        $maxProcessCount,
        Client $disque,
        LoggerInterface $logger
    ) {
        // Assignments
        $this->queues = $queues;
        $this->minProcessCount = $minProcessCount;
        $this->maxProcessCount = $maxProcessCount;

        $this->disque = $disque;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function calculateProcessCount($currentProcessCount)
    {
        // @todo Implement the prediction
        return $this->minProcessCount;
    }

}
