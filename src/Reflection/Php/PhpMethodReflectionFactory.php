<?php

declare (strict_types=1);
namespace PHPStan\Reflection\Php;

use PHPStan\BetterReflection\Reflection\Adapter\ReflectionMethod;
use PHPStan\Reflection\Assertions;
use PHPStan\Reflection\AttributeReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Type;
interface PhpMethodReflectionFactory
{
    /**
     * @param Type[] $phpDocParameterTypes
     * @param Type[] $phpDocParameterOutTypes
     * @param array<string, TrinaryLogic> $immediatelyInvokedCallableParameters
     * @param array<string, Type> $phpDocClosureThisTypeParameters
     * @param list<AttributeReflection> $attributes
     */
    public function create(ClassReflection $declaringClass, ?ClassReflection $declaringTrait, ReflectionMethod $reflection, TemplateTypeMap $templateTypeMap, array $phpDocParameterTypes, ?Type $phpDocReturnType, ?Type $phpDocThrowType, ?string $deprecatedDescription, bool $isDeprecated, bool $isInternal, bool $isFinal, ?bool $isPure, Assertions $asserts, ?Type $selfOutType, ?string $phpDocComment, array $phpDocParameterOutTypes, array $immediatelyInvokedCallableParameters, array $phpDocClosureThisTypeParameters, bool $acceptsNamedArguments, array $attributes): \PHPStan\Reflection\Php\PhpMethodReflection;
}
