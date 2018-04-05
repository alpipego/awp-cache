<?php
/**
 * Created by PhpStorm.
 * User: alpipego
 * Date: 03.04.18
 * Time: 14:54
 */
declare(strict_types = 1);

namespace Alpipego\AWP\Cache\Exceptions;

class InvalidOptionTypeException extends \InvalidArgumentException
{
    public function __construct(string $name, string $typeOption, string $passedType)
    {
        $this->message = sprintf('Expected %s to be of type %s, %s passed', $name, $typeOption, $passedType);
    }
}
