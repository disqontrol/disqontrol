<?php
/*
* This file is part of the Disqontrol package.
*
* (c) Mediaplus.cz s.r.o. <info@mediaplus.cz>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/
namespace Disqontrol\Dispatcher\Call;

use Disqontrol\Job\JobInterface;
use Disqontrol\Router\WorkerDirectionsInterface;
use Disqontrol\Worker\WorkerType;

/**
 * AbstractCall for concrete calls
 *
 * @author Martin Schlemmer
 */
abstract class AbstractCall implements CallInterface
{
    /**
     * A human-readable error message if the call failed, otherwise empty string
     *
     * @var string
     */
    protected $errorMessage = '';

    /**
     * @var JobInterface The processed job
     */
    protected $job;

    /**
     * @var WorkerDirectionsInterface
     */
    protected $workerDirections;

    /**
     * {@inheritdoc}
     */
    public function isBlocking()
    {
        return $this->getType() === WorkerType::INLINE_PHP_WORKER();
    }

    /**
     * {@inheritdoc}
     */
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }

    /**
     * {@inheritdoc}
     */
    public function getJob()
    {
        return $this->job;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return $this->workerDirections->getType();
    }
}
