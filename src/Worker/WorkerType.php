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
use InvalidArgumentException;

/**
 * WorkerType enumerates all known worker types for type hinting
 * 
 * Worker types are strings in the configuration defined by convention.
 * This enum contains all known worker types and allows to make use of type hinting
 * thus making it easier to know whether you work with a valid worker type.
 *
 * CLI is a worker called via a console command
 * HTTP is a worker called via a HTTP request
 * INLINE_PHP_WORKER is a PHP code called directly from the Consumer
 * ISOLATED_PHP_WORKER is a PHP worker called in a separate process to process
 * one job
 *
 * PHP workers must be registered during the bootstrap
 * in the WorkerFactoryCollection, which must be injected into the Disqontrol
 * class constructor.
 *
 * @see also disqontrol.yml
 * 
 * @author Martin Schlemmer
 *
 * @method static WorkerType CLI()
 * @method static WorkerType HTTP()
 * @method static WorkerType INLINE_PHP_WORKER()
 * @method static WorkerType ISOLATED_PHP_WORKER()
 */
class WorkerType extends Enum
{
    const CLI = 'cli';
    const HTTP = 'http';
    const INLINE_PHP_WORKER = 'inline_php_worker';
    const ISOLATED_PHP_WORKER = 'isolated_php_worker';

    /**
     * Get the WorkerType instance by its value.
     *
     * Always returns the class on which the method is called.
     * I.e. WorkerType::getByValue(...) returns WorkerType, not Enum.
     *
     * @param mixed $enumValue The enum value to search.
     *
     * @throws InvalidArgumentException if specified value and default value is not defined
     *
     * @return static
     */
    public static function getByValue($enumValue)
    {
        $enum = self::findEnumByValue($enumValue);
        if ($enum !== null) {
            return $enum;
        }

        $msg = sprintf("%s with value '%s' is not defined", get_class(), $enumValue);
        throw new InvalidArgumentException($msg);
    }

    /**
     * Search for the Enum with a specified value
     *
     * @param mixed $value
     *
     * @return Enum|null Return the Enum with the specified value, otherwise return null
     */
    private static function findEnumByValue($value)
    {
        $enums = array_filter(self::getEnumValues(), function (Enum $enum) use ($value) {
            return $enum->getConstValue() === $value;
        });

        if ( ! empty($enums)) {
            return reset($enums);
        }
    }
}
