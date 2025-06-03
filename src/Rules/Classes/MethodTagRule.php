<?php

declare (strict_types=1);
namespace PHPStan\Rules\Classes;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\Rule;
/**
 * @implements Rule<InClassNode>
 */
final class MethodTagRule implements Rule
{
    private \PHPStan\Rules\Classes\MethodTagCheck $check;
    public function __construct(\PHPStan\Rules\Classes\MethodTagCheck $check)
    {
        $this->check = $check;
    }
    public function getNodeType() : string
    {
        return InClassNode::class;
    }
    public function processNode(Node $node, Scope $scope) : array
    {
        return $this->check->check($scope, $node->getClassReflection(), $node->getOriginalNode());
    }
}
