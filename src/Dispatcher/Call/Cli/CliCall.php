<?php
/*
* This file is part of the Disqontrol package.
*
* (c) Webtrh s.r.o. <info@webtrh.cz>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Disqontrol\Dispatcher\Call\Cli;

use Disqontrol\Dispatcher\Call\AbstractCall;
use Disqontrol\Dispatcher\Call\CallInterface;
use Disqontrol\Job\Marshaller\MarshallerInterface;
use Symfony\Component\Process\Process;
use Disqontrol\Router\WorkerDirectionsInterface;
use Disqontrol\Job\JobInterface;
use Disqontrol\Worker\WorkerType;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Exception;
use RuntimeException;

/**
 * {@inheritdoc}
 */
class CliCall extends AbstractCall implements CallInterface
{
    const COMMAND = '%s --body=%s --metadata=%s';

    /**
     * The system process used for this CLI call
     *
     * @var Process|null
     */
    private $process;

    /**
     * @var ProcessFactory
     */
    private $processFactory;

    /**
     * @param WorkerDirectionsInterface $directions
     * @param JobInterface              $job
     * @param MarshallerInterface       $jobMarshaller
     * @param int                       $timeout in seconds
     * @param ProcessFactory            $processFactory
     */
    public function __construct(
        WorkerDirectionsInterface $directions,
        JobInterface $job,
        MarshallerInterface $jobMarshaller,
        $timeout,
        ProcessFactory $processFactory
    ) {
        $this->workerDirections = $directions;
        $this->job = $job;

        if ($directions->getType() !== WorkerType::CLI()) {
            $errorMessage = sprintf(
                'A CliCall cannot use directions for a %s worker.',
                $directions->getType()->getConstName()
            );

            return $this->failEarly($errorMessage);
        }

        try {
            $jobBody = escapeshellarg(
                $jobMarshaller->marshal(
                    $job->getBody()
                )
            );
            $jobMetadata = escapeshellarg(
                $jobMarshaller->marshal(
                    $job->getAllMetadata()
                )
            );
        } catch (RuntimeException $e) {
            return $this->failEarly($e->getMessage());
        }

        $command = sprintf(
            self::COMMAND,
            $directions->getAddress(),
            $jobBody,
            $jobMetadata
        );

        // This can throw an exception if proc_open is not installed.
        // This is a fatal exception. We cannot do anything. Better fail fast.
        $this->process = $processFactory->create($command, $timeout);
    }

    /**
     * {@inheritdoc}
     */
    public function call()
    {
        try {
            $this->process->start();
        } catch (Exception $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isRunning()
    {
        return $this->process->isRunning();
    }

    /**
     * {@inheritdoc}
     */
    public function checkTimeout()
    {
        try {
            $this->process->checkTimeout();
        } catch (Exception $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function wasSuccessful()
    {
        // If the process is running, block it until it finishes
        if ($this->process->isRunning()) {
            try {
                $this->process->wait();
            } catch (Exception $e) {
                $this->errorMessage = $e->getMessage();
            }
        }

        // Generate an error message, if the process failed but we have no message
        if ( ! $this->process->isSuccessful() and empty($this->errorMessage)) {
            // This exception makes nice, readable error messages. Let's use it
            $niceException = new ProcessFailedException($this->process);
            $this->errorMessage = $niceException->getMessage();
        }

        return $this->process->isSuccessful();
    }

    /**
     * Return from the constructor early and fail with the Null Object pattern
     *
     * @param string $errorMessage
     *
     * @return null
     */
    private function failEarly($errorMessage = '')
    {
        $this->errorMessage = $errorMessage;
        $this->process = $this->processFactory->createNullProcess();

        return null;
    }
}
