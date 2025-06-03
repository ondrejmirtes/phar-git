<?php

declare (strict_types=1);
namespace PHPStan\Rules;

use PhpParser\Node\Arg;
use PHPStan\Analyser\ArgumentsNormalizer;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use function sprintf;
#[\PHPStan\DependencyInjection\AutowiredService]
final class ParameterCastableToStringCheck
{
    private \PHPStan\Rules\RuleLevelHelper $ruleLevelHelper;
    public function __construct(\PHPStan\Rules\RuleLevelHelper $ruleLevelHelper)
    {
        $this->ruleLevelHelper = $ruleLevelHelper;
    }
    /** @param callable(Type): Type $castFn */
    public function checkParameter(Arg $parameter, Scope $scope, string $errorMessageTemplate, callable $castFn, string $functionName, string $parameterName) : ?\PHPStan\Rules\IdentifierRuleError
    {
        if ($parameter->unpack) {
            return null;
        }
        $typeResult = $this->ruleLevelHelper->findTypeToCheck($scope, $parameter->value, '', static fn(Type $type): bool => $type->isArray()->yes() && !$castFn($type->getIterableValueType()) instanceof ErrorType);
        if (!$typeResult->getType()->isArray()->yes() || !$castFn($typeResult->getType()->getIterableValueType()) instanceof ErrorType) {
            return null;
        }
        return \PHPStan\Rules\RuleErrorBuilder::message(sprintf($errorMessageTemplate, $parameterName, $functionName, $typeResult->getType()->describe(VerbosityLevel::typeOnly())))->identifier('argument.type')->build();
    }
    public function getParameterName(Arg $parameter, int $parameterIdx, ?ParameterReflection $parameterReflection) : string
    {
        if ($parameterReflection === null) {
            return sprintf('#%d', $parameterIdx + 1);
        }
        $paramName = $parameterReflection->getName();
        $origParameter = $parameter->getAttributes()[ArgumentsNormalizer::ORIGINAL_ARG_ATTRIBUTE] ?? null;
        if (!$origParameter instanceof Arg) {
            $origParameter = $parameter;
        }
        return $origParameter->name !== null ? sprintf('$%s', $paramName) : sprintf('#%d $%s', $parameterIdx + 1, $paramName);
    }
}
