<?php

declare (strict_types=1);
namespace PHPStan\Rules\Functions;

use Attribute;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClosureNode;
use PHPStan\Rules\AttributesCheck;
use PHPStan\Rules\Rule;
/**
 * @implements Rule<InClosureNode>
 */
final class ClosureAttributesRule implements Rule
{
    private AttributesCheck $attributesCheck;
    public function __construct(AttributesCheck $attributesCheck)
    {
        $this->attributesCheck = $attributesCheck;
    }
    public function getNodeType(): string
    {
        return InClosureNode::class;
    }
    public function processNode(Node $node, Scope $scope): array
    {
        return $this->attributesCheck->check($scope, $node->getOriginalNode()->attrGroups, Attribute::TARGET_FUNCTION, 'function');
    }
}
