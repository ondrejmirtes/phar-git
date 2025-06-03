<?php

declare (strict_types=1);
namespace PHPStan\Rules\Properties;

use Attribute;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InPropertyHookNode;
use PHPStan\Rules\AttributesCheck;
use PHPStan\Rules\Rule;
/**
 * @implements Rule<InPropertyHookNode>
 */
final class PropertyHookAttributesRule implements Rule
{
    private AttributesCheck $attributesCheck;
    public function __construct(AttributesCheck $attributesCheck)
    {
        $this->attributesCheck = $attributesCheck;
    }
    public function getNodeType(): string
    {
        return InPropertyHookNode::class;
    }
    public function processNode(Node $node, Scope $scope): array
    {
        return $this->attributesCheck->check($scope, $node->getOriginalNode()->attrGroups, Attribute::TARGET_METHOD, 'method');
    }
}
