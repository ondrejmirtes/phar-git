<?php

declare (strict_types=1);
namespace PHPStan\Analyser;

final class EnsuredNonNullabilityResult
{
    private \PHPStan\Analyser\MutatingScope $scope;
    /**
     * @var EnsuredNonNullabilityResultExpression[]
     */
    private array $specifiedExpressions;
    /**
     * @param EnsuredNonNullabilityResultExpression[] $specifiedExpressions
     */
    public function __construct(\PHPStan\Analyser\MutatingScope $scope, array $specifiedExpressions)
    {
        $this->scope = $scope;
        $this->specifiedExpressions = $specifiedExpressions;
    }
    public function getScope() : \PHPStan\Analyser\MutatingScope
    {
        return $this->scope;
    }
    /**
     * @return EnsuredNonNullabilityResultExpression[]
     */
    public function getSpecifiedExpressions() : array
    {
        return $this->specifiedExpressions;
    }
}
