<?php

declare (strict_types=1);
namespace PHPStan\Rules\Generators;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NullType;
use PHPStan\Type\VerbosityLevel;
use function sprintf;
/**
 * @implements Rule<Node\Expr\Yield_>
 */
final class YieldTypeRule implements Rule
{
    private RuleLevelHelper $ruleLevelHelper;
    public function __construct(RuleLevelHelper $ruleLevelHelper)
    {
        $this->ruleLevelHelper = $ruleLevelHelper;
    }
    public function getNodeType(): string
    {
        return Node\Expr\Yield_::class;
    }
    public function processNode(Node $node, Scope $scope): array
    {
        $anonymousFunctionReturnType = $scope->getAnonymousFunctionReturnType();
        $scopeFunction = $scope->getFunction();
        if ($anonymousFunctionReturnType !== null) {
            $returnType = $anonymousFunctionReturnType;
        } elseif ($scopeFunction !== null) {
            $returnType = $scopeFunction->getReturnType();
        } else {
            return [];
            // already reported by YieldInGeneratorRule
        }
        if ($returnType instanceof MixedType) {
            return [];
        }
        if ($node->key === null) {
            $keyType = new IntegerType();
        } else {
            $keyType = $scope->getType($node->key);
        }
        $messages = [];
        $acceptsKey = $this->ruleLevelHelper->accepts($returnType->getIterableKeyType(), $keyType, $scope->isDeclareStrictTypes());
        if (!$acceptsKey->result) {
            $verbosityLevel = VerbosityLevel::getRecommendedLevelByType($returnType->getIterableKeyType(), $keyType);
            $messages[] = RuleErrorBuilder::message(sprintf('Generator expects key type %s, %s given.', $returnType->getIterableKeyType()->describe($verbosityLevel), $keyType->describe($verbosityLevel)))->acceptsReasonsTip($acceptsKey->reasons)->identifier('generator.keyType')->build();
        }
        if ($node->value === null) {
            $valueType = new NullType();
        } else {
            $valueType = $scope->getType($node->value);
        }
        $acceptsValue = $this->ruleLevelHelper->accepts($returnType->getIterableValueType(), $valueType, $scope->isDeclareStrictTypes());
        if (!$acceptsValue->result) {
            $verbosityLevel = VerbosityLevel::getRecommendedLevelByType($returnType->getIterableValueType(), $valueType);
            $messages[] = RuleErrorBuilder::message(sprintf('Generator expects value type %s, %s given.', $returnType->getIterableValueType()->describe($verbosityLevel), $valueType->describe($verbosityLevel)))->acceptsReasonsTip($acceptsValue->reasons)->identifier('generator.valueType')->build();
        }
        if (!$scope->isInFirstLevelStatement() && $scope->getType($node)->isVoid()->yes()) {
            $messages[] = RuleErrorBuilder::message('Result of yield (void) is used.')->identifier('generator.void')->build();
        }
        return $messages;
    }
}
