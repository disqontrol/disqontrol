<?php
/*
* This file is part of the Disqontrol package.
*
* (c) Webtrh s.r.o. <info@webtrh.cz>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Disqontrol\Worker;

use GerritDrost\Lib\Enum\Enum;

/**
 * WorkerType enumerates all known worker types for type hinting
 * 
 * Worker types are strings in the configuration defined by convention.
 * This enum contains all known worker types and allows to make use of type hinting
 * thus making it easier to know whether you work with a valid worker type.
 *
 * CLI is a worker called via a console command.
 * HTTP is a worker called via a HTTP request.
 * PHP is an inline PHP worker - PHP code called directly from the Consumer.
 * PHP-CLI is a PHP worker called in an independent process to perform one job.
 *
 * @see also disqontrol.yml
 * 
 * @author Martin Schlemmer
 *
 * @method static WorkerType CLI()
 * @method static WorkerType HTTP()
 * @method static WorkerType PHP()
 * @method static WorkerType PHP_CLI()
 */
class WorkerType extends Enum
{
    const CLI = 'cli';
    const HTTP = 'http';
    const PHP = 'php';
    const PHP_CLI = 'php-cli';
}
