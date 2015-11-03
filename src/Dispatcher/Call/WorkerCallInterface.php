<?php
/*
* This file is part of the Disqontrol package.
*
* (c) Webtrh s.r.o. <info@webtrh.cz>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Disqontrol\Dispatcher\Call;

/**
 * Call a job worker and interpret its results
 * 
 * This object wraps the call to a job worker. It knows how to call it, where
 * to reach it and what arguments to send. It also understands the return codes
 * and knows if the worker was successful in performing the job or not.
 * 
 * @author Martin Schlemmer
 */
interface WorkerCallInterface
{
    
}
