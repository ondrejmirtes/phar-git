<?php

declare (strict_types=1);
namespace PHPStan\Analyser;

use PHPStan\Node\InvalidateExprNode;
final class ProcessClosureResult
{
    private \PHPStan\Analyser\MutatingScope $scope;
    /**
     * @var ThrowPoint[]
     */
    private array $throwPoints;
    /**
     * @var ImpurePoint[]
     */
    private array $impurePoints;
    /**
     * @var InvalidateExprNode[]
     */
    private array $invalidateExpressions;
    /**
     * @param ThrowPoint[] $throwPoints
     * @param ImpurePoint[] $impurePoints
     * @param InvalidateExprNode[] $invalidateExpressions
     */
    public function __construct(\PHPStan\Analyser\MutatingScope $scope, array $throwPoints, array $impurePoints, array $invalidateExpressions)
    {
        $this->scope = $scope;
        $this->throwPoints = $throwPoints;
        $this->impurePoints = $impurePoints;
        $this->invalidateExpressions = $invalidateExpressions;
    }
    public function getScope() : \PHPStan\Analyser\MutatingScope
    {
        return $this->scope;
    }
    /**
     * @return ThrowPoint[]
     */
    public function getThrowPoints() : array
    {
        return $this->throwPoints;
    }
    /**
     * @return ImpurePoint[]
     */
    public function getImpurePoints() : array
    {
        return $this->impurePoints;
    }
    /**
     * @return InvalidateExprNode[]
     */
    public function getInvalidateExpressions() : array
    {
        return $this->invalidateExpressions;
    }
}
