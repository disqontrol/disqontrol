<?php
/*
 * This file is part of the Disqontrol package.
 *
 * (c) Webtrh s.r.o. <info@webtrh.cz>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Disqontrol\Job\Marshaller;

use Disqontrol\Job\JobInterface;

/**
 * Job marshaller translates a Job object between PHP and Disque
 *
 * @author Martin Schlemmer
 */
interface MarshallerInterface
{
    /**
     * Prepare the job body to be sent to Disque
     *
     * @param string|array $jobBody
     *
     * @return string A serialized job body
     *
     * @throws \RuntimeException
     */
    public function marshal($jobBody);

    /**
     * @param array $getJobResponse Response from the Disque command GETJOB
     *
     * @return JobInterface A Job object
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function unmarshal(array $getJobResponse);
}
