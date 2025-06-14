<?php

declare (strict_types=1);
namespace PHPStan\Reflection\Callables;

use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\ExtendedParameterReflection;
use PHPStan\Reflection\ExtendedParametersAcceptor;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeVarianceMap;
use PHPStan\Type\NeverType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use Throwable;
use function array_map;
use function count;
final class FunctionCallableVariant implements \PHPStan\Reflection\Callables\CallableParametersAcceptor, ExtendedParametersAcceptor
{
    /**
     * @var \PHPStan\Reflection\FunctionReflection|\PHPStan\Reflection\ExtendedMethodReflection
     */
    private $function;
    private ExtendedParametersAcceptor $variant;
    /** @var SimpleThrowPoint[]|null  */
    private ?array $throwPoints = null;
    /** @var SimpleImpurePoint[]|null  */
    private ?array $impurePoints = null;
    /**
     * @param \PHPStan\Reflection\FunctionReflection|\PHPStan\Reflection\ExtendedMethodReflection $function
     */
    public function __construct($function, ExtendedParametersAcceptor $variant)
    {
        $this->function = $function;
        $this->variant = $variant;
    }
    /**
     * @param ExtendedParametersAcceptor[] $variants
     * @return self[]
     * @param \PHPStan\Reflection\FunctionReflection|\PHPStan\Reflection\ExtendedMethodReflection $function
     */
    public static function createFromVariants($function, array $variants): array
    {
        return array_map(static fn(ExtendedParametersAcceptor $variant) => new self($function, $variant), $variants);
    }
    public function getTemplateTypeMap(): TemplateTypeMap
    {
        return $this->variant->getTemplateTypeMap();
    }
    public function getResolvedTemplateTypeMap(): TemplateTypeMap
    {
        return $this->variant->getResolvedTemplateTypeMap();
    }
    /**
     * @return list<ExtendedParameterReflection>
     */
    public function getParameters(): array
    {
        return $this->variant->getParameters();
    }
    public function isVariadic(): bool
    {
        return $this->variant->isVariadic();
    }
    public function getReturnType(): Type
    {
        return $this->variant->getReturnType();
    }
    public function getPhpDocReturnType(): Type
    {
        return $this->variant->getPhpDocReturnType();
    }
    public function getNativeReturnType(): Type
    {
        return $this->variant->getNativeReturnType();
    }
    public function getCallSiteVarianceMap(): TemplateTypeVarianceMap
    {
        return $this->variant->getCallSiteVarianceMap();
    }
    public function getThrowPoints(): array
    {
        if ($this->throwPoints !== null) {
            return $this->throwPoints;
        }
        if ($this->variant instanceof \PHPStan\Reflection\Callables\CallableParametersAcceptor) {
            return $this->throwPoints = $this->variant->getThrowPoints();
        }
        $returnType = $this->variant->getReturnType();
        $throwType = $this->function->getThrowType();
        if ($throwType === null) {
            if ($returnType instanceof NeverType && $returnType->isExplicit()) {
                $throwType = new ObjectType(Throwable::class);
            }
        }
        $throwPoints = [];
        if ($throwType !== null) {
            if (!$throwType->isVoid()->yes()) {
                $throwPoints[] = \PHPStan\Reflection\Callables\SimpleThrowPoint::createExplicit($throwType, \true);
            }
        } else if (!(new ObjectType(Throwable::class))->isSuperTypeOf($returnType)->yes()) {
            $throwPoints[] = \PHPStan\Reflection\Callables\SimpleThrowPoint::createImplicit();
        }
        return $this->throwPoints = $throwPoints;
    }
    public function isPure(): TrinaryLogic
    {
        $impurePoints = $this->getImpurePoints();
        if (count($impurePoints) === 0) {
            return TrinaryLogic::createYes();
        }
        $certainCount = 0;
        foreach ($impurePoints as $impurePoint) {
            if (!$impurePoint->isCertain()) {
                continue;
            }
            $certainCount++;
        }
        return $certainCount > 0 ? TrinaryLogic::createNo() : TrinaryLogic::createMaybe();
    }
    public function getImpurePoints(): array
    {
        if ($this->impurePoints !== null) {
            return $this->impurePoints;
        }
        if ($this->variant instanceof \PHPStan\Reflection\Callables\CallableParametersAcceptor) {
            return $this->impurePoints = $this->variant->getImpurePoints();
        }
        $impurePoint = \PHPStan\Reflection\Callables\SimpleImpurePoint::createFromVariant($this->function, $this->variant);
        if ($impurePoint === null) {
            return $this->impurePoints = [];
        }
        return $this->impurePoints = [$impurePoint];
    }
    public function getInvalidateExpressions(): array
    {
        return [];
    }
    public function getUsedVariables(): array
    {
        return [];
    }
    public function acceptsNamedArguments(): TrinaryLogic
    {
        return $this->function->acceptsNamedArguments();
    }
}
