<?php
namespace Disqontrol\Exception;

/**
 * @author Martin Patera <mzstic@gmail.com>
 */
class ConfigurationException extends \Exception
{
    public static function configFileNotFound($filename) {
        return new static(sprintf("Configuration file %s does not exist.", $filename));
    }
}