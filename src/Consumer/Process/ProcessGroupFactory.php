<?php
/*
* This file is part of the Disqontrol package.
*
* (c) Webtrh s.r.o. <info@webtrh.cz>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Disqontrol\Consumer\Process;

use Disqontrol\Consumer\Autoscale\AutoscaleAlgorithmFactory;
use Psr\Log\LoggerInterface;

/**
 * Factory for creating a group of consumer processes
 *
 * @author Martin Schlemmer
 */
class ProcessGroupFactory
{
    /**
     * @var ConsumerProcessSpawner
     */
    private $processSpawner;

    /**
     * @var AutoscaleAlgorithmFactory
     */
    private $autoscaleAlgorithmFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ConsumerProcessSpawner    $processSpawner
     * @param AutoscaleAlgorithmFactory $autoscaleAlgorithmFactory
     * @param LoggerInterface           $logger
     */
    public function __construct(
        ConsumerProcessSpawner $processSpawner,
        AutoscaleAlgorithmFactory $autoscaleAlgorithmFactory,
        LoggerInterface $logger
    ) {
        $this->processSpawner = $processSpawner;
        $this->autoscaleAlgorithmFactory = $autoscaleAlgorithmFactory;
        $this->logger = $logger;
    }

    /**
     * Instantiate a new ConsumerProcessGroup
     *
     * @param array $queues
     * @param int   $minProcessCount
     * @param int   $maxProcessCount
     * @param int   $jobBatch
     *
     * @return ConsumerProcessGroup
     */
    public function create(
        array $queues,
        $minProcessCount,
        $maxProcessCount,
        $jobBatch
    ) {
        $maxProcessCount = max($minProcessCount, $maxProcessCount);
        $minProcessCount = $this->atLeastOne($minProcessCount);
        $maxProcessCount = $this->atLeastOne($maxProcessCount);
        $jobBatch = $this->atLeastOne($jobBatch);

        // TODO: Allow the user to choose an autoscale algorithm
        $autoscaleAlgorithm = $this->autoscaleAlgorithmFactory
            ->createConstantAlgorithm($minProcessCount);

        return new ConsumerProcessGroup(
            $queues,
            $minProcessCount,
            $maxProcessCount,
            $jobBatch,
            $this->processSpawner,
            $autoscaleAlgorithm,
            $this->logger
        );
    }

    /**
     * Ensure that the value is an integer with a value of at least 1
     *
     * @param mixed $value
     *
     * @return int $value
     */
    private function atLeastOne($value)
    {
        return max(1, (int)$value);
    }
}
