<?php
/*
* This file is part of the Disqontrol package.
*
* (c) Mediaplus.cz s.r.o. <info@mediaplus.cz>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Disqontrol\Job\Serializer;

use InvalidArgumentException;

/**
 * This serializer translates the job body and metadata between PHP and non-PHP
 * layers, eg. between PHP and Disque, command line interface or HTTP requests.
 *
 * @author Martin Schlemmer
 */
interface SerializerInterface {
    /**
     * Serialize a job body for Disque
     *
     * @param array|string $jobBody
     *
     * @return string Serialized job body
     *
     * @throws InvalidArgumentException
     */
    public function serialize($jobBody);

    /**
     * Deserialize a job body from Disque
     *
     * @param string $jobBody
     *
     * @return array|string Deserialized job body
     *
     * @throws InvalidArgumentException
     */
    public function deserialize($jobBody);
}
