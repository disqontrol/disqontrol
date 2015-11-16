<?php

namespace Disqontrol\Exception;

/**
 * Class UnexpectedValueException
 * @author Martin Patera <mzstic@gmail.com>
 */
class UnexpectedValueException extends \UnexpectedValueException
{
    public static function create($message)
    {
        return new static($message);
    }
}