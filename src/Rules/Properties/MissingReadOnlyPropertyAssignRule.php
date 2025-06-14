<?php

declare (strict_types=1);
namespace PHPStan\Rules\Properties;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\ClassPropertiesNode;
use PHPStan\Reflection\ConstructorsHelper;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function sprintf;
/**
 * @implements Rule<ClassPropertiesNode>
 */
final class MissingReadOnlyPropertyAssignRule implements Rule
{
    private ConstructorsHelper $constructorsHelper;
    public function __construct(ConstructorsHelper $constructorsHelper)
    {
        $this->constructorsHelper = $constructorsHelper;
    }
    public function getNodeType(): string
    {
        return ClassPropertiesNode::class;
    }
    public function processNode(Node $node, Scope $scope): array
    {
        $classReflection = $node->getClassReflection();
        [$properties, $prematureAccess, $additionalAssigns] = $node->getUninitializedProperties($scope, $this->constructorsHelper->getConstructors($classReflection));
        $errors = [];
        foreach ($properties as $propertyName => $propertyNode) {
            if (!$propertyNode->isReadOnly()) {
                continue;
            }
            $errors[] = RuleErrorBuilder::message(sprintf('Class %s has an uninitialized readonly property $%s. Assign it in the constructor.', $classReflection->getDisplayName(), $propertyName))->line($propertyNode->getStartLine())->identifier('property.uninitializedReadonly')->build();
        }
        foreach ($prematureAccess as [$propertyName, $line, $propertyNode, $file, $fileDescription]) {
            if (!$propertyNode->isReadOnly()) {
                continue;
            }
            $errors[] = RuleErrorBuilder::message(sprintf('Access to an uninitialized readonly property %s::$%s.', $classReflection->getDisplayName(), $propertyName))->line($line)->file($file, $fileDescription)->identifier('property.uninitializedReadonly')->build();
        }
        foreach ($additionalAssigns as [$propertyName, $line, $propertyNode]) {
            if (!$propertyNode->isReadOnly()) {
                continue;
            }
            $errors[] = RuleErrorBuilder::message(sprintf('Readonly property %s::$%s is already assigned.', $classReflection->getDisplayName(), $propertyName))->line($line)->identifier('assign.readOnlyProperty')->build();
        }
        return $errors;
    }
}
