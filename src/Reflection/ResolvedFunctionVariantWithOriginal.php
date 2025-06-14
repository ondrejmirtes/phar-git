<?php

declare (strict_types=1);
namespace PHPStan\Reflection;

use PHPStan\Reflection\Php\ExtendedDummyParameter;
use PHPStan\Type\ConditionalTypeForParameter;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Generic\GenericStaticType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateTypeHelper;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Generic\TemplateTypeVarianceMap;
use PHPStan\Type\NonAcceptingNeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\TypeUtils;
use function array_key_exists;
use function array_map;
final class ResolvedFunctionVariantWithOriginal implements \PHPStan\Reflection\ResolvedFunctionVariant
{
    private \PHPStan\Reflection\ExtendedParametersAcceptor $parametersAcceptor;
    private TemplateTypeMap $resolvedTemplateTypeMap;
    private TemplateTypeVarianceMap $callSiteVarianceMap;
    /**
     * @var array<string, Type>
     */
    private array $passedArgs;
    /** @var list<ExtendedParameterReflection>|null */
    private ?array $parameters = null;
    private ?Type $returnTypeWithUnresolvableTemplateTypes = null;
    private ?Type $phpDocReturnTypeWithUnresolvableTemplateTypes = null;
    private ?Type $returnType = null;
    private ?Type $phpDocReturnType = null;
    /**
     * @param array<string, Type> $passedArgs
     */
    public function __construct(\PHPStan\Reflection\ExtendedParametersAcceptor $parametersAcceptor, TemplateTypeMap $resolvedTemplateTypeMap, TemplateTypeVarianceMap $callSiteVarianceMap, array $passedArgs)
    {
        $this->parametersAcceptor = $parametersAcceptor;
        $this->resolvedTemplateTypeMap = $resolvedTemplateTypeMap;
        $this->callSiteVarianceMap = $callSiteVarianceMap;
        $this->passedArgs = $passedArgs;
    }
    public function getOriginalParametersAcceptor(): \PHPStan\Reflection\ParametersAcceptor
    {
        return $this->parametersAcceptor;
    }
    public function getTemplateTypeMap(): TemplateTypeMap
    {
        return $this->parametersAcceptor->getTemplateTypeMap();
    }
    public function getResolvedTemplateTypeMap(): TemplateTypeMap
    {
        return $this->resolvedTemplateTypeMap;
    }
    public function getCallSiteVarianceMap(): TemplateTypeVarianceMap
    {
        return $this->callSiteVarianceMap;
    }
    public function getParameters(): array
    {
        $parameters = $this->parameters;
        if ($parameters === null) {
            $parameters = array_map(function (\PHPStan\Reflection\ExtendedParameterReflection $param): \PHPStan\Reflection\ExtendedParameterReflection {
                $paramType = TypeUtils::resolveLateResolvableTypes(TemplateTypeHelper::resolveTemplateTypes($this->resolveConditionalTypesForParameter($param->getType()), $this->resolvedTemplateTypeMap, $this->callSiteVarianceMap, TemplateTypeVariance::createContravariant()), \false);
                $paramOutType = $param->getOutType();
                if ($paramOutType !== null) {
                    $paramOutType = TypeUtils::resolveLateResolvableTypes(TemplateTypeHelper::resolveTemplateTypes($this->resolveConditionalTypesForParameter($paramOutType), $this->resolvedTemplateTypeMap, $this->callSiteVarianceMap, TemplateTypeVariance::createCovariant()), \false);
                }
                $closureThisType = $param->getClosureThisType();
                if ($closureThisType !== null) {
                    $closureThisType = TypeUtils::resolveLateResolvableTypes(TemplateTypeHelper::resolveTemplateTypes($this->resolveConditionalTypesForParameter($closureThisType), $this->resolvedTemplateTypeMap, $this->callSiteVarianceMap, TemplateTypeVariance::createCovariant()), \false);
                }
                return new ExtendedDummyParameter($param->getName(), $paramType, $param->isOptional(), $param->passedByReference(), $param->isVariadic(), $param->getDefaultValue(), $param->getNativeType(), $param->getPhpDocType(), $paramOutType, $param->isImmediatelyInvokedCallable(), $closureThisType, $param->getAttributes());
            }, $this->parametersAcceptor->getParameters());
            $this->parameters = $parameters;
        }
        return $parameters;
    }
    public function isVariadic(): bool
    {
        return $this->parametersAcceptor->isVariadic();
    }
    public function getReturnTypeWithUnresolvableTemplateTypes(): Type
    {
        return $this->returnTypeWithUnresolvableTemplateTypes ??= $this->resolveConditionalTypesForParameter($this->resolveResolvableTemplateTypes($this->parametersAcceptor->getReturnType(), TemplateTypeVariance::createCovariant()));
    }
    public function getPhpDocReturnTypeWithUnresolvableTemplateTypes(): Type
    {
        return $this->phpDocReturnTypeWithUnresolvableTemplateTypes ??= $this->resolveConditionalTypesForParameter($this->resolveResolvableTemplateTypes($this->parametersAcceptor->getPhpDocReturnType(), TemplateTypeVariance::createCovariant()));
    }
    public function getReturnType(): Type
    {
        $type = $this->returnType;
        if ($type === null) {
            $type = TypeUtils::resolveLateResolvableTypes(TemplateTypeHelper::resolveTemplateTypes($this->getReturnTypeWithUnresolvableTemplateTypes(), $this->resolvedTemplateTypeMap, $this->callSiteVarianceMap, TemplateTypeVariance::createCovariant()), \false);
            $this->returnType = $type;
        }
        return $type;
    }
    public function getPhpDocReturnType(): Type
    {
        $type = $this->phpDocReturnType;
        if ($type === null) {
            $type = TypeUtils::resolveLateResolvableTypes(TemplateTypeHelper::resolveTemplateTypes($this->getPhpDocReturnTypeWithUnresolvableTemplateTypes(), $this->resolvedTemplateTypeMap, $this->callSiteVarianceMap, TemplateTypeVariance::createCovariant()), \false);
            $this->phpDocReturnType = $type;
        }
        return $type;
    }
    public function getNativeReturnType(): Type
    {
        return $this->parametersAcceptor->getNativeReturnType();
    }
    private function resolveResolvableTemplateTypes(Type $type, TemplateTypeVariance $positionVariance): Type
    {
        $references = $type->getReferencedTemplateTypes($positionVariance);
        $objectCb = function (Type $type, callable $traverse) use ($references): Type {
            if ($type instanceof TemplateType && !$type->isArgument() && $type->getScope()->getFunctionName() !== null) {
                $newType = $this->resolvedTemplateTypeMap->getType($type->getName());
                if ($newType === null || $newType instanceof ErrorType) {
                    return $traverse($type);
                }
                $newType = TemplateTypeHelper::generalizeInferredTemplateType($type, $newType);
                $variance = TemplateTypeVariance::createInvariant();
                foreach ($references as $reference) {
                    // this uses identity to distinguish between different occurrences of the same template type
                    // see https://github.com/phpstan/phpstan-src/pull/2485#discussion_r1328555397 for details
                    if ($reference->getType() === $type) {
                        $variance = $reference->getPositionVariance();
                        break;
                    }
                }
                $callSiteVariance = $this->callSiteVarianceMap->getVariance($type->getName());
                if ($callSiteVariance === null || $callSiteVariance->invariant()) {
                    return $newType;
                }
                if (!$callSiteVariance->covariant() && $variance->covariant()) {
                    return $traverse($type->getBound());
                }
                if (!$callSiteVariance->contravariant() && $variance->contravariant()) {
                    return new NonAcceptingNeverType();
                }
                return $newType;
            }
            return $traverse($type);
        };
        return TypeTraverser::map($type, function (Type $type, callable $traverse) use ($references, $objectCb): Type {
            if ($type instanceof GenericObjectType || $type instanceof GenericStaticType) {
                return TypeTraverser::map($type, $objectCb);
            }
            if ($type instanceof TemplateType && !$type->isArgument()) {
                $newType = $this->resolvedTemplateTypeMap->getType($type->getName());
                if ($newType === null || $newType instanceof ErrorType) {
                    return $traverse($type);
                }
                $variance = TemplateTypeVariance::createInvariant();
                foreach ($references as $reference) {
                    // this uses identity to distinguish between different occurrences of the same template type
                    // see https://github.com/phpstan/phpstan-src/pull/2485#discussion_r1328555397 for details
                    if ($reference->getType() === $type) {
                        $variance = $reference->getPositionVariance();
                        break;
                    }
                }
                $callSiteVariance = $this->callSiteVarianceMap->getVariance($type->getName());
                if ($callSiteVariance === null || $callSiteVariance->invariant()) {
                    return $newType;
                }
                if (!$callSiteVariance->covariant() && $variance->covariant()) {
                    return $traverse($type->getBound());
                }
                if (!$callSiteVariance->contravariant() && $variance->contravariant()) {
                    return new NonAcceptingNeverType();
                }
                return $newType;
            }
            return $traverse($type);
        });
    }
    private function resolveConditionalTypesForParameter(Type $type): Type
    {
        return TypeTraverser::map($type, function (Type $type, callable $traverse): Type {
            if ($type instanceof ConditionalTypeForParameter && array_key_exists($type->getParameterName(), $this->passedArgs)) {
                $type = $type->toConditional($this->passedArgs[$type->getParameterName()]);
            }
            return $traverse($type);
        });
    }
}
