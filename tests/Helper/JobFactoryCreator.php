<?php
/*
* This file is part of the Disqontrol package.
*
* (c) Mediaplus.cz s.r.o. <info@mediaplus.cz>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Disqontrol\Test\Helper;

use Disqontrol\Job\JobFactory;
use Mockery as m;
use Disqontrol\Configuration\Configuration;
use Disqontrol\Logger\JobLogger;

/**
 * A helper class for creating the job factory for tests
 *
 * Could also be called JobFactoryFactory
 *
 * @author Martin Schlemmer
 */
class JobFactoryCreator
{
    /**
     * @param $config
     * @param $logger
     *
     * @return JobFactory
     */
    public static function create($config = null, $logger = null)
    {
        if (is_null($config)) {
            $config = m::mock(Configuration::class)
                ->shouldReceive('getJobLifetime')
                ->andReturn(3660)
                ->getMock();
        }

        if (is_null($logger)) {
            $logger = m::mock(JobLogger::class)
                ->shouldReceive('debug')
                ->getMock();
        }

        return new JobFactory($config, $logger);
    }
}
