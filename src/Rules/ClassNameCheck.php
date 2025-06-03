<?php

declare (strict_types=1);
namespace PHPStan\Rules;

use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\Container;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\RestrictedUsage\RestrictedClassNameUsageExtension;
#[\PHPStan\DependencyInjection\AutowiredService]
final class ClassNameCheck
{
    private \PHPStan\Rules\ClassCaseSensitivityCheck $classCaseSensitivityCheck;
    private \PHPStan\Rules\ClassForbiddenNameCheck $classForbiddenNameCheck;
    private ReflectionProvider $reflectionProvider;
    private Container $container;
    public function __construct(\PHPStan\Rules\ClassCaseSensitivityCheck $classCaseSensitivityCheck, \PHPStan\Rules\ClassForbiddenNameCheck $classForbiddenNameCheck, ReflectionProvider $reflectionProvider, Container $container)
    {
        $this->classCaseSensitivityCheck = $classCaseSensitivityCheck;
        $this->classForbiddenNameCheck = $classForbiddenNameCheck;
        $this->reflectionProvider = $reflectionProvider;
        $this->container = $container;
    }
    /**
     * @param ClassNameNodePair[] $pairs
     * @return list<IdentifierRuleError>
     */
    public function checkClassNames(Scope $scope, array $pairs, ?\PHPStan\Rules\ClassNameUsageLocation $location, bool $checkClassCaseSensitivity = \true): array
    {
        $errors = [];
        if ($checkClassCaseSensitivity) {
            foreach ($this->classCaseSensitivityCheck->checkClassNames($pairs) as $error) {
                $errors[] = $error;
            }
        }
        foreach ($this->classForbiddenNameCheck->checkClassNames($pairs) as $error) {
            $errors[] = $error;
        }
        if ($location === null) {
            return $errors;
        }
        /** @var RestrictedClassNameUsageExtension[] $extensions */
        $extensions = $this->container->getServicesByTag(RestrictedClassNameUsageExtension::CLASS_NAME_EXTENSION_TAG);
        if ($extensions === []) {
            return $errors;
        }
        foreach ($pairs as $pair) {
            if (!$this->reflectionProvider->hasClass($pair->getClassName())) {
                continue;
            }
            $classReflection = $this->reflectionProvider->getClass($pair->getClassName());
            foreach ($extensions as $extension) {
                $restrictedUsage = $extension->isRestrictedClassNameUsage($classReflection, $scope, $location);
                if ($restrictedUsage === null) {
                    continue;
                }
                $errors[] = \PHPStan\Rules\RuleErrorBuilder::message($restrictedUsage->errorMessage)->identifier($restrictedUsage->identifier)->line($pair->getNode()->getStartLine())->build();
            }
        }
        return $errors;
    }
}
