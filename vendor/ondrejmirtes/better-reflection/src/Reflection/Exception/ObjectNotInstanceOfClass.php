<?php

declare (strict_types=1);
namespace PHPStan\BetterReflection\Reflection\Exception;

use InvalidArgumentException;
use function sprintf;
class ObjectNotInstanceOfClass extends InvalidArgumentException
{
    public static function fromClassName(string $className): self
    {
        return new self(sprintf('Object is not instance of class "%s"', $className));
    }
}
