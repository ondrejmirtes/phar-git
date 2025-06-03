<?php

declare (strict_types=1);
namespace PHPStan\Analyser;

final class ExpressionResult
{
    private \PHPStan\Analyser\MutatingScope $scope;
    private bool $hasYield;
    /**
     * @var ThrowPoint[]
     */
    private array $throwPoints;
    /**
     * @var ImpurePoint[]
     */
    private array $impurePoints;
    /** @var (callable(): MutatingScope)|null */
    private $truthyScopeCallback;
    private ?\PHPStan\Analyser\MutatingScope $truthyScope = null;
    /** @var (callable(): MutatingScope)|null */
    private $falseyScopeCallback;
    private ?\PHPStan\Analyser\MutatingScope $falseyScope = null;
    /**
     * @param ThrowPoint[] $throwPoints
     * @param ImpurePoint[] $impurePoints
     * @param (callable(): MutatingScope)|null $truthyScopeCallback
     * @param (callable(): MutatingScope)|null $falseyScopeCallback
     */
    public function __construct(\PHPStan\Analyser\MutatingScope $scope, bool $hasYield, array $throwPoints, array $impurePoints, ?callable $truthyScopeCallback = null, ?callable $falseyScopeCallback = null)
    {
        $this->scope = $scope;
        $this->hasYield = $hasYield;
        $this->throwPoints = $throwPoints;
        $this->impurePoints = $impurePoints;
        $this->truthyScopeCallback = $truthyScopeCallback;
        $this->falseyScopeCallback = $falseyScopeCallback;
    }
    public function getScope() : \PHPStan\Analyser\MutatingScope
    {
        return $this->scope;
    }
    public function hasYield() : bool
    {
        return $this->hasYield;
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
    public function getTruthyScope() : \PHPStan\Analyser\MutatingScope
    {
        if ($this->truthyScopeCallback === null) {
            return $this->scope;
        }
        if ($this->truthyScope !== null) {
            return $this->truthyScope;
        }
        $callback = $this->truthyScopeCallback;
        $this->truthyScope = $callback();
        return $this->truthyScope;
    }
    public function getFalseyScope() : \PHPStan\Analyser\MutatingScope
    {
        if ($this->falseyScopeCallback === null) {
            return $this->scope;
        }
        if ($this->falseyScope !== null) {
            return $this->falseyScope;
        }
        $callback = $this->falseyScopeCallback;
        $this->falseyScope = $callback();
        return $this->falseyScope;
    }
}
