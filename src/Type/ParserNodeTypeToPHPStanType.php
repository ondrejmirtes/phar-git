<?php

declare (strict_types=1);
namespace PHPStan\Type;

use PhpParser\Node;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PHPStan\Reflection\ClassReflection;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Constant\ConstantBooleanType;
use function get_class;
use function in_array;
use function strtolower;
final class ParserNodeTypeToPHPStanType
{
    /**
     * @param Node\Name|Node\Identifier|Node\ComplexType|null $type
     */
    public static function resolve($type, ?ClassReflection $classReflection): \PHPStan\Type\Type
    {
        if ($type === null) {
            return new \PHPStan\Type\MixedType();
        } elseif ($type instanceof Name) {
            $typeClassName = (string) $type;
            $lowercasedClassName = strtolower($typeClassName);
            if ($classReflection !== null && in_array($lowercasedClassName, ['self', 'static'], \true)) {
                if ($lowercasedClassName === 'static') {
                    return new \PHPStan\Type\StaticType($classReflection);
                }
                $typeClassName = $classReflection->getName();
            } elseif ($lowercasedClassName === 'parent' && $classReflection !== null && $classReflection->getParentClass() !== null) {
                $typeClassName = $classReflection->getParentClass()->getName();
            }
            return new \PHPStan\Type\ObjectType($typeClassName);
        } elseif ($type instanceof NullableType) {
            return \PHPStan\Type\TypeCombinator::addNull(self::resolve($type->type, $classReflection));
        } elseif ($type instanceof Node\UnionType) {
            $types = [];
            foreach ($type->types as $unionTypeType) {
                $types[] = self::resolve($unionTypeType, $classReflection);
            }
            return \PHPStan\Type\TypeCombinator::union(...$types);
        } elseif ($type instanceof Node\IntersectionType) {
            $types = [];
            foreach ($type->types as $intersectionTypeType) {
                $innerType = self::resolve($intersectionTypeType, $classReflection);
                if (!$innerType->isObject()->yes()) {
                    return new \PHPStan\Type\NeverType();
                }
                $types[] = $innerType;
            }
            return \PHPStan\Type\TypeCombinator::intersect(...$types);
        } elseif (!$type instanceof Identifier) {
            throw new ShouldNotHappenException(get_class($type));
        }
        $type = $type->name;
        if ($type === 'string') {
            return new \PHPStan\Type\StringType();
        } elseif ($type === 'int') {
            return new \PHPStan\Type\IntegerType();
        } elseif ($type === 'bool') {
            return new \PHPStan\Type\BooleanType();
        } elseif ($type === 'float') {
            return new \PHPStan\Type\FloatType();
        } elseif ($type === 'callable') {
            return new \PHPStan\Type\CallableType();
        } elseif ($type === 'array') {
            return new \PHPStan\Type\ArrayType(new \PHPStan\Type\MixedType(), new \PHPStan\Type\MixedType());
        } elseif ($type === 'iterable') {
            return new \PHPStan\Type\IterableType(new \PHPStan\Type\MixedType(), new \PHPStan\Type\MixedType());
        } elseif ($type === 'void') {
            return new \PHPStan\Type\VoidType();
        } elseif ($type === 'object') {
            return new \PHPStan\Type\ObjectWithoutClassType();
        } elseif ($type === 'true') {
            return new ConstantBooleanType(\true);
        } elseif ($type === 'false') {
            return new ConstantBooleanType(\false);
        } elseif ($type === 'null') {
            return new \PHPStan\Type\NullType();
        } elseif ($type === 'mixed') {
            return new \PHPStan\Type\MixedType(\true);
        } elseif ($type === 'never') {
            return new \PHPStan\Type\NonAcceptingNeverType();
        }
        return new \PHPStan\Type\MixedType();
    }
}
