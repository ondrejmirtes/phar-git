<?php

declare (strict_types=1);
namespace PHPStan\Reflection\Type;

use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\Type;
use function array_map;
final class UnionTypeUnresolvedMethodPrototypeReflection implements \PHPStan\Reflection\Type\UnresolvedMethodPrototypeReflection
{
    private string $methodName;
    /**
     * @var UnresolvedMethodPrototypeReflection[]
     */
    private array $methodPrototypes;
    private ?ExtendedMethodReflection $transformedMethod = null;
    private ?self $cachedDoNotResolveTemplateTypeMapToBounds = null;
    /**
     * @param UnresolvedMethodPrototypeReflection[] $methodPrototypes
     */
    public function __construct(string $methodName, array $methodPrototypes)
    {
        $this->methodName = $methodName;
        $this->methodPrototypes = $methodPrototypes;
    }
    public function doNotResolveTemplateTypeMapToBounds(): \PHPStan\Reflection\Type\UnresolvedMethodPrototypeReflection
    {
        if ($this->cachedDoNotResolveTemplateTypeMapToBounds !== null) {
            return $this->cachedDoNotResolveTemplateTypeMapToBounds;
        }
        return $this->cachedDoNotResolveTemplateTypeMapToBounds = new self($this->methodName, array_map(static fn(\PHPStan\Reflection\Type\UnresolvedMethodPrototypeReflection $prototype): \PHPStan\Reflection\Type\UnresolvedMethodPrototypeReflection => $prototype->doNotResolveTemplateTypeMapToBounds(), $this->methodPrototypes));
    }
    public function getNakedMethod(): ExtendedMethodReflection
    {
        return $this->getTransformedMethod();
    }
    public function getTransformedMethod(): ExtendedMethodReflection
    {
        if ($this->transformedMethod !== null) {
            return $this->transformedMethod;
        }
        $methods = array_map(static fn(\PHPStan\Reflection\Type\UnresolvedMethodPrototypeReflection $prototype): MethodReflection => $prototype->getTransformedMethod(), $this->methodPrototypes);
        return $this->transformedMethod = new \PHPStan\Reflection\Type\UnionTypeMethodReflection($this->methodName, $methods);
    }
    public function withCalledOnType(Type $type): \PHPStan\Reflection\Type\UnresolvedMethodPrototypeReflection
    {
        return new self($this->methodName, array_map(static fn(\PHPStan\Reflection\Type\UnresolvedMethodPrototypeReflection $prototype): \PHPStan\Reflection\Type\UnresolvedMethodPrototypeReflection => $prototype->withCalledOnType($type), $this->methodPrototypes));
    }
}
