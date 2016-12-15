<?php
/*
* This file is part of the Disqontrol package.
*
* (c) Mediaplus.cz s.r.o. <info@mediaplus.cz>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Disqontrol\Dispatcher\Call\Factory;

use Disqontrol\Router\WorkerDirectionsInterface;
use Disqontrol\Job\JobInterface;
use Disqontrol\Dispatcher\Call\CallInterface;

/**
 * Create worker calls based on directions
 */
interface CallFactoryInterface
{
    /**
     * Create a worker call
     *
     * @param WorkerDirectionsInterface $workerDirections
     * @param JobInterface              $job
     *
     * @return CallInterface
     */
    public function createCall(
        WorkerDirectionsInterface $workerDirections,
        JobInterface $job
    );
}
