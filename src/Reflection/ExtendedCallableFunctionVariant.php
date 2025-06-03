<?php

declare (strict_types=1);
namespace PHPStan\Reflection;

use PHPStan\Node\InvalidateExprNode;
use PHPStan\Reflection\Callables\CallableParametersAcceptor;
use PHPStan\Reflection\Callables\SimpleImpurePoint;
use PHPStan\Reflection\Callables\SimpleThrowPoint;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeVarianceMap;
use PHPStan\Type\Type;
final class ExtendedCallableFunctionVariant extends \PHPStan\Reflection\ExtendedFunctionVariant implements CallableParametersAcceptor
{
    /**
     * @var SimpleThrowPoint[]
     */
    private array $throwPoints;
    private TrinaryLogic $isPure;
    /**
     * @var SimpleImpurePoint[]
     */
    private array $impurePoints;
    /**
     * @var InvalidateExprNode[]
     */
    private array $invalidateExpressions;
    /**
     * @var string[]
     */
    private array $usedVariables;
    private TrinaryLogic $acceptsNamedArguments;
    /**
     * @param list<ExtendedParameterReflection> $parameters
     * @param SimpleThrowPoint[] $throwPoints
     * @param SimpleImpurePoint[] $impurePoints
     * @param InvalidateExprNode[] $invalidateExpressions
     * @param string[] $usedVariables
     */
    public function __construct(TemplateTypeMap $templateTypeMap, ?TemplateTypeMap $resolvedTemplateTypeMap, array $parameters, bool $isVariadic, Type $returnType, Type $phpDocReturnType, Type $nativeReturnType, ?TemplateTypeVarianceMap $callSiteVarianceMap, array $throwPoints, TrinaryLogic $isPure, array $impurePoints, array $invalidateExpressions, array $usedVariables, TrinaryLogic $acceptsNamedArguments)
    {
        $this->throwPoints = $throwPoints;
        $this->isPure = $isPure;
        $this->impurePoints = $impurePoints;
        $this->invalidateExpressions = $invalidateExpressions;
        $this->usedVariables = $usedVariables;
        $this->acceptsNamedArguments = $acceptsNamedArguments;
        parent::__construct($templateTypeMap, $resolvedTemplateTypeMap, $parameters, $isVariadic, $returnType, $phpDocReturnType, $nativeReturnType, $callSiteVarianceMap);
    }
    public function getThrowPoints(): array
    {
        return $this->throwPoints;
    }
    public function isPure(): TrinaryLogic
    {
        return $this->isPure;
    }
    public function getImpurePoints(): array
    {
        return $this->impurePoints;
    }
    public function getInvalidateExpressions(): array
    {
        return $this->invalidateExpressions;
    }
    public function getUsedVariables(): array
    {
        return $this->usedVariables;
    }
    public function acceptsNamedArguments(): TrinaryLogic
    {
        return $this->acceptsNamedArguments;
    }
}
