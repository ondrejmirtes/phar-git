<?php

declare (strict_types=1);
namespace PHPStan\Type;

use PHPStan\Type\Accessory\AccessoryType;
use PHPStan\Type\Accessory\HasPropertyType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Generic\TemplateBenevolentUnionType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateUnionType;
use function array_merge;
/**
 * @api
 */
final class TypeUtils
{
    /**
     * @return list<ConstantIntegerType>
     */
    public static function getConstantIntegers(\PHPStan\Type\Type $type) : array
    {
        return self::map(ConstantIntegerType::class, $type, \false);
    }
    /**
     * @return list<IntegerRangeType>
     */
    public static function getIntegerRanges(\PHPStan\Type\Type $type) : array
    {
        return self::map(\PHPStan\Type\IntegerRangeType::class, $type, \false);
    }
    /**
     * @return list<mixed>
     */
    private static function map(string $typeClass, \PHPStan\Type\Type $type, bool $inspectIntersections, bool $stopOnUnmatched = \true) : array
    {
        if ($type instanceof $typeClass) {
            return [$type];
        }
        if ($type instanceof \PHPStan\Type\UnionType) {
            $matchingTypes = [];
            foreach ($type->getTypes() as $innerType) {
                $matchingInner = self::map($typeClass, $innerType, $inspectIntersections, $stopOnUnmatched);
                if ($matchingInner === []) {
                    if ($stopOnUnmatched) {
                        return [];
                    }
                    continue;
                }
                foreach ($matchingInner as $innerMapped) {
                    $matchingTypes[] = $innerMapped;
                }
            }
            return $matchingTypes;
        }
        if ($inspectIntersections && $type instanceof \PHPStan\Type\IntersectionType) {
            $matchingTypes = [];
            foreach ($type->getTypes() as $innerType) {
                if (!$innerType instanceof $typeClass) {
                    if ($stopOnUnmatched) {
                        return [];
                    }
                    continue;
                }
                $matchingTypes[] = $innerType;
            }
            return $matchingTypes;
        }
        return [];
    }
    public static function toBenevolentUnion(\PHPStan\Type\Type $type) : \PHPStan\Type\Type
    {
        if ($type instanceof \PHPStan\Type\BenevolentUnionType) {
            return $type;
        }
        if ($type instanceof \PHPStan\Type\UnionType) {
            return new \PHPStan\Type\BenevolentUnionType($type->getTypes());
        }
        return $type;
    }
    /**
     * @return ($type is UnionType ? UnionType : Type)
     */
    public static function toStrictUnion(\PHPStan\Type\Type $type) : \PHPStan\Type\Type
    {
        if ($type instanceof TemplateBenevolentUnionType) {
            return new TemplateUnionType($type->getScope(), $type->getStrategy(), $type->getVariance(), $type->getName(), static::toStrictUnion($type->getBound()), $type->getDefault());
        }
        if ($type instanceof \PHPStan\Type\BenevolentUnionType) {
            return new \PHPStan\Type\UnionType($type->getTypes());
        }
        return $type;
    }
    /**
     * @return Type[]
     */
    public static function flattenTypes(\PHPStan\Type\Type $type) : array
    {
        if ($type instanceof ConstantArrayType) {
            return $type->getAllArrays();
        }
        if ($type instanceof \PHPStan\Type\UnionType) {
            $types = [];
            foreach ($type->getTypes() as $innerType) {
                if ($innerType instanceof ConstantArrayType) {
                    foreach ($innerType->getAllArrays() as $array) {
                        $types[] = $array;
                    }
                    continue;
                }
                $types[] = $innerType;
            }
            return $types;
        }
        return [$type];
    }
    public static function findThisType(\PHPStan\Type\Type $type) : ?\PHPStan\Type\ThisType
    {
        if ($type instanceof \PHPStan\Type\ThisType) {
            return $type;
        }
        if ($type instanceof \PHPStan\Type\UnionType || $type instanceof \PHPStan\Type\IntersectionType) {
            foreach ($type->getTypes() as $innerType) {
                $thisType = self::findThisType($innerType);
                if ($thisType !== null) {
                    return $thisType;
                }
            }
        }
        return null;
    }
    /**
     * @return HasPropertyType[]
     */
    public static function getHasPropertyTypes(\PHPStan\Type\Type $type) : array
    {
        if ($type instanceof HasPropertyType) {
            return [$type];
        }
        if ($type instanceof \PHPStan\Type\UnionType || $type instanceof \PHPStan\Type\IntersectionType) {
            $hasPropertyTypes = [[]];
            foreach ($type->getTypes() as $innerType) {
                $hasPropertyTypes[] = self::getHasPropertyTypes($innerType);
            }
            return array_merge(...$hasPropertyTypes);
        }
        return [];
    }
    /**
     * @return AccessoryType[]
     */
    public static function getAccessoryTypes(\PHPStan\Type\Type $type) : array
    {
        return self::map(AccessoryType::class, $type, \true, \false);
    }
    public static function containsTemplateType(\PHPStan\Type\Type $type) : bool
    {
        $containsTemplateType = \false;
        \PHPStan\Type\TypeTraverser::map($type, static function (\PHPStan\Type\Type $type, callable $traverse) use(&$containsTemplateType) : \PHPStan\Type\Type {
            if ($type instanceof TemplateType) {
                $containsTemplateType = \true;
            }
            return $containsTemplateType ? $type : $traverse($type);
        });
        return $containsTemplateType;
    }
    public static function resolveLateResolvableTypes(\PHPStan\Type\Type $type, bool $resolveUnresolvableTypes = \true) : \PHPStan\Type\Type
    {
        /** @var int $ignoreResolveUnresolvableTypesLevel */
        $ignoreResolveUnresolvableTypesLevel = 0;
        return \PHPStan\Type\TypeTraverser::map($type, static function (\PHPStan\Type\Type $type, callable $traverse) use($resolveUnresolvableTypes, &$ignoreResolveUnresolvableTypesLevel) : \PHPStan\Type\Type {
            while ($type instanceof \PHPStan\Type\LateResolvableType && ($resolveUnresolvableTypes && $ignoreResolveUnresolvableTypesLevel === 0 || $type->isResolvable())) {
                $type = $type->resolve();
            }
            if ($type instanceof \PHPStan\Type\CallableType || $type instanceof \PHPStan\Type\ClosureType) {
                $ignoreResolveUnresolvableTypesLevel++;
                $result = $traverse($type);
                $ignoreResolveUnresolvableTypesLevel--;
                return $result;
            }
            return $traverse($type);
        });
    }
}
