<?php

declare (strict_types=1);
namespace PHPStan\Type\Generic;

use PHPStan\PhpDoc\Tag\TemplateTag;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\IterableType;
use PHPStan\Type\KeyOfType;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectShapeType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use function get_class;
final class TemplateTypeFactory
{
    /**
     * @param non-empty-string $name
     */
    public static function create(\PHPStan\Type\Generic\TemplateTypeScope $scope, string $name, ?Type $bound, \PHPStan\Type\Generic\TemplateTypeVariance $variance, ?\PHPStan\Type\Generic\TemplateTypeStrategy $strategy = null, ?Type $default = null) : \PHPStan\Type\Generic\TemplateType
    {
        $strategy ??= new \PHPStan\Type\Generic\TemplateTypeParameterStrategy();
        if ($bound === null) {
            return new \PHPStan\Type\Generic\TemplateMixedType($scope, $strategy, $variance, $name, new MixedType(\true), $default);
        }
        $boundClass = get_class($bound);
        if ($bound instanceof ObjectType && ($boundClass === ObjectType::class || $bound instanceof \PHPStan\Type\Generic\TemplateType)) {
            return new \PHPStan\Type\Generic\TemplateObjectType($scope, $strategy, $variance, $name, $bound, $default);
        }
        if ($bound instanceof \PHPStan\Type\Generic\GenericObjectType && ($boundClass === \PHPStan\Type\Generic\GenericObjectType::class || $bound instanceof \PHPStan\Type\Generic\TemplateType)) {
            return new \PHPStan\Type\Generic\TemplateGenericObjectType($scope, $strategy, $variance, $name, $bound, $default);
        }
        if ($bound instanceof ObjectWithoutClassType && ($boundClass === ObjectWithoutClassType::class || $bound instanceof \PHPStan\Type\Generic\TemplateType)) {
            return new \PHPStan\Type\Generic\TemplateObjectWithoutClassType($scope, $strategy, $variance, $name, $bound, $default);
        }
        if ($bound instanceof ArrayType && ($boundClass === ArrayType::class || $bound instanceof \PHPStan\Type\Generic\TemplateType)) {
            return new \PHPStan\Type\Generic\TemplateArrayType($scope, $strategy, $variance, $name, $bound, $default);
        }
        if ($bound instanceof ConstantArrayType && ($boundClass === ConstantArrayType::class || $bound instanceof \PHPStan\Type\Generic\TemplateType)) {
            return new \PHPStan\Type\Generic\TemplateConstantArrayType($scope, $strategy, $variance, $name, $bound, $default);
        }
        if ($bound instanceof ObjectShapeType && ($boundClass === ObjectShapeType::class || $bound instanceof \PHPStan\Type\Generic\TemplateType)) {
            return new \PHPStan\Type\Generic\TemplateObjectShapeType($scope, $strategy, $variance, $name, $bound, $default);
        }
        if ($bound instanceof StringType && ($boundClass === StringType::class || $bound instanceof \PHPStan\Type\Generic\TemplateType)) {
            return new \PHPStan\Type\Generic\TemplateStringType($scope, $strategy, $variance, $name, $bound, $default);
        }
        if ($bound instanceof ConstantStringType && ($boundClass === ConstantStringType::class || $bound instanceof \PHPStan\Type\Generic\TemplateType)) {
            return new \PHPStan\Type\Generic\TemplateConstantStringType($scope, $strategy, $variance, $name, $bound, $default);
        }
        if ($bound instanceof IntegerType && ($boundClass === IntegerType::class || $bound instanceof \PHPStan\Type\Generic\TemplateType)) {
            return new \PHPStan\Type\Generic\TemplateIntegerType($scope, $strategy, $variance, $name, $bound, $default);
        }
        if ($bound instanceof ConstantIntegerType && ($boundClass === ConstantIntegerType::class || $bound instanceof \PHPStan\Type\Generic\TemplateType)) {
            return new \PHPStan\Type\Generic\TemplateConstantIntegerType($scope, $strategy, $variance, $name, $bound, $default);
        }
        if ($bound instanceof FloatType && ($boundClass === FloatType::class || $bound instanceof \PHPStan\Type\Generic\TemplateType)) {
            return new \PHPStan\Type\Generic\TemplateFloatType($scope, $strategy, $variance, $name, $bound, $default);
        }
        if ($bound instanceof BooleanType && ($boundClass === BooleanType::class || $bound instanceof \PHPStan\Type\Generic\TemplateType)) {
            return new \PHPStan\Type\Generic\TemplateBooleanType($scope, $strategy, $variance, $name, $bound, $default);
        }
        if ($bound instanceof MixedType && ($boundClass === MixedType::class || $bound instanceof \PHPStan\Type\Generic\TemplateType)) {
            return new \PHPStan\Type\Generic\TemplateMixedType($scope, $strategy, $variance, $name, $bound, $default);
        }
        if ($bound instanceof UnionType) {
            if ($boundClass === UnionType::class || $bound instanceof \PHPStan\Type\Generic\TemplateUnionType) {
                return new \PHPStan\Type\Generic\TemplateUnionType($scope, $strategy, $variance, $name, $bound, $default);
            }
            if ($bound instanceof BenevolentUnionType) {
                return new \PHPStan\Type\Generic\TemplateBenevolentUnionType($scope, $strategy, $variance, $name, $bound, $default);
            }
        }
        if ($bound instanceof IntersectionType) {
            return new \PHPStan\Type\Generic\TemplateIntersectionType($scope, $strategy, $variance, $name, $bound, $default);
        }
        if ($bound instanceof KeyOfType && ($boundClass === KeyOfType::class || $bound instanceof \PHPStan\Type\Generic\TemplateType)) {
            return new \PHPStan\Type\Generic\TemplateKeyOfType($scope, $strategy, $variance, $name, $bound, $default);
        }
        if ($bound instanceof IterableType && ($boundClass === IterableType::class || $bound instanceof \PHPStan\Type\Generic\TemplateType)) {
            return new \PHPStan\Type\Generic\TemplateIterableType($scope, $strategy, $variance, $name, $bound, $default);
        }
        return new \PHPStan\Type\Generic\TemplateMixedType($scope, $strategy, $variance, $name, new MixedType(\true), $default);
    }
    public static function fromTemplateTag(\PHPStan\Type\Generic\TemplateTypeScope $scope, TemplateTag $tag) : \PHPStan\Type\Generic\TemplateType
    {
        return self::create($scope, $tag->getName(), $tag->getBound(), $tag->getVariance(), null, $tag->getDefault());
    }
}
