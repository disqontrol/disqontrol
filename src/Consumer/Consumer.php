<?php
/*
* This file is part of the Disqontrol package.
*
* (c) Webtrh s.r.o. <info@webtrh.cz>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Disqontrol\Consumer;

use Disqontrol\Dispatcher\JobDispatcherInterface;
use Disqontrol\Logger\MessageFormatter;
use Disqontrol\ProcessControl\ProcessControl;
use Disque\Client;
use Disqontrol\Job\Marshaller\MarshallerInterface;
use Exception;
use Disqontrol\Job\Serializer\SerializerInterface;
use Disqontrol\Job\JobInterface;
use Psr\Log\LoggerInterface;

/**
 * {@inheritdoc}
 *
 * @author Martin Schlemmer
 */
class Consumer implements ConsumerInterface
{
    /**
     * Names of the Disque-php GetJob options
     * @see Disque\Command\GetJob
     */
    const GETJOB_OPTIONS_TIMEOUT = 'timeout';
    const GETJOB_OPTIONS_NOHANG = 'nohang';
    const GETJOB_OPTIONS_COUNT = 'count';
    const GETJOB_OPTIONS_WITHCOUNTERS = 'withcounters';

    /**
     * Wait for jobs for 100 ms, or 0.1 s
     */
    const GETJOB_TIMEOUT = 100;
    /**
     * How long to wait if there were no jobs in the last cycle
     * 100000 microseconds, or 0.1s
     */
    const PAUSE_IF_NO_JOBS = 100000;

    /**
     * Some sanity checks
     */
    const MIN_BATCH = 1;
    const MAX_BATCH = 99;

    /**
     * @var Client
     */
    private $disque;
    
    /**
     * @var MarshallerInterface
     */
    private $marshaller;

    /**
     * @var SerializerInterface
     */
    private $jsonSerializer;

    /**
     * @var JobDispatcherInterface
     */
    private $jobDispatcher;

    /**
     * @var ProcessControl
     */
    private $processControl;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * True if the consumer received a signal to terminate
     *
     * Finish started calls, return unprocessed jobs, then terminate.
     *
     * @var bool
     */
    private $mustTerminate = false;

    /**
     * @param Client                 $disque
     * @param MarshallerInterface    $marshaller
     * @param JobDispatcherInterface $jobDispatcher
     * @param ProcessControl         $processControl
     * @param SerializerInterface    $jsonSerializer
     * @param LoggerInterface        $logger
     */
    public function __construct(
        Client $disque,
        MarshallerInterface $marshaller,
        JobDispatcherInterface $jobDispatcher,
        ProcessControl $processControl,
        SerializerInterface $jsonSerializer,
        LoggerInterface $logger
    ) {
        $this->disque = $disque;
        $this->marshaller = $marshaller;
        $this->jobDispatcher = $jobDispatcher;
        $this->processControl = $processControl;
        $this->jsonSerializer = $jsonSerializer;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function listen(array $queueNames, $jobBatch = 10, $burstMode = false)
    {
        $this->registerSignalHandlers();

        $jobBatch = $this->checkJobBatchBounds($jobBatch);
        $getJobOptions = $this->createGetJobOptions($jobBatch);

        while (true) {
            $this->processControl->checkForSignals();
            if ($this->mustTerminate === true) {
                return;
            }

            $jobsFromDisque = call_user_func_array(
                [$this->disque, 'getJob'],
                array_merge($queueNames, [$getJobOptions])
            );

            if ($this->hasNoMoreJobsInBurstMode($jobsFromDisque, $burstMode)) {
                return;
            }

            $jobs = $this->createJobsFromDisqueResponse($jobsFromDisque);

            if (empty($jobs)) {
                usleep(self::PAUSE_IF_NO_JOBS);
                continue;
            }

            $this->jobDispatcher->dispatch($jobs);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function terminate()
    {
        // Tell other methods in the Consumer that they must shut down
        $this->mustTerminate = true;

        // Tell also the job dispatcher it has to shut down
        $this->jobDispatcher->terminate();

        $this->logger->debug(MessageFormatter::receivedTerminateSignal());
    }

    /**
     * Register signal handlers that listen for process signals
     */
    private function registerSignalHandlers()
    {
        $this->processControl->registerSignalHandler(
            [SIGINT, SIGTERM],
            [$this, 'terminate']
        );
    }

    /**
     * Check the lower and upper bounds of the job batch argument
     *
     * @param int $jobBatch
     *
     * @return int Checked job batch
     */
    private function checkJobBatchBounds($jobBatch)
    {
        if ($jobBatch < self::MIN_BATCH) {
            $jobBatch = self::MIN_BATCH;
        } else if (self::MAX_BATCH < $jobBatch) {
            $jobBatch = self::MAX_BATCH;
        }

        return $jobBatch;
    }

    /**
     * Create options for the GETJOB command
     *
     * @param int  $jobBatch
     *
     * @return array Options for the GETJOB command
     */
    private function createGetJobOptions($jobBatch)
    {
        $options = [
            self::GETJOB_OPTIONS_WITHCOUNTERS => true,
            self::GETJOB_OPTIONS_COUNT => (int) $jobBatch
        ];

        // Disque offers the possibility to wait for jobs indefinitely, but
        // we need to check for incoming process signals every now and then.
        // So let's wait only a certain time, then check for signals, then
        // wait for jobs again, etc.
        $options[self::GETJOB_OPTIONS_TIMEOUT] = self::GETJOB_TIMEOUT;

        return $options;
    }

    /**
     * Check if we are in burst mode and have nothing else to do
     *
     * @param array $jobsFromDisque
     * @param bool  $burstMode
     *
     * @return bool
     */
    private function hasNoMoreJobsInBurstMode($jobsFromDisque, $burstMode)
    {
        return empty($jobsFromDisque) and $burstMode === true;
    }
    
    /**
     * Create Job objects from the Disque text response
     *
     * @param array $jobsFromDisque The Disque GETJOB response
     *
     * @return JobInterface[]
     */
    private function createJobsFromDisqueResponse(array $jobsFromDisque)
    {
        $jobs = array();

        foreach ($jobsFromDisque as $jobData) {
            try {
                $jobs[] = $this->marshaller->unmarshal($jobData);
            } catch (Exception $e) {
                $this->logger->error(
                    MessageFormatter::failedToUnmarshalJob(
                        $this->jsonSerializer->serialize($jobData),
                        $e->getMessage()
                    )
                );
            }
        }

        return $jobs;
    }
    
}
