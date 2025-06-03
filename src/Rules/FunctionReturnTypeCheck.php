<?php

declare (strict_types=1);
namespace PHPStan\Rules;

use Generator;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Type\ErrorType;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\VerbosityLevel;
use function sprintf;
#[\PHPStan\DependencyInjection\AutowiredService]
final class FunctionReturnTypeCheck
{
    private \PHPStan\Rules\RuleLevelHelper $ruleLevelHelper;
    public function __construct(\PHPStan\Rules\RuleLevelHelper $ruleLevelHelper)
    {
        $this->ruleLevelHelper = $ruleLevelHelper;
    }
    /**
     * @return list<IdentifierRuleError>
     */
    public function checkReturnType(Scope $scope, Type $returnType, ?Expr $returnValue, Node $returnNode, string $emptyReturnStatementMessage, string $voidMessage, string $typeMismatchMessage, string $neverMessage, bool $isGenerator): array
    {
        $returnType = TypeUtils::resolveLateResolvableTypes($returnType);
        if ($returnType instanceof NeverType && $returnType->isExplicit()) {
            return [\PHPStan\Rules\RuleErrorBuilder::message($neverMessage)->line($returnNode->getStartLine())->identifier('return.never')->build()];
        }
        if ($isGenerator) {
            $returnType = $returnType->getTemplateType(Generator::class, 'TReturn');
            if ($returnType instanceof ErrorType) {
                return [];
            }
        }
        $isVoidSuperType = $returnType->isVoid();
        $verbosityLevel = VerbosityLevel::getRecommendedLevelByType($returnType, null);
        if ($returnValue === null) {
            if (!$isVoidSuperType->no()) {
                return [];
            }
            return [\PHPStan\Rules\RuleErrorBuilder::message(sprintf($emptyReturnStatementMessage, $returnType->describe($verbosityLevel)))->line($returnNode->getStartLine())->identifier('return.empty')->build()];
        }
        if ($returnNode instanceof Expr\Yield_ || $returnNode instanceof Expr\YieldFrom) {
            return [];
        }
        $returnValueType = $scope->getType($returnValue);
        $verbosityLevel = VerbosityLevel::getRecommendedLevelByType($returnType, $returnValueType);
        if ($isVoidSuperType->yes()) {
            return [\PHPStan\Rules\RuleErrorBuilder::message(sprintf($voidMessage, $returnValueType->describe($verbosityLevel)))->line($returnNode->getStartLine())->identifier('return.void')->build()];
        }
        $accepts = $this->ruleLevelHelper->accepts($returnType, $returnValueType, $scope->isDeclareStrictTypes());
        if (!$accepts->result) {
            return [\PHPStan\Rules\RuleErrorBuilder::message(sprintf($typeMismatchMessage, $returnType->describe($verbosityLevel), $returnValueType->describe($verbosityLevel)))->line($returnNode->getStartLine())->identifier('return.type')->acceptsReasonsTip($accepts->reasons)->build()];
        }
        return [];
    }
}
