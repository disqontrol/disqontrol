<?php
/*
* This file is part of the Disqontrol package.
*
* (c) Webtrh s.r.o. <info@webtrh.cz>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Disqontrol\Router;

use Disqontrol\Job\JobInterface;

/**
 * {@inheritdoc}
 *
 * A simple job route that always returns one type of directions
 *
 * This route should be used if you don't need any logic in routing.
 *
 * @author Martin Schlemmer
 */
class SimpleRoute implements RouteInterface
{
    /**
     * @var array Names of supported queues
     */
    private $queues;

    /**
     * @var WorkerDirectionsInterface
     */
    private $directions;
    
    /**
     * @param string[]                  Names of supported queues
     * @param WorkerDirectionsInterface $directions
     */
    public function __construct(
        array $queues,
        WorkerDirectionsInterface $directions
    ) {
        $this->queues = $queues;
        $this->directions = $directions;
    }
    
    /**
     * {@inheritdoc}
     *
     * This Route is so simple it doesn't even look at the job. It immediately
     * returns the only WorkerDirections it has.
     */
    public function getDirections(JobInterface $job)
    {
        return $this->directions;
    }

    /***
     * {@inheritdoc}
     */
    public function getSupportedQueues()
    {
        return $this->queues;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsQueue($queue)
    {
        return in_array($queue, $this->queues);
    }
}
