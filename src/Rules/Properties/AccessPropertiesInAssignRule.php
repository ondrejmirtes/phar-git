<?php

declare (strict_types=1);
namespace PHPStan\Rules\Properties;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\PropertyAssignNode;
use PHPStan\Rules\Rule;
/**
 * @implements Rule<PropertyAssignNode>
 */
final class AccessPropertiesInAssignRule implements Rule
{
    private \PHPStan\Rules\Properties\AccessPropertiesCheck $check;
    public function __construct(\PHPStan\Rules\Properties\AccessPropertiesCheck $check)
    {
        $this->check = $check;
    }
    public function getNodeType(): string
    {
        return PropertyAssignNode::class;
    }
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node->getPropertyFetch() instanceof Node\Expr\PropertyFetch) {
            return [];
        }
        if ($node->isAssignOp()) {
            return [];
        }
        return $this->check->check($node->getPropertyFetch(), $scope, \true);
    }
}
