<?php

declare (strict_types=1);
namespace PHPStan\Rules\Classes;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InTraitNode;
use PHPStan\Rules\Rule;
/**
 * @implements Rule<InTraitNode>
 */
final class PropertyTagTraitUseRule implements Rule
{
    private \PHPStan\Rules\Classes\PropertyTagCheck $check;
    public function __construct(\PHPStan\Rules\Classes\PropertyTagCheck $check)
    {
        $this->check = $check;
    }
    public function getNodeType(): string
    {
        return InTraitNode::class;
    }
    public function processNode(Node $node, Scope $scope): array
    {
        return $this->check->checkInTraitUseContext($scope, $node->getTraitReflection(), $node->getImplementingClassReflection(), $node->getOriginalNode());
    }
}
