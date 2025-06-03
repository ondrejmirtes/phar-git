<?php

declare (strict_types=1);
namespace PHPStan\Rules\RestrictedUsage;

use PhpParser\Node;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\Container;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
/**
 * @implements Rule<Node\Expr\PropertyFetch>
 */
#[\PHPStan\DependencyInjection\AutowiredService]
final class RestrictedPropertyUsageRule implements Rule
{
    private Container $container;
    private ReflectionProvider $reflectionProvider;
    public function __construct(Container $container, ReflectionProvider $reflectionProvider)
    {
        $this->container = $container;
        $this->reflectionProvider = $reflectionProvider;
    }
    public function getNodeType(): string
    {
        return Node\Expr\PropertyFetch::class;
    }
    /**
     * @api
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node->name instanceof Identifier) {
            return [];
        }
        /** @var RestrictedPropertyUsageExtension[] $extensions */
        $extensions = $this->container->getServicesByTag(\PHPStan\Rules\RestrictedUsage\RestrictedPropertyUsageExtension::PROPERTY_EXTENSION_TAG);
        if ($extensions === []) {
            return [];
        }
        $propertyName = $node->name->name;
        $propertyCalledOnType = $scope->getType($node->var);
        $referencedClasses = $propertyCalledOnType->getObjectClassNames();
        $errors = [];
        foreach ($referencedClasses as $referencedClass) {
            if (!$this->reflectionProvider->hasClass($referencedClass)) {
                continue;
            }
            $classReflection = $this->reflectionProvider->getClass($referencedClass);
            if (!$classReflection->hasProperty($propertyName)) {
                continue;
            }
            $propertyReflection = $classReflection->getProperty($propertyName, $scope);
            foreach ($extensions as $extension) {
                $restrictedUsage = $extension->isRestrictedPropertyUsage($propertyReflection, $scope);
                if ($restrictedUsage === null) {
                    continue;
                }
                $errors[] = RuleErrorBuilder::message($restrictedUsage->errorMessage)->identifier($restrictedUsage->identifier)->build();
            }
        }
        return $errors;
    }
}
