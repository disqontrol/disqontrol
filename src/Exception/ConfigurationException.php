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

/**
 * @author Martin Patera <mzstic@gmail.com>
 */
class ConfigurationException extends \Exception
{
    /**
     * The configuration file could not be found
     *
     * @param string $filename
     *
     * @return static
     */
    public static function configFileNotFound($filename) {
        return new static(sprintf("Configuration file %s does not exist.", $filename));
    }

    /**
     * Create a new exception
     *
     * @param string $message
     *
     * @return static
     */
    public static function create($message) {
        return new static($message);
    }
}
