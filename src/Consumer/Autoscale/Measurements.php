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

use Revisor\Trend\TrendCalculator;

/**
 * Queue statistics used in autoscaling
 *
 * @author Martin Schlemmer
 */
class Measurements
{
    /**
     * @var TrendCalculator
     */
    private $trendCalculator;
    
    /**
     * Job count at a certain time
     *
     * [
     *  [time => job count],
     *  [time => job count],
     *  ...
     * ]
     *
     * @var array
     */
    private $measurements = array();
    
    /**
     * Measurements constructor.
     *
     * @param TrendCalculator $trendCalculator
     */
    public function __construct(
        TrendCalculator $trendCalculator
    ) {
        $this->trendCalculator = $trendCalculator;
    }
    
    /**
     * Record a job count at the given time, or now if the time is missing
     *
     * @param int        $jobCount
     * @param float|null $time
     */
    public function recordJobCount($jobCount, $time = null)
    {
        if (is_null($time)) {
            $timeAsFloat = false;
            $time = microtime($timeAsFloat);
        }
        
        $this->measurements[] = [$time => $jobCount];
    }
    
    /**
     * Truncate the measurements so that only the last X seconds remain
     *
     * @param int $secondsToKeep
     */
    public function truncateMeasurements($secondsToKeep = 300)
    {
        $this->measurements = $this->truncateGivenMeasurements(
            $this->measurements, 
            $secondsToKeep
        );
    }
    
    /**
     * Calculate the trend for the measurements, for the last X seconds
     *
     * @param int $timeSpan Time (in seconds) to calculate the trend for
     *
     * @return float
     */
    public function calculateTrend($timeSpan = 0)
    {
        $trendData = $this->truncateGivenMeasurements(
            $this->measurements,
            $timeSpan
        );
        $trend = $this->trendCalculator->calculateTrend($trendData);
        
        return $trend;
    }
    
    /**
     * Truncate measurements given in the argument, to the last X seconds
     *
     * @param array $measurements
     * @param int   $secondsToKeep
     *
     * @return array
     */
    private function truncateGivenMeasurements(array $measurements, $secondsToKeep)
    {
        $truncateUntil = strtotime("-$secondsToKeep seconds");
        
        $truncatedMeasurements = array_filter(
            $measurements,
            function ($measurement) use ($truncateUntil) {
                return $truncateUntil < key($measurement);
            }
        );
        
        return $truncatedMeasurements;
    }
    
}
