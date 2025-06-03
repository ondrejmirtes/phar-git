<?php

declare (strict_types=1);
namespace PHPStan\Reflection\Php;

use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\Type\UnresolvedMethodPrototypeReflection;
use PHPStan\Type\ClosureType;
use PHPStan\Type\Type;
final class ClosureCallUnresolvedMethodPrototypeReflection implements UnresolvedMethodPrototypeReflection
{
    private UnresolvedMethodPrototypeReflection $prototype;
    private ClosureType $closure;
    public function __construct(UnresolvedMethodPrototypeReflection $prototype, ClosureType $closure)
    {
        $this->prototype = $prototype;
        $this->closure = $closure;
    }
    public function doNotResolveTemplateTypeMapToBounds(): UnresolvedMethodPrototypeReflection
    {
        return new self($this->prototype->doNotResolveTemplateTypeMapToBounds(), $this->closure);
    }
    public function getNakedMethod(): ExtendedMethodReflection
    {
        return $this->getTransformedMethod();
    }
    public function getTransformedMethod(): ExtendedMethodReflection
    {
        return new \PHPStan\Reflection\Php\ClosureCallMethodReflection($this->prototype->getTransformedMethod(), $this->closure);
    }
    public function withCalledOnType(Type $type): UnresolvedMethodPrototypeReflection
    {
        return new self($this->prototype->withCalledOnType($type), $this->closure);
    }
}
