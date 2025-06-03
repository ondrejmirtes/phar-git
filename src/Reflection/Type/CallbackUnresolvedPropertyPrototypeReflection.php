<?php

declare (strict_types=1);
namespace PHPStan\Reflection\Type;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\Dummy\ChangedTypePropertyReflection;
use PHPStan\Reflection\ExtendedPropertyReflection;
use PHPStan\Reflection\ResolvedPropertyReflection;
use PHPStan\Type\Type;
final class CallbackUnresolvedPropertyPrototypeReflection implements \PHPStan\Reflection\Type\UnresolvedPropertyPrototypeReflection
{
    private ExtendedPropertyReflection $propertyReflection;
    private ClassReflection $resolvedDeclaringClass;
    private bool $resolveTemplateTypeMapToBounds;
    /** @var callable(Type): Type */
    private $transformStaticTypeCallback;
    private ?ExtendedPropertyReflection $transformedProperty = null;
    private ?self $cachedDoNotResolveTemplateTypeMapToBounds = null;
    /**
     * @param callable(Type): Type $transformStaticTypeCallback
     */
    public function __construct(ExtendedPropertyReflection $propertyReflection, ClassReflection $resolvedDeclaringClass, bool $resolveTemplateTypeMapToBounds, callable $transformStaticTypeCallback)
    {
        $this->propertyReflection = $propertyReflection;
        $this->resolvedDeclaringClass = $resolvedDeclaringClass;
        $this->resolveTemplateTypeMapToBounds = $resolveTemplateTypeMapToBounds;
        $this->transformStaticTypeCallback = $transformStaticTypeCallback;
    }
    public function doNotResolveTemplateTypeMapToBounds(): \PHPStan\Reflection\Type\UnresolvedPropertyPrototypeReflection
    {
        if ($this->cachedDoNotResolveTemplateTypeMapToBounds !== null) {
            return $this->cachedDoNotResolveTemplateTypeMapToBounds;
        }
        return $this->cachedDoNotResolveTemplateTypeMapToBounds = new self($this->propertyReflection, $this->resolvedDeclaringClass, \false, $this->transformStaticTypeCallback);
    }
    public function getNakedProperty(): ExtendedPropertyReflection
    {
        return $this->propertyReflection;
    }
    public function getTransformedProperty(): ExtendedPropertyReflection
    {
        if ($this->transformedProperty !== null) {
            return $this->transformedProperty;
        }
        $templateTypeMap = $this->resolvedDeclaringClass->getActiveTemplateTypeMap();
        $callSiteVarianceMap = $this->resolvedDeclaringClass->getCallSiteVarianceMap();
        return $this->transformedProperty = new ResolvedPropertyReflection($this->transformPropertyWithStaticType($this->resolvedDeclaringClass, $this->propertyReflection), $this->resolveTemplateTypeMapToBounds ? $templateTypeMap->resolveToBounds() : $templateTypeMap, $callSiteVarianceMap);
    }
    public function withFechedOnType(Type $type): \PHPStan\Reflection\Type\UnresolvedPropertyPrototypeReflection
    {
        return new \PHPStan\Reflection\Type\CalledOnTypeUnresolvedPropertyPrototypeReflection($this->propertyReflection, $this->resolvedDeclaringClass, $this->resolveTemplateTypeMapToBounds, $type);
    }
    private function transformPropertyWithStaticType(ClassReflection $declaringClass, ExtendedPropertyReflection $property): ExtendedPropertyReflection
    {
        $readableType = $this->transformStaticType($property->getReadableType());
        $writableType = $this->transformStaticType($property->getWritableType());
        $phpDocType = $this->transformStaticType($property->getPhpDocType());
        $nativeType = $this->transformStaticType($property->getNativeType());
        return new ChangedTypePropertyReflection($declaringClass, $property, $readableType, $writableType, $phpDocType, $nativeType);
    }
    private function transformStaticType(Type $type): Type
    {
        $callback = $this->transformStaticTypeCallback;
        return $callback($type);
    }
}
