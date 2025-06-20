<?php

declare (strict_types=1);
namespace PHPStan\Rules\Arrays;

use PhpParser\Node\Expr;
use PHPStan\Analyser\NullsafeOperatorHelper;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use function count;
use function sprintf;
final class NonexistentOffsetInArrayDimFetchCheck
{
    private RuleLevelHelper $ruleLevelHelper;
    private bool $reportMaybes;
    private bool $reportPossiblyNonexistentGeneralArrayOffset;
    private bool $reportPossiblyNonexistentConstantArrayOffset;
    public function __construct(RuleLevelHelper $ruleLevelHelper, bool $reportMaybes, bool $reportPossiblyNonexistentGeneralArrayOffset, bool $reportPossiblyNonexistentConstantArrayOffset)
    {
        $this->ruleLevelHelper = $ruleLevelHelper;
        $this->reportMaybes = $reportMaybes;
        $this->reportPossiblyNonexistentGeneralArrayOffset = $reportPossiblyNonexistentGeneralArrayOffset;
        $this->reportPossiblyNonexistentConstantArrayOffset = $reportPossiblyNonexistentConstantArrayOffset;
    }
    /**
     * @return list<IdentifierRuleError>
     */
    public function check(Scope $scope, Expr $var, string $unknownClassPattern, Type $dimType): array
    {
        $typeResult = $this->ruleLevelHelper->findTypeToCheck($scope, NullsafeOperatorHelper::getNullsafeShortcircuitedExprRespectingScope($scope, $var), $unknownClassPattern, static fn(Type $type): bool => $type->hasOffsetValueType($dimType)->yes());
        $type = $typeResult->getType();
        if ($type instanceof ErrorType) {
            return $typeResult->getUnknownClassErrors();
        }
        if ($scope->isInExpressionAssign($var) || $scope->isUndefinedExpressionAllowed($var)) {
            return [];
        }
        if ($type->hasOffsetValueType($dimType)->no()) {
            return [RuleErrorBuilder::message(sprintf('Offset %s does not exist on %s.', $dimType->describe(count($dimType->getConstantStrings()) > 0 ? VerbosityLevel::precise() : VerbosityLevel::value()), $type->describe(VerbosityLevel::value())))->identifier('offsetAccess.notFound')->build()];
        }
        if ($this->reportMaybes) {
            $report = \false;
            if ($type instanceof BenevolentUnionType) {
                $flattenedTypes = [$type];
            } else {
                $flattenedTypes = TypeUtils::flattenTypes($type);
            }
            foreach ($flattenedTypes as $innerType) {
                if ($this->reportPossiblyNonexistentGeneralArrayOffset && $innerType->isArray()->yes() && !$innerType->isConstantArray()->yes() && !$innerType->hasOffsetValueType($dimType)->yes()) {
                    $report = \true;
                    break;
                }
                if ($this->reportPossiblyNonexistentConstantArrayOffset && $innerType->isConstantArray()->yes() && !$innerType->hasOffsetValueType($dimType)->yes()) {
                    $report = \true;
                    break;
                }
                if ($dimType instanceof UnionType) {
                    if ($innerType->hasOffsetValueType($dimType)->no()) {
                        $report = \true;
                        break;
                    }
                    continue;
                }
                foreach (TypeUtils::flattenTypes($dimType) as $innerDimType) {
                    if ($innerType->hasOffsetValueType($innerDimType)->no()) {
                        $report = \true;
                        break 2;
                    }
                }
            }
            if ($report) {
                return [RuleErrorBuilder::message(sprintf('Offset %s might not exist on %s.', $dimType->describe(count($dimType->getConstantStrings()) > 0 ? VerbosityLevel::precise() : VerbosityLevel::value()), $type->describe(VerbosityLevel::value())))->identifier('offsetAccess.notFound')->build()];
            }
        }
        return [];
    }
}
