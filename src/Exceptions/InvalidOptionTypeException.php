<?php

declare(strict_types = 1);

namespace Alpipego\AWP\Cache\Exceptions;

class InvalidOptionTypeException extends \InvalidArgumentException
{
    public function __construct(string $name, string $typeOption, string $passedType)
    {
        parent::__construct(sprintf('Expected %s to be of type %s, %s passed', $name, $typeOption, $passedType));
    }
}
