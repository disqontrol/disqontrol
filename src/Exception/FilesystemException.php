<?php
namespace Disqontrol\Exception;

/**
 * @author Martin Patera <mzstic@gmail.com>
 */
class FilesystemException extends \RuntimeException
{
    public static function directoryProblem($dir, $name, $operation)
    {
        return new static(sprintf("Unable to %s the %s directory (%s)\n", $operation, $name, $dir));
    }

    public static function cantCreateDirectory($dir, $name)
    {
        return static::directoryProblem($dir, $name, 'create');
    }

    public static function cantWriteDirectory($dir, $name)
    {
        return static::directoryProblem($dir, $name, 'write');
    }
}
