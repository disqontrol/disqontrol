<?php
/*
* This file is part of the Disqontrol package.
*
* (c) Webtrh s.r.o. <info@webtrh.cz>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Disqontrol\Dispatcher\Call\Php;

use Disqontrol\Dispatcher\Call\CallInterface;
use Disqontrol\Dispatcher\Call\AbstractCall;
use Disqontrol\Job\JobInterface;
use Disqontrol\Router\WorkerDirectionsInterface;
use Disqontrol\Worker\WorkerInterface;
use Exception;

/**
 * Execute a PHP code directly in the current process
 *
 * {@inheritdoc}
 */
class PhpCall extends AbstractCall implements CallInterface
{
    /**
     * @var WorkerInterface
     */
    private $worker;

    /**
     * @var bool Has the call already been called?
     */
    private $hasBeenCalled = false;

    /**
     * @var bool Has the job been successfully processed?
     */
    private $result;

    /**
     * @param WorkerDirectionsInterface $workerDirections
     * @param WorkerInterface           $worker
     * @param JobInterface              $job
     */
    public function __construct(
        WorkerDirectionsInterface $workerDirections,
        WorkerInterface $worker,
        JobInterface $job
    ) {
        $this->workerDirections = $workerDirections;
        $this->worker = $worker;
        $this->job = $job;
    }

    /**
     * {@inheritdoc}
     */
    public function call()
    {
        if ($this->hasBeenCalled) {
            return;
        }

        $this->hasBeenCalled = true;

        try {
            $this->result = $this->worker->process($this->job);
        } catch (Exception $e) {
            $this->errorMessage = $e->getMessage();
            $this->result = false;
        }
    }

    /**
     * {@inheritdoc}
     *
     * The PHP call is blocking and if asked, can never be running
     */
    public function isRunning()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function checkTimeout()
    {
        return;
    }

    /**
     * {@inheritdoc}
     */
    public function wasSuccessful()
    {
        if ( ! $this->hasBeenCalled) {
            $this->call();
        }

        return $this->result;
    }
}
