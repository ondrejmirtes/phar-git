<?php

declare (strict_types=1);
namespace PHPStan\Rules\Properties;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function sprintf;
/**
 * @implements Rule<Node\Expr\AssignRef>
 */
final class PropertyAssignRefRule implements Rule
{
    private PhpVersion $phpVersion;
    private \PHPStan\Rules\Properties\PropertyReflectionFinder $propertyReflectionFinder;
    public function __construct(PhpVersion $phpVersion, \PHPStan\Rules\Properties\PropertyReflectionFinder $propertyReflectionFinder)
    {
        $this->phpVersion = $phpVersion;
        $this->propertyReflectionFinder = $propertyReflectionFinder;
    }
    public function getNodeType() : string
    {
        return Node\Expr\AssignRef::class;
    }
    public function processNode(Node $node, Scope $scope) : array
    {
        if (!$this->phpVersion->supportsAsymmetricVisibility()) {
            return [];
        }
        if (!$node->expr instanceof Node\Expr\PropertyFetch) {
            return [];
        }
        $propertyFetch = $node->expr;
        $errors = [];
        $reflections = $this->propertyReflectionFinder->findPropertyReflectionsFromNode($propertyFetch, $scope);
        foreach ($reflections as $propertyReflection) {
            $nativeReflection = $propertyReflection->getNativeReflection();
            if ($nativeReflection === null) {
                continue;
            }
            if ($scope->canWriteProperty($propertyReflection)) {
                continue;
            }
            $declaringClass = $nativeReflection->getDeclaringClass();
            $errors[] = RuleErrorBuilder::message(sprintf('Property %s::$%s with %s visibility is assigned by reference.', $declaringClass->getDisplayName(), $propertyReflection->getName(), $propertyReflection->isPrivateSet() ? 'private(set)' : ($propertyReflection->isProtectedSet() ? 'protected(set)' : ($propertyReflection->isPrivate() ? 'private' : 'protected'))))->identifier('property.assignByRef')->build();
        }
        return $errors;
    }
}
