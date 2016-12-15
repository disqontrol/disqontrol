<?php
/*
 * This file is part of the Disqontrol package.
 *
 * (c) Mediaplus.cz s.r.o. <info@mediaplus.cz>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Disqontrol\Disque;

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
        $this->disque->connect();
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
            $credentials[] = new Credentials(
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
