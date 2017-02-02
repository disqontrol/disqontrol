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
use Psr\Log\LoggerInterface;
use Revisor\Trend\TrendCalculator;

/**
 * A factory for autoscaling algorithms used in ConsumerProcessGroups
 *
 * @author Martin Schlemmer
 */
class AutoscaleAlgorithmFactory
{
    /**
     * @var Client
     */
    private $disque;
    
    /**
     * @var LoggerInterface
     */
    private $logger;
    
    /**
     * @var TrendCalculator
     */
    private $trendCalculator;
    
    /**
     * @param Client          $disque
     * @param TrendCalculator $trendCalculator
     * @param LoggerInterface $logger
     */
    public function __construct(
        Client $disque,
        TrendCalculator $trendCalculator,
        LoggerInterface $logger
    ) {
        $this->disque = $disque;
        $this->logger = $logger;
        $this->trendCalculator = $trendCalculator;
    }
    
    /**
     * Create an algorithm that always returns a constant number
     *
     * @param int $processCount The number of processes the algorithm returns
     *
     * @return ConstantProcessCount
     */
    public function createConstantAlgorithm($processCount)
    {
        return new ConstantProcessCount($processCount);
    }
    
    /**
     * @param array $queues
     * @param int   $minProcessCount
     * @param int   $maxProcessCount
     * 
     * @return PredictiveAutoscaling
     */
    public function createPredictiveAlgorithm(
        array $queues,
        $minProcessCount,
        $maxProcessCount
    ) {
        $emptyMeasurements = new Measurements($this->trendCalculator);
        
        return new PredictiveAutoscaling(
            $queues,
            $minProcessCount,
            $maxProcessCount,
            $this->disque,
            $emptyMeasurements,
            $this->logger
        );
    }
}
