<?php
/*
 * This file is part of the Disqontrol package.
 *
 * (c) Webtrh s.r.o. <info@webtrh.cz>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Disqontrol;

use Disqontrol\Configuration\Configuration;
use Disqontrol\Configuration\ConfigDefinition;
use Disque\Connection\Credentials;
use Disque\Client;

/**
 * Create and hold a connection to Disque
 *
 * @author Martin Schlemmer
 */
class DisqueClientFactory
{
    /**
     * @var Configuration
     */
    private $config;

    /**
     * @var \Disque\Client
     */
    private $disque;

    /**
     * @param Configuration $config
     */
    public function __construct(Configuration $config)
    {
        $this->config = $config;
    }

    /**
     * @return \Disque\Client
     */
    public function getClient()
    {
        if ($this->disque === null) {
            $this->createDisqueClient();
        }

        return $this->disque;
    }

    /**
     * Create a Disque client
     */
    private function createDisqueClient()
    {
        $credentials = $this->getCredentials();
        $this->disque = new Client($credentials);
    }

    /**
     * Create credentials for the Disque connection
     *
     * @return Credentials[]
     */
    private function getCredentials()
    {
        $credentials = [];
        foreach ($this->config->getDisqueConfig() as $credentialsConfig) {
            $result[] = new Credentials(
                $credentialsConfig[ConfigDefinition::HOST],
                $credentialsConfig[ConfigDefinition::PORT],
                $credentialsConfig[ConfigDefinition::PASSWORD],
                $credentialsConfig[ConfigDefinition::CONNECTION_TIMEOUT],
                $credentialsConfig[ConfigDefinition::RESPONSE_TIMEOUT]
            );
        }

        return $credentials;
    }
}
