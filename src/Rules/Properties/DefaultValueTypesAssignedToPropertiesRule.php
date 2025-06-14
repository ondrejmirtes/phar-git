<?php

declare (strict_types=1);
namespace PHPStan\Rules\Properties;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\ClassPropertyNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\VerbosityLevel;
use function sprintf;
/**
 * @implements Rule<ClassPropertyNode>
 */
final class DefaultValueTypesAssignedToPropertiesRule implements Rule
{
    private RuleLevelHelper $ruleLevelHelper;
    public function __construct(RuleLevelHelper $ruleLevelHelper)
    {
        $this->ruleLevelHelper = $ruleLevelHelper;
    }
    public function getNodeType(): string
    {
        return ClassPropertyNode::class;
    }
    public function processNode(Node $node, Scope $scope): array
    {
        $default = $node->getDefault();
        if ($default === null) {
            return [];
        }
        $classReflection = $node->getClassReflection();
        $propertyReflection = $classReflection->getNativeProperty($node->getName());
        $propertyType = $propertyReflection->getWritableType();
        if (!$propertyReflection->hasNativeType()) {
            if ($default instanceof Node\Expr\ConstFetch && $default->name->toLowerString() === 'null') {
                return [];
            }
        }
        $defaultValueType = $scope->getType($default);
        $accepts = $this->ruleLevelHelper->accepts($propertyType, $defaultValueType, \true);
        if ($accepts->result) {
            return [];
        }
        $verbosityLevel = VerbosityLevel::getRecommendedLevelByType($propertyType, $defaultValueType);
        return [RuleErrorBuilder::message(sprintf('%s %s::$%s (%s) does not accept default value of type %s.', $node->isStatic() ? 'Static property' : 'Property', $classReflection->getDisplayName(), $node->getName(), $propertyType->describe($verbosityLevel), $defaultValueType->describe($verbosityLevel)))->identifier('property.defaultValue')->acceptsReasonsTip($accepts->reasons)->build()];
    }
}
