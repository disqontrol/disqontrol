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

/**
 * Failure strategies are classes that decide what happens with a failed job.
 *
 * The job dispatcher receives a failure strategy and delegates the decision
 * to it. Failure strategies can be swapped in and out depending on your needs.
 *
 * @author Martin Schlemmer
 */
interface FailureStrategyInterface
{
    /**
     * Handle a failed job call
     *
     * @param CallInterface $call The call that resulted in failure
     */
    public function handleFailure(CallInterface $call);
}
