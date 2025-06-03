<?php

declare (strict_types=1);
namespace PHPStan\Rules\Functions;

use Attribute;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\AttributesCheck;
use PHPStan\Rules\Rule;
/**
 * @implements Rule<Node\Param>
 */
final class ParamAttributesRule implements Rule
{
    private AttributesCheck $attributesCheck;
    public function __construct(AttributesCheck $attributesCheck)
    {
        $this->attributesCheck = $attributesCheck;
    }
    public function getNodeType(): string
    {
        return Node\Param::class;
    }
    public function processNode(Node $node, Scope $scope): array
    {
        $targetName = 'parameter';
        $targetType = Attribute::TARGET_PARAMETER;
        if ($node->flags !== 0) {
            $targetName = 'parameter or property';
            $targetType |= Attribute::TARGET_PROPERTY;
        }
        return $this->attributesCheck->check($scope, $node->attrGroups, $targetType, $targetName);
    }
}
