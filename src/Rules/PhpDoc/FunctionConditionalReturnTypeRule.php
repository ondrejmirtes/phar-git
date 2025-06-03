<?php

declare (strict_types=1);
namespace PHPStan\Rules\PhpDoc;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InFunctionNode;
use PHPStan\Rules\Rule;
use function count;
/**
 * @implements Rule<InFunctionNode>
 */
final class FunctionConditionalReturnTypeRule implements Rule
{
    private \PHPStan\Rules\PhpDoc\ConditionalReturnTypeRuleHelper $helper;
    public function __construct(\PHPStan\Rules\PhpDoc\ConditionalReturnTypeRuleHelper $helper)
    {
        $this->helper = $helper;
    }
    public function getNodeType(): string
    {
        return InFunctionNode::class;
    }
    public function processNode(Node $node, Scope $scope): array
    {
        $function = $node->getFunctionReflection();
        $variants = $function->getVariants();
        if (count($variants) !== 1) {
            return [];
        }
        return $this->helper->check($variants[0]);
    }
}
