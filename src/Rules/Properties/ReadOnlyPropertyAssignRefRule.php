<?php

declare (strict_types=1);
namespace PHPStan\Rules\Properties;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function sprintf;
/**
 * @implements Rule<Node\Expr\AssignRef>
 */
final class ReadOnlyPropertyAssignRefRule implements Rule
{
    private \PHPStan\Rules\Properties\PropertyReflectionFinder $propertyReflectionFinder;
    public function __construct(\PHPStan\Rules\Properties\PropertyReflectionFinder $propertyReflectionFinder)
    {
        $this->propertyReflectionFinder = $propertyReflectionFinder;
    }
    public function getNodeType(): string
    {
        return Node\Expr\AssignRef::class;
    }
    public function processNode(Node $node, Scope $scope): array
    {
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
            if (!$scope->canWriteProperty($propertyReflection)) {
                continue;
            }
            if (!$nativeReflection->isReadOnly()) {
                continue;
            }
            $declaringClass = $nativeReflection->getDeclaringClass();
            $errors[] = RuleErrorBuilder::message(sprintf('Readonly property %s::$%s is assigned by reference.', $declaringClass->getDisplayName(), $propertyReflection->getName()))->identifier('property.readOnlyAssignByRef')->build();
        }
        return $errors;
    }
}
