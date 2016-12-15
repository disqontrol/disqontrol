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

use Disqontrol\Exception\ConfigurationException;
use Disqontrol\Job\JobInterface;
use Disqontrol\Worker\WorkerInterface;
use Disqontrol\Logger\MessageFormatter as msg;


/**
 * A worker that doesn't do anything and always fails the job processing
 *
 * The job will not be processed, but the event will be logged.
 * The NullWorker is used if no PHP worker for a job is found
 *
 * @author Martin Schlemmer
 */
class NullWorker implements WorkerInterface
{
    /**
     * @var string The missing worker name
     */
    private $workerName;
    
    /**
     * @param string $workerName
     */
    public function __construct($workerName)
    {
        $this->workerName = $workerName;
    }
    
    /**
     * {@inheritdoc}
     */
    public function process(JobInterface $job)
    {
        throw new ConfigurationException(
            msg::phpJobWorkerNotFound($this->workerName)
        );
    }
}
