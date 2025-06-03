<?php

declare (strict_types=1);
namespace PHPStan\Reflection;

use PHPStan\Reflection\RequireExtension\RequireExtendsMethodsClassReflectionExtension;
use PHPStan\Reflection\RequireExtension\RequireExtendsPropertiesClassReflectionExtension;
final class ClassReflectionExtensionRegistry
{
    /**
     * @var PropertiesClassReflectionExtension[]
     */
    private array $propertiesClassReflectionExtensions;
    /**
     * @var MethodsClassReflectionExtension[]
     */
    private array $methodsClassReflectionExtensions;
    /**
     * @var AllowedSubTypesClassReflectionExtension[]
     */
    private array $allowedSubTypesClassReflectionExtensions;
    private RequireExtendsPropertiesClassReflectionExtension $requireExtendsPropertiesClassReflectionExtension;
    private RequireExtendsMethodsClassReflectionExtension $requireExtendsMethodsClassReflectionExtension;
    /**
     * @param PropertiesClassReflectionExtension[] $propertiesClassReflectionExtensions
     * @param MethodsClassReflectionExtension[] $methodsClassReflectionExtensions
     * @param AllowedSubTypesClassReflectionExtension[] $allowedSubTypesClassReflectionExtensions
     */
    public function __construct(array $propertiesClassReflectionExtensions, array $methodsClassReflectionExtensions, array $allowedSubTypesClassReflectionExtensions, RequireExtendsPropertiesClassReflectionExtension $requireExtendsPropertiesClassReflectionExtension, RequireExtendsMethodsClassReflectionExtension $requireExtendsMethodsClassReflectionExtension)
    {
        $this->propertiesClassReflectionExtensions = $propertiesClassReflectionExtensions;
        $this->methodsClassReflectionExtensions = $methodsClassReflectionExtensions;
        $this->allowedSubTypesClassReflectionExtensions = $allowedSubTypesClassReflectionExtensions;
        $this->requireExtendsPropertiesClassReflectionExtension = $requireExtendsPropertiesClassReflectionExtension;
        $this->requireExtendsMethodsClassReflectionExtension = $requireExtendsMethodsClassReflectionExtension;
    }
    /**
     * @return PropertiesClassReflectionExtension[]
     */
    public function getPropertiesClassReflectionExtensions(): array
    {
        return $this->propertiesClassReflectionExtensions;
    }
    /**
     * @return MethodsClassReflectionExtension[]
     */
    public function getMethodsClassReflectionExtensions(): array
    {
        return $this->methodsClassReflectionExtensions;
    }
    /**
     * @return AllowedSubTypesClassReflectionExtension[]
     */
    public function getAllowedSubTypesClassReflectionExtensions(): array
    {
        return $this->allowedSubTypesClassReflectionExtensions;
    }
    public function getRequireExtendsPropertyClassReflectionExtension(): RequireExtendsPropertiesClassReflectionExtension
    {
        return $this->requireExtendsPropertiesClassReflectionExtension;
    }
    public function getRequireExtendsMethodsClassReflectionExtension(): RequireExtendsMethodsClassReflectionExtension
    {
        return $this->requireExtendsMethodsClassReflectionExtension;
    }
}
