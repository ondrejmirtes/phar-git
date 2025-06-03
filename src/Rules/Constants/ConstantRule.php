<?php

declare (strict_types=1);
namespace PHPStan\Rules\Constants;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function sprintf;
/**
 * @implements Rule<Node\Expr\ConstFetch>
 */
final class ConstantRule implements Rule
{
    private bool $discoveringSymbolsTip;
    public function __construct(bool $discoveringSymbolsTip)
    {
        $this->discoveringSymbolsTip = $discoveringSymbolsTip;
    }
    public function getNodeType(): string
    {
        return Node\Expr\ConstFetch::class;
    }
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$scope->hasConstant($node->name)) {
            $errorBuilder = RuleErrorBuilder::message(sprintf('Constant %s not found.', (string) $node->name))->identifier('constant.notFound');
            if ($this->discoveringSymbolsTip) {
                $errorBuilder->discoveringSymbolsTip();
            }
            return [$errorBuilder->build()];
        }
        return [];
    }
}
