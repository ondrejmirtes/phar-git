<?php

declare (strict_types=1);
namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function sprintf;
use function strtolower;
/**
 * @implements Rule<Node\Expr\FuncCall>
 */
final class CallToNonExistentFunctionRule implements Rule
{
    private ReflectionProvider $reflectionProvider;
    private bool $checkFunctionNameCase;
    private bool $discoveringSymbolsTip;
    public function __construct(ReflectionProvider $reflectionProvider, bool $checkFunctionNameCase, bool $discoveringSymbolsTip)
    {
        $this->reflectionProvider = $reflectionProvider;
        $this->checkFunctionNameCase = $checkFunctionNameCase;
        $this->discoveringSymbolsTip = $discoveringSymbolsTip;
    }
    public function getNodeType() : string
    {
        return FuncCall::class;
    }
    public function processNode(Node $node, Scope $scope) : array
    {
        if (!$node->name instanceof Node\Name) {
            return [];
        }
        if (!$this->reflectionProvider->hasFunction($node->name, $scope)) {
            if ($scope->isInFunctionExists($node->name->toString())) {
                return [];
            }
            $errorBuilder = RuleErrorBuilder::message(sprintf('Function %s not found.', (string) $node->name))->identifier('function.notFound');
            if ($this->discoveringSymbolsTip) {
                $errorBuilder->discoveringSymbolsTip();
            }
            return [$errorBuilder->build()];
        }
        $function = $this->reflectionProvider->getFunction($node->name, $scope);
        $name = (string) $node->name;
        if ($this->checkFunctionNameCase) {
            /** @var string $calledFunctionName */
            $calledFunctionName = $this->reflectionProvider->resolveFunctionName($node->name, $scope);
            if (strtolower($function->getName()) === strtolower($calledFunctionName) && $function->getName() !== $calledFunctionName) {
                return [RuleErrorBuilder::message(sprintf('Call to function %s() with incorrect case: %s', $function->getName(), $name))->identifier('function.nameCase')->build()];
            }
        }
        return [];
    }
}
