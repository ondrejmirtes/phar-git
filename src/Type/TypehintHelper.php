<?php

declare (strict_types=1);
namespace PHPStan\Type;

use PhpParser\Node\Identifier;
use PhpParser\Node\Name\FullyQualified;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionIntersectionType;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionNamedType;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionUnionType;
use PHPStan\Reflection\ClassReflection;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Generic\TemplateTypeHelper;
use ReflectionType;
use function array_map;
use function count;
use function get_class;
use function sprintf;
final class TypehintHelper
{
    /** @api
     *  @param \PHPStan\Reflection\ClassReflection|null $selfClass */
    public static function decideTypeFromReflection(?ReflectionType $reflectionType, ?\PHPStan\Type\Type $phpDocType = null, $selfClass = null, bool $isVariadic = \false) : \PHPStan\Type\Type
    {
        if ($reflectionType === null) {
            if ($isVariadic && ($phpDocType instanceof \PHPStan\Type\ArrayType || $phpDocType instanceof ConstantArrayType)) {
                $phpDocType = $phpDocType->getItemType();
            }
            return $phpDocType ?? new \PHPStan\Type\MixedType();
        }
        if ($reflectionType instanceof ReflectionUnionType) {
            $type = \PHPStan\Type\TypeCombinator::union(...array_map(static fn(ReflectionType $type): \PHPStan\Type\Type => self::decideTypeFromReflection($type, null, $selfClass), $reflectionType->getTypes()));
            return self::decideType($type, $phpDocType);
        }
        if ($reflectionType instanceof ReflectionIntersectionType) {
            $types = [];
            foreach ($reflectionType->getTypes() as $innerReflectionType) {
                $innerType = self::decideTypeFromReflection($innerReflectionType, null, $selfClass);
                if (!$innerType->isObject()->yes()) {
                    return new \PHPStan\Type\NeverType();
                }
                $types[] = $innerType;
            }
            return self::decideType(\PHPStan\Type\TypeCombinator::intersect(...$types), $phpDocType);
        }
        if (!$reflectionType instanceof ReflectionNamedType) {
            throw new ShouldNotHappenException(sprintf('Unexpected type: %s', get_class($reflectionType)));
        }
        if ($reflectionType->isIdentifier()) {
            $typeNode = new Identifier($reflectionType->getName());
        } else {
            $typeNode = new FullyQualified($reflectionType->getName());
        }
        $type = \PHPStan\Type\ParserNodeTypeToPHPStanType::resolve($typeNode, $selfClass);
        if ($reflectionType->allowsNull()) {
            $type = \PHPStan\Type\TypeCombinator::addNull($type);
        }
        return self::decideType($type, $phpDocType);
    }
    public static function decideType(\PHPStan\Type\Type $type, ?\PHPStan\Type\Type $phpDocType) : \PHPStan\Type\Type
    {
        if ($phpDocType !== null && $type->isNull()->no()) {
            $phpDocType = \PHPStan\Type\TypeCombinator::removeNull($phpDocType);
        }
        if ($type instanceof \PHPStan\Type\BenevolentUnionType) {
            return $type;
        }
        if ($phpDocType !== null && !$phpDocType instanceof \PHPStan\Type\ErrorType) {
            if ($phpDocType instanceof \PHPStan\Type\NeverType && $phpDocType->isExplicit()) {
                return $phpDocType;
            }
            if ($type instanceof \PHPStan\Type\MixedType && !$type->isExplicitMixed() && $phpDocType->isVoid()->yes()) {
                return $phpDocType;
            }
            if (\PHPStan\Type\TypeCombinator::removeNull($type) instanceof \PHPStan\Type\IterableType) {
                if ($phpDocType instanceof \PHPStan\Type\UnionType) {
                    $innerTypes = [];
                    foreach ($phpDocType->getTypes() as $innerType) {
                        if ($innerType instanceof \PHPStan\Type\ArrayType || $innerType instanceof ConstantArrayType) {
                            $innerTypes[] = new \PHPStan\Type\IterableType($innerType->getIterableKeyType(), $innerType->getItemType());
                        } else {
                            $innerTypes[] = $innerType;
                        }
                    }
                    $phpDocType = new \PHPStan\Type\UnionType($innerTypes);
                } elseif ($phpDocType instanceof \PHPStan\Type\ArrayType || $phpDocType instanceof ConstantArrayType) {
                    $phpDocType = new \PHPStan\Type\IterableType($phpDocType->getKeyType(), $phpDocType->getItemType());
                }
            }
            if ($type->isCallable()->yes() && $phpDocType->isCallable()->yes() || (!$phpDocType instanceof \PHPStan\Type\NeverType || $type instanceof \PHPStan\Type\MixedType && !$type->isExplicitMixed()) && $type->isSuperTypeOf(TemplateTypeHelper::resolveToBounds($phpDocType))->yes()) {
                $resultType = $phpDocType;
            } else {
                $resultType = $type;
            }
            if ($type instanceof \PHPStan\Type\UnionType) {
                $addToUnionTypes = [];
                foreach ($type->getTypes() as $innerType) {
                    if (!$innerType->isSuperTypeOf($resultType)->no()) {
                        continue;
                    }
                    $addToUnionTypes[] = $innerType;
                }
                if (count($addToUnionTypes) > 0) {
                    $type = \PHPStan\Type\TypeCombinator::union($resultType, ...$addToUnionTypes);
                } else {
                    $type = $resultType;
                }
            } elseif (\PHPStan\Type\TypeCombinator::containsNull($type)) {
                $type = \PHPStan\Type\TypeCombinator::addNull($resultType);
            } else {
                $type = $resultType;
            }
        }
        return $type;
    }
}
