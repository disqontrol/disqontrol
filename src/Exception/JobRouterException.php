<?php
/*
* This file is part of the Disqontrol package.
*
* (c) Webtrh s.r.o. <info@webtrh.cz>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Disqontrol\Exception;

use Exception;

/**
 * An exception indicating that the job router cannot find a worker for a job
 *
 * @author Martin Patera <mzstic@gmail.com>
 */
class JobRouterException extends Exception
{

}
