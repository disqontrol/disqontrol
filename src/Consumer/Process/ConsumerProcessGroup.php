<?php
/*
* This file is part of the Disqontrol package.
*
* (c) Mediaplus.cz s.r.o. <info@mediaplus.cz>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Disqontrol\Consumer\Process;

use Disqontrol\Consumer\Autoscale\AutoscaleAlgorithmInterface;
use Exception;
use Psr\Log\LoggerInterface;

/**
 * A group of consumer processes
 *
 * @author Martin Schlemmer
 */
class ConsumerProcessGroup
{
    /**
     * @var int A SIGTERM signal value as used in
     * Symfony\Component\Process\Process::stop()
     */
    const SIGTERM = 15;

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
     * @var int How many job should each consumer ask for in one batch
     */
    private $jobBatch;

    /**
     * An object that spawns new consumer processes
     *
     * @var ConsumerProcessSpawner
     */
    private $processSpawner;

    /**
     * An algorithm for autoscaling the number of consumer processes
     *
     * @var AutoscaleAlgorithmInterface
     */
    private $autoscaleAlgorithm;

    /**
     * @var LoggerInterface
     */
    private $logger;
    
    /**
     * @var ConsumerProcess[]
     */
    private $processes = array();

    /**
     * @param string[]                    $queues
     * @param int                         $minProcessCount
     * @param int                         $maxProcessCount
     * @param int                         $jobBatch
     * @param ConsumerProcessSpawner      $processSpawner
     * @param AutoscaleAlgorithmInterface $autoscaleAlgorithm
     * @param LoggerInterface             $logger
     */
    public function __construct(
        array $queues,
        $minProcessCount,
        $maxProcessCount,
        $jobBatch,
        ConsumerProcessSpawner $processSpawner,
        AutoscaleAlgorithmInterface $autoscaleAlgorithm,
        LoggerInterface $logger
    ) {
        $this->queues = $queues;
        $this->minProcessCount = $minProcessCount;
        $this->maxProcessCount = $maxProcessCount;
        $this->jobBatch = $jobBatch;

        $this->processSpawner = $processSpawner;
        $this->autoscaleAlgorithm = $autoscaleAlgorithm;
        $this->logger = $logger;
        
        // Spawn the initial number of processes
        $this->startPermanentProcesses();
    }

    /**
     * Perform a regular check on consumers
     *
     * Remove stopped processes, spawn new ones. This method should be called
     * in regular intervals.
     */
    public function checkOnConsumers()
    {
        $this->freeMemory();

        $currentProcessCount = count($this->processes);
        $targetProcessCount = $this->autoscaleAlgorithm->calculateProcessCount(
            $currentProcessCount
        );
        
        $this->spawnProcesses($targetProcessCount);
    }

    /**
     * Send a SIGTERM signal to all processes
     *
     * This should be called before stopCompletely() to send the stop signal
     * to all processes at once.
     *
     * These two steps are split into two methods so the Supervisor can send
     * SIGTERM to all consumer groups at first and the processes can start
     * their shutdown procedure immediately.
     *
     * Only then do we really wait for each process and enforce its termination,
     * one by one.
     */
    public function sendStopSignal()
    {
        foreach ($this->processes as $process) {
            try {
                $process->signal(self::SIGTERM);
            } catch (Exception $e) {
                // We're not interested in any exception but we want to catch
                // them instead of crashing. Log?
            }
        }
    }

    /**
     * Ensure all processes have stopped
     *
     * Process::stop() works synchronously: It sends a stop signal to the process
     * and waits up to 10 seconds whether it really stopped, else a KILL signal
     * is sent.
     *
     * To send the SIGTERM signal to all processes at once, use the method
     * sendStopSignal() and then this one.
     */
    public function stopCompletely()
    {
        foreach ($this->processes as $process) {
            $process->stop();
        }
    }

    /**
     * Get the names of the supported queues
     *
     * @return string[]
     */
    public function getQueues()
    {
        return $this->queues;
    }

    /**
     * Get the minimum number of processes
     *
     * @return int
     */
    public function getMinProcessCount()
    {
        return $this->minProcessCount;
    }

    /**
     * Get the maximum number of processes
     *
     * @return int
     */
    public function getMaxProcessCount()
    {
        return $this->maxProcessCount;
    }

    /**
     * Get the number of jobs in one job batch
     *
     * @return int
     */
    public function getJobBatch()
    {
        return $this->jobBatch;
    }

    /**
     * Remove stopped processes from memory
     *
     * Technically we mark them as unset and they will be removed in the next
     * garbage collection cycle.
     */
    private function freeMemory()
    {
        foreach ($this->processes as $key => $process) {
            if ( ! $process->isRunning()) {
                unset($this->processes[$key]);
            }
        }

        $this->processes = array_values($this->processes);
    }

    /**
     * Ensure that enough consumer processes are running
     *
     * @param int $targetProcessCount The total required number of processes
     */
    private function spawnProcesses($targetProcessCount)
    {
        $this->startPermanentProcesses();
        $this->startBurstProcesses($targetProcessCount);
    }

    /**
     * Start the permanent processes
     *
     * Permanent processes are consumers that must always run according
     * to the configuration; opposite to the burst processes, that only
     * run for a short time if there's a high demand, and then they stop.
     */
    private function startPermanentProcesses()
    {
        $missingPermanentCount = $this->calculateMissingPermanentProcessCount();

        if ($missingPermanentCount <= 0) {
            return;
        }

        $burstMode = false;
        $this->doSpawn($missingPermanentCount, $burstMode);
    }

    /**
     * Start the burst processes
     *
     * @param int $totalTargetCount
     */
    private function startBurstProcesses($totalTargetCount)
    {
        $missingBurstCount = $this->calculateMissingBurstProcessCount($totalTargetCount);

        if ($missingBurstCount <= 0) {
            return;
        }

        $burstMode = true;
        $this->doSpawn($missingBurstCount, $burstMode);
    }

    /**
     * How many processes we must spawn to have the minimum process count
     *
     * @return int The number of permanent processes to spawn
     */
    private function calculateMissingPermanentProcessCount()
    {
        $missingPermanentCount = $this->minProcessCount - $this->getCurrentProcessCount();
        $missingPermanentCount = min($missingPermanentCount, $this->maxProcessCount);

        return $missingPermanentCount;
    }

    /**
     * How many burst processes we must spawn to have the target process count
     *
     * @param int $targetCount How many processes in total we require
     *
     * @return int The number of burst processes to spawn
     */
    private function calculateMissingBurstProcessCount($targetCount)
    {
        $targetCount = min($targetCount, $this->maxProcessCount);
        $missingBurstCount = $targetCount - $this->minProcessCount;
        $missingBurstCount = max($missingBurstCount, 0);

        return $missingBurstCount;
    }

    /**
     * Get the number of current processes
     *
     * @return int
     */
    private function getCurrentProcessCount()
    {
        return count($this->processes);
    }

    /**
     * Spawn the required number of processes in the given mode
     *
     * @param int  $count
     * @param bool $burstMode
     */
    private function doSpawn($count, $burstMode)
    {
        for ($i = 0; $i < $count; $i++) {
            $this->processes[] = $this->processSpawner->spawn(
                $this->queues,
                $this->jobBatch,
                $burstMode
            );
        }
    }

}
