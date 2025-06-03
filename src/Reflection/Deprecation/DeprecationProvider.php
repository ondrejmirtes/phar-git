<?php

declare (strict_types=1);
namespace PHPStan\Reflection\Deprecation;

use PHPStan\BetterReflection\Reflection\Adapter\ReflectionClass;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionClassConstant;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionEnum;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionEnumBackedCase;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionEnumUnitCase;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionFunction;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionMethod;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionProperty;
use PHPStan\BetterReflection\Reflection\ReflectionConstant;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\Container;
#[\PHPStan\DependencyInjection\AutowiredService]
final class DeprecationProvider
{
    private Container $container;
    /** @var ?array<PropertyDeprecationExtension> $propertyDeprecationExtensions */
    private ?array $propertyDeprecationExtensions = null;
    /** @var ?array<MethodDeprecationExtension> $methodDeprecationExtensions */
    private ?array $methodDeprecationExtensions = null;
    /** @var ?array<ClassConstantDeprecationExtension> $classConstantDeprecationExtensions */
    private ?array $classConstantDeprecationExtensions = null;
    /** @var ?array<ClassDeprecationExtension> $classDeprecationExtensions */
    private ?array $classDeprecationExtensions = null;
    /** @var ?array<FunctionDeprecationExtension> $functionDeprecationExtensions */
    private ?array $functionDeprecationExtensions = null;
    /** @var ?array<ConstantDeprecationExtension> $constantDeprecationExtensions */
    private ?array $constantDeprecationExtensions = null;
    /** @var ?array<EnumCaseDeprecationExtension> $enumCaseDeprecationExtensions */
    private ?array $enumCaseDeprecationExtensions = null;
    public function __construct(Container $container)
    {
        $this->container = $container;
    }
    public function getPropertyDeprecation(ReflectionProperty $reflectionProperty) : ?\PHPStan\Reflection\Deprecation\Deprecation
    {
        $this->propertyDeprecationExtensions ??= $this->container->getServicesByTag(\PHPStan\Reflection\Deprecation\PropertyDeprecationExtension::PROPERTY_EXTENSION_TAG);
        foreach ($this->propertyDeprecationExtensions as $extension) {
            $deprecation = $extension->getPropertyDeprecation($reflectionProperty);
            if ($deprecation !== null) {
                return $deprecation;
            }
        }
        return null;
    }
    public function getMethodDeprecation(ReflectionMethod $methodReflection) : ?\PHPStan\Reflection\Deprecation\Deprecation
    {
        $this->methodDeprecationExtensions ??= $this->container->getServicesByTag(\PHPStan\Reflection\Deprecation\MethodDeprecationExtension::METHOD_EXTENSION_TAG);
        foreach ($this->methodDeprecationExtensions as $extension) {
            $deprecation = $extension->getMethodDeprecation($methodReflection);
            if ($deprecation !== null) {
                return $deprecation;
            }
        }
        return null;
    }
    public function getClassConstantDeprecation(ReflectionClassConstant $reflectionConstant) : ?\PHPStan\Reflection\Deprecation\Deprecation
    {
        $this->classConstantDeprecationExtensions ??= $this->container->getServicesByTag(\PHPStan\Reflection\Deprecation\ClassConstantDeprecationExtension::CLASS_CONSTANT_EXTENSION_TAG);
        foreach ($this->classConstantDeprecationExtensions as $extension) {
            $deprecation = $extension->getClassConstantDeprecation($reflectionConstant);
            if ($deprecation !== null) {
                return $deprecation;
            }
        }
        return null;
    }
    /**
     * @param \PHPStan\BetterReflection\Reflection\Adapter\ReflectionClass|\PHPStan\BetterReflection\Reflection\Adapter\ReflectionEnum $reflection
     */
    public function getClassDeprecation($reflection) : ?\PHPStan\Reflection\Deprecation\Deprecation
    {
        $this->classDeprecationExtensions ??= $this->container->getServicesByTag(\PHPStan\Reflection\Deprecation\ClassDeprecationExtension::CLASS_EXTENSION_TAG);
        foreach ($this->classDeprecationExtensions as $extension) {
            $deprecation = $extension->getClassDeprecation($reflection);
            if ($deprecation !== null) {
                return $deprecation;
            }
        }
        return null;
    }
    public function getFunctionDeprecation(ReflectionFunction $reflectionFunction) : ?\PHPStan\Reflection\Deprecation\Deprecation
    {
        $this->functionDeprecationExtensions ??= $this->container->getServicesByTag(\PHPStan\Reflection\Deprecation\FunctionDeprecationExtension::FUNCTION_EXTENSION_TAG);
        foreach ($this->functionDeprecationExtensions as $extension) {
            $deprecation = $extension->getFunctionDeprecation($reflectionFunction);
            if ($deprecation !== null) {
                return $deprecation;
            }
        }
        return null;
    }
    public function getConstantDeprecation(ReflectionConstant $constantReflection) : ?\PHPStan\Reflection\Deprecation\Deprecation
    {
        $this->constantDeprecationExtensions ??= $this->container->getServicesByTag(\PHPStan\Reflection\Deprecation\ConstantDeprecationExtension::CONSTANT_EXTENSION_TAG);
        foreach ($this->constantDeprecationExtensions as $extension) {
            $deprecation = $extension->getConstantDeprecation($constantReflection);
            if ($deprecation !== null) {
                return $deprecation;
            }
        }
        return null;
    }
    /**
     * @param \PHPStan\BetterReflection\Reflection\Adapter\ReflectionEnumUnitCase|\PHPStan\BetterReflection\Reflection\Adapter\ReflectionEnumBackedCase $enumCaseReflection
     */
    public function getEnumCaseDeprecation($enumCaseReflection) : ?\PHPStan\Reflection\Deprecation\Deprecation
    {
        $this->enumCaseDeprecationExtensions ??= $this->container->getServicesByTag(\PHPStan\Reflection\Deprecation\EnumCaseDeprecationExtension::ENUM_CASE_EXTENSION_TAG);
        foreach ($this->enumCaseDeprecationExtensions as $extension) {
            $deprecation = $extension->getEnumCaseDeprecation($enumCaseReflection);
            if ($deprecation !== null) {
                return $deprecation;
            }
        }
        return null;
    }
}
