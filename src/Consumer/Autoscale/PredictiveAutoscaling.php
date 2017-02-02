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
use Disqontrol\Logger\MessageFormatter as Msg;

/**
 * Watch the number of jobs and predict the required number of consumer processes
 *
 * @author Martin Schlemmer
 */
class PredictiveAutoscaling implements AutoscaleAlgorithmInterface
{
    /**
     * The maximum measurement age in seconds
     * 
     * Older measurements will be discarded
     */
    const MAX_MEASUREMENT_AGE = 300;
    
    /**
     * The time span for the short trend calculation in seconds
     */
    const SHORT_TREND_SPAN = 60;
    
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
     * @var Measurements
     */
    private $measurements;
    
    /**
     * @var int The first planned calculation time (UNIX timestamp)
     */
    private $firstTrendCalculationTime = 0;
    
    /**
     * @var int The last time the trend was calculated (UNIX timestamp)
     */
    private $lastTrendCalculationTime = 0;
    
    /**
     * @param array           $queues
     * @param int             $minProcessCount
     * @param int             $maxProcessCount
     * @param Client          $disque
     * @param Measurements    $emptyMeasurements
     * @param LoggerInterface $logger
     */
    public function __construct(
        array $queues,
        $minProcessCount,
        $maxProcessCount,
        Client $disque,
        Measurements $emptyMeasurements,
        LoggerInterface $logger
    ) {
        $this->queues = $queues;
        $this->minProcessCount = $minProcessCount;
        $this->maxProcessCount = $maxProcessCount;
        
        $this->disque = $disque;
        $this->measurements = $emptyMeasurements;
        $this->logger = $logger;
        
        $this->firstTrendCalculationTime = time() + self::MAX_MEASUREMENT_AGE; 
        $this->lastTrendCalculationTime = time();
    }
    
    /**
     * @inheritdoc
     */
    public function calculateProcessCount($currentProcessCount)
    {
        // Record measurements every call
        $this->recordMeasurements();
        
        // Calculate the trend every minute, not every call
        if ( ! $this->isTimeToCalculateTrend()) {
            return $this->minProcessCount;
        }
        
        $longTrend = $this->measurements->calculateTrend(self::MAX_MEASUREMENT_AGE);
        $shortTrend = $this->measurements->calculateTrend(self::SHORT_TREND_SPAN);
        
        $suggestedProcessCount = $currentProcessCount;
        // If both trends are growing, suggest increasing the process count
        if (0 < $longTrend && 0 < $shortTrend) {
            $suggestedProcessCount = $currentProcessCount + 1;
        }
        
        $suggestedProcessCount = min($suggestedProcessCount, $this->maxProcessCount);
    
        $this->logger->debug(
            Msg::calculatedJobCountTrend($shortTrend, $longTrend, $suggestedProcessCount, $this->queues)
        );
    
        return $suggestedProcessCount;
    }
    
    /**
     * Record the current job count in the measurements
     */
    private function recordMeasurements()
    {
        $jobCount = 0;
        
        foreach ($this->queues as $queue) {
            $jobCount += $this->disque->qlen($queue);
        }
        
        $this->measurements->recordJobCount($jobCount);
        $this->measurements->truncateMeasurements(self::MAX_MEASUREMENT_AGE);
        
        $this->logger->debug(Msg::recordJobCount($jobCount, $this->queues));
    }
    
    /**
     * Is now the time to calculate the trend?
     * 
     * Calculate after we have at least one whole cycle of data (5 minutes)
     * and only after each short cycle (every 60 seconds)
     * 
     * @return bool
     */
    private function isTimeToCalculateTrend()
    {
        $now = time();
        
        $first = $this->firstTrendCalculationTime < $now;
        $last = ($this->lastTrendCalculationTime + self::SHORT_TREND_SPAN) < $now;
        
        return $first && $last;
    }
    
}
