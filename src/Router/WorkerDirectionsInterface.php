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
 * Directions describe how and where a worker can be called
 * 
 * They contain the type of the worker, its address (URL/command/worker name)
 * and more parameters if needed (arguments, HTTP headers).
 * 
 * @author Martin Schlemmer
 */
interface WorkerDirectionsInterface
{
    /**
     * Get the worker type
     * 
     * @return WorkerType
     */
    public function getType();
    
    /**
     * Get the worker address - its command, worker name or a URL
     * 
     * @return string
     */
    public function getAddress();
    
    /**
     * Get more worker parameters in an indexed array
     * 
     * @return array
     */
    public function getParameters();
}
