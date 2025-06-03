<?php

declare (strict_types=1);
namespace PHPStan\Reflection\Php;

use PHPStan\Reflection\Annotations\AnnotationsPropertiesClassReflectionExtension;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\PropertiesClassReflectionExtension;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\MixedType;
final class UniversalObjectCratesClassReflectionExtension implements PropertiesClassReflectionExtension
{
    private ReflectionProvider $reflectionProvider;
    /**
     * @var list<string>
     */
    private array $classes;
    private AnnotationsPropertiesClassReflectionExtension $annotationClassReflection;
    /**
     * @param list<string> $classes
     */
    public function __construct(ReflectionProvider $reflectionProvider, array $classes, AnnotationsPropertiesClassReflectionExtension $annotationClassReflection)
    {
        $this->reflectionProvider = $reflectionProvider;
        $this->classes = $classes;
        $this->annotationClassReflection = $annotationClassReflection;
    }
    public function hasProperty(ClassReflection $classReflection, string $propertyName): bool
    {
        return self::isUniversalObjectCrateImplementation($this->reflectionProvider, $this->classes, $classReflection);
    }
    public static function isUniversalObjectCrate(ReflectionProvider $reflectionProvider, ClassReflection $classReflection): bool
    {
        return self::isUniversalObjectCrateImplementation($reflectionProvider, $reflectionProvider->getUniversalObjectCratesClasses(), $classReflection);
    }
    /**
     * @param list<string> $classes
     */
    private static function isUniversalObjectCrateImplementation(ReflectionProvider $reflectionProvider, array $classes, ClassReflection $classReflection): bool
    {
        foreach ($classes as $className) {
            if (!$reflectionProvider->hasClass($className)) {
                continue;
            }
            if ($classReflection->is($className)) {
                return \true;
            }
        }
        return \false;
    }
    public function getProperty(ClassReflection $classReflection, string $propertyName): PropertyReflection
    {
        if ($this->annotationClassReflection->hasProperty($classReflection, $propertyName)) {
            return $this->annotationClassReflection->getProperty($classReflection, $propertyName);
        }
        if ($classReflection->hasNativeMethod('__get')) {
            $readableType = $classReflection->getNativeMethod('__get')->getOnlyVariant()->getReturnType();
        } else {
            $readableType = new MixedType();
        }
        if ($classReflection->hasNativeMethod('__set')) {
            $writableType = $classReflection->getNativeMethod('__set')->getOnlyVariant()->getParameters()[1]->getType();
        } else {
            $writableType = new MixedType();
        }
        return new \PHPStan\Reflection\Php\UniversalObjectCrateProperty($propertyName, $classReflection, $readableType, $writableType);
    }
}
