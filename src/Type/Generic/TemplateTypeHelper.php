<?php

declare (strict_types=1);
namespace PHPStan\Type\Generic;

use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Type\ErrorType;
use PHPStan\Type\GeneralizePrecision;
use PHPStan\Type\NonAcceptingNeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\VerbosityLevel;
final class TemplateTypeHelper
{
    /**
     * Replaces template types with standin types
     */
    public static function resolveTemplateTypes(Type $type, \PHPStan\Type\Generic\TemplateTypeMap $standins, \PHPStan\Type\Generic\TemplateTypeVarianceMap $callSiteVariances, \PHPStan\Type\Generic\TemplateTypeVariance $positionVariance, bool $keepErrorTypes = \false): Type
    {
        $references = $type->getReferencedTemplateTypes($positionVariance);
        return TypeTraverser::map($type, static function (Type $type, callable $traverse) use ($standins, $references, $callSiteVariances, $keepErrorTypes): Type {
            if ($type instanceof \PHPStan\Type\Generic\TemplateType && !$type->isArgument()) {
                $newType = $standins->getType($type->getName());
                $variance = \PHPStan\Type\Generic\TemplateTypeVariance::createInvariant();
                foreach ($references as $reference) {
                    // this uses identity to distinguish between different occurrences of the same template type
                    // see https://github.com/phpstan/phpstan-src/pull/2485#discussion_r1328555397 for details
                    if ($reference->getType() === $type) {
                        $variance = $reference->getPositionVariance();
                        break;
                    }
                }
                if ($newType === null) {
                    return $traverse($type);
                }
                if ($newType instanceof ErrorType && !$keepErrorTypes) {
                    return $traverse($type->getDefault() ?? $type->getBound());
                }
                $callSiteVariance = $callSiteVariances->getVariance($type->getName());
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
    public static function resolveToDefaults(Type $type): Type
    {
        return TypeTraverser::map($type, static function (Type $type, callable $traverse): Type {
            if ($type instanceof \PHPStan\Type\Generic\TemplateType) {
                return $traverse($type->getDefault() ?? $type->getBound());
            }
            return $traverse($type);
        });
    }
    public static function resolveToBounds(Type $type): Type
    {
        return TypeTraverser::map($type, static function (Type $type, callable $traverse): Type {
            if ($type instanceof \PHPStan\Type\Generic\TemplateType) {
                return $traverse($type->getBound());
            }
            return $traverse($type);
        });
    }
    /**
     * @template T of Type
     * @param T $type
     * @return T
     */
    public static function toArgument(Type $type): Type
    {
        $ownedTemplates = [];
        /** @var T */
        return TypeTraverser::map($type, static function (Type $type, callable $traverse) use (&$ownedTemplates): Type {
            if ($type instanceof ParametersAcceptor) {
                $templateTypeMap = $type->getTemplateTypeMap();
                foreach ($type->getParameters() as $parameter) {
                    $parameterType = $parameter->getType();
                    if (!$parameterType instanceof \PHPStan\Type\Generic\TemplateType || !$templateTypeMap->hasType($parameterType->getName())) {
                        continue;
                    }
                    $ownedTemplates[] = $parameterType;
                }
                $returnType = $type->getReturnType();
                if ($returnType instanceof \PHPStan\Type\Generic\TemplateType && $templateTypeMap->hasType($returnType->getName())) {
                    $ownedTemplates[] = $returnType;
                }
            }
            foreach ($ownedTemplates as $ownedTemplate) {
                if ($ownedTemplate === $type) {
                    return $traverse($type);
                }
            }
            if ($type instanceof \PHPStan\Type\Generic\TemplateType) {
                return $traverse($type->toArgument());
            }
            return $traverse($type);
        });
    }
    public static function generalizeInferredTemplateType(\PHPStan\Type\Generic\TemplateType $templateType, Type $type): Type
    {
        if (!$templateType->getVariance()->covariant()) {
            $isArrayKey = $templateType->getBound()->describe(VerbosityLevel::precise()) === '(int|string)';
            if ($type->isScalar()->yes() && $isArrayKey) {
                $type = $type->generalize(GeneralizePrecision::templateArgument());
            } elseif ($type->isConstantValue()->yes() && (!$templateType->getBound()->isScalar()->yes() || $isArrayKey)) {
                $type = $type->generalize(GeneralizePrecision::templateArgument());
            }
        }
        return $type;
    }
}
