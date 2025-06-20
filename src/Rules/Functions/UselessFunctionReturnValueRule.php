<?php

declare (strict_types=1);
namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\ArgumentsNormalizer;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function array_key_exists;
use function count;
use function sprintf;
/**
 * @implements Rule<Node\Expr\FuncCall>
 */
final class UselessFunctionReturnValueRule implements Rule
{
    private ReflectionProvider $reflectionProvider;
    private const USELESS_FUNCTIONS = ['var_export' => 'null', 'print_r' => 'true', 'highlight_string' => 'true'];
    public function __construct(ReflectionProvider $reflectionProvider)
    {
        $this->reflectionProvider = $reflectionProvider;
    }
    public function getNodeType(): string
    {
        return FuncCall::class;
    }
    public function processNode(Node $funcCall, Scope $scope): array
    {
        if (!$funcCall->name instanceof Node\Name || $scope->isInFirstLevelStatement()) {
            return [];
        }
        if (!$this->reflectionProvider->hasFunction($funcCall->name, $scope)) {
            return [];
        }
        $functionReflection = $this->reflectionProvider->getFunction($funcCall->name, $scope);
        if (!array_key_exists($functionReflection->getName(), self::USELESS_FUNCTIONS)) {
            return [];
        }
        $parametersAcceptor = ParametersAcceptorSelector::selectFromArgs($scope, $funcCall->getArgs(), $functionReflection->getVariants(), $functionReflection->getNamedArgumentsVariants());
        $reorderedFuncCall = ArgumentsNormalizer::reorderFuncArguments($parametersAcceptor, $funcCall);
        if ($reorderedFuncCall === null) {
            return [];
        }
        $reorderedArgs = $reorderedFuncCall->getArgs();
        if (count($reorderedArgs) === 1 || count($reorderedArgs) >= 2 && $scope->getType($reorderedArgs[1]->value)->isFalse()->yes()) {
            return [RuleErrorBuilder::message(sprintf('Return value of function %s() is always %s and the result is printed instead of being returned. Pass in true as parameter #%d $%s to return the output instead.', $functionReflection->getName(), self::USELESS_FUNCTIONS[$functionReflection->getName()], 2, $parametersAcceptor->getParameters()[1]->getName()))->identifier('function.uselessReturnValue')->line($funcCall->getStartLine())->build()];
        }
        return [];
    }
}
