<?php
/*
* This file is part of the Disqontrol package.
*
* (c) Mediaplus.cz s.r.o. <info@mediaplus.cz>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/
namespace Disqontrol\Dispatcher\Failure;

use Disqontrol\Dispatcher\Call\CallInterface;
use Disqontrol\Disque\FailJob;

/**
 * This failure handler retries all failed jobs immediately with a NACK
 *
 * @author Martin Schlemmer
 */
class RetryImmediately implements FailureStrategyInterface
{
    /**
     * @var FailJob
     */
    private $failJob;

    /**
     * @param FailJob $failJob
     */
    public function __construct(FailJob $failJob)
    {
        $this->failJob = $failJob;
    }

    /**
     * {@inheritdoc}
     */
    public function handleFailure(CallInterface $call)
    {
        $job = $call->getJob();
        $errorMessage = $call->getErrorMessage();
        $this->failJob->logError($job, $errorMessage);

        $this->failJob->nack($job);
    }
}
