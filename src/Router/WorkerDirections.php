<?php
/*
* This file is part of the Disqontrol package.
*
* (c) Mediaplus.cz s.r.o. <info@mediaplus.cz>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Disqontrol\Router;

use Disqontrol\Worker\WorkerType;

/**
 * {@inheritdoc}
 *
 * @author Martin Patera <mzstic@gmail.com>
 */
class WorkerDirections implements WorkerDirectionsInterface
{
    /**
     * The type of the worker - CLI, HTTP, INLINE_PHP_WORKER, ISOLATED_PHP_WORKER
     *
     * @var WorkerType
     */
    protected $type;

    /**
     * The worker address - the command name, URI, class name...
     *
     * @var string
     */
    protected $address;

    /**
     * Additional parameters
     *
     * @var array
     */
    protected $parameters;

    /**
     * @param WorkerType $type
     * @param string     $address
     * @param array      $parameters
     */
    public function __construct(
        WorkerType $type,
        $address,
        array $parameters = []
    ) {
        $this->type = $type;
        $this->address = $address;
        $this->parameters = $parameters;
    }

    /**
     * @inheritdoc
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @inheritdoc
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @inheritdoc
     */
    public function getParameters()
    {
        return $this->parameters;
    }
}
