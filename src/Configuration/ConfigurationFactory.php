<?php
/*
* This file is part of the Disqontrol package.
*
* (c) Mediaplus.cz s.r.o. <info@mediaplus.cz>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Disqontrol\Configuration;

/**
 * Receive the configuration array and create the Configuration object
 *
 * The configuration array comes from outside the service container and cannot
 * be registered as container parameters (the container is cached but we need
 * fresh configuration), so the configuration array is registered through
 * this factory.
 *
 * @author Martin Schlemmer
 */
class ConfigurationFactory
{
    /**
     * @var array The configuration parameters
     */
    private $configArray = array();

    /**
     * @var Configuration
     */
    private $config;

    /**
     * Set the configuration parameters
     *
     * @param array $configArray
     */
    public function setConfigArray(array $configArray)
    {
        $this->configArray = $configArray;
    }

    /**
     * Create the Configuration object
     *
     * @return Configuration
     */
    public function create()
    {
        if (empty($this->config)) {
            $this->config = new Configuration($this->configArray);
        }

        return $this->config;
    }
}
