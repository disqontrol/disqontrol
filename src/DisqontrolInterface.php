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

use Disque\Client;
use Disqontrol\Producer\ProducerInterface;
use Disqontrol\Consumer\ConsumerInterface;

/**
 * The top-level Disqontrol class used for runtime configuration and access
 * 
 * This class receives the configuration, logger and other information
 * from the user during Disqontrol start.
 * 
 * @author Martin Schlemmer
 */
interface DisqontrolInterface
{
    /**
     * Return the Disque client
     *
     * @return Client
     */
    public function getDisque();

    /**
     * Get a queue producer
     * 
     * Producer can send jobs to the queue.
     * 
     * @param bool $synchronousMode Is synchronous mode on?
     *
     * @return ProducerInterface
     */
    public function getProducer($synchronousMode = false);
    
    /**
     * Get a queue consumer
     * 
     * Consumer can listen to one or more queues.
     * 
     * @return ConsumerInterface
     */
    public function getConsumer();
}
