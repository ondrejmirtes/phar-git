<?php

declare (strict_types=1);
namespace PHPStan\Rules;

use Closure;
use Generator;
use Iterator;
use IteratorAggregate;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Accessory\AccessoryType;
use PHPStan\Type\CallableType;
use PHPStan\Type\ClosureType;
use PHPStan\Type\ConditionalType;
use PHPStan\Type\ConditionalTypeForParameter;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Generic\GenericStaticType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateTypeHelper;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;
use Traversable;
use function array_filter;
use function array_keys;
use function array_merge;
use function count;
use function implode;
use function in_array;
use function sprintf;
use function strtolower;
final class MissingTypehintCheck
{
    private bool $checkMissingCallableSignature;
    /**
     * @var string[]
     */
    private array $skipCheckGenericClasses;
    public const MISSING_ITERABLE_VALUE_TYPE_TIP = 'See: https://phpstan.org/blog/solving-phpstan-no-value-type-specified-in-iterable-type';
    private const ITERABLE_GENERIC_CLASS_NAMES = [Traversable::class, Iterator::class, IteratorAggregate::class, Generator::class];
    /**
     * @param string[] $skipCheckGenericClasses
     */
    public function __construct(bool $checkMissingCallableSignature, array $skipCheckGenericClasses)
    {
        $this->checkMissingCallableSignature = $checkMissingCallableSignature;
        $this->skipCheckGenericClasses = $skipCheckGenericClasses;
    }
    /**
     * @return Type[]
     */
    public function getIterableTypesWithMissingValueTypehint(Type $type): array
    {
        $iterablesWithMissingValueTypehint = [];
        TypeTraverser::map($type, function (Type $type, callable $traverse) use (&$iterablesWithMissingValueTypehint): Type {
            if ($type instanceof TemplateType) {
                return $type;
            }
            if ($type instanceof AccessoryType) {
                return $type;
            }
            if ($type instanceof ConditionalType || $type instanceof ConditionalTypeForParameter) {
                $iterablesWithMissingValueTypehint = array_merge($iterablesWithMissingValueTypehint, $this->getIterableTypesWithMissingValueTypehint($type->getIf()), $this->getIterableTypesWithMissingValueTypehint($type->getElse()));
                return $type;
            }
            if ($type->isIterable()->yes()) {
                $iterableValue = $type->getIterableValueType();
                if ($iterableValue instanceof MixedType && !$iterableValue->isExplicitMixed()) {
                    $iterablesWithMissingValueTypehint[] = $type;
                }
                if ($type instanceof IntersectionType) {
                    if ($type->isList()->yes()) {
                        return $traverse($iterableValue);
                    }
                    return $type;
                }
            }
            return $traverse($type);
        });
        return $iterablesWithMissingValueTypehint;
    }
    /**
     * @return array<int, array{string, string}>
     */
    public function getNonGenericObjectTypesWithGenericClass(Type $type): array
    {
        $objectTypes = [];
        TypeTraverser::map($type, function (Type $type, callable $traverse) use (&$objectTypes): Type {
            if ($type instanceof GenericObjectType || $type instanceof GenericStaticType) {
                $traverse($type);
                return $type;
            }
            if ($type instanceof TemplateType) {
                return $type;
            }
            if ($type instanceof ObjectType) {
                $classReflection = $type->getClassReflection();
                if ($classReflection === null) {
                    return $type;
                }
                if (in_array($classReflection->getName(), self::ITERABLE_GENERIC_CLASS_NAMES, \true)) {
                    // checked by getIterableTypesWithMissingValueTypehint() already
                    return $type;
                }
                if (in_array($classReflection->getName(), $this->skipCheckGenericClasses, \true)) {
                    return $type;
                }
                if ($classReflection->isTrait()) {
                    return $type;
                }
                if (!$classReflection->isGeneric()) {
                    return $type;
                }
                $resolvedType = TemplateTypeHelper::resolveToBounds($type);
                if (!$resolvedType instanceof ObjectType) {
                    throw new ShouldNotHappenException();
                }
                $templateTypes = $classReflection->getTemplateTypeMap()->getTypes();
                $templateTypesCount = count($templateTypes);
                $requiredTemplateTypesCount = count(array_filter($templateTypes, static fn(Type $type) => $type instanceof TemplateType && $type->getDefault() === null));
                if ($requiredTemplateTypesCount === 0) {
                    return $type;
                }
                $templateTypesList = implode(', ', array_keys($templateTypes));
                if ($requiredTemplateTypesCount !== $templateTypesCount) {
                    $templateTypesList .= sprintf(' (%d-%d required)', $requiredTemplateTypesCount, $templateTypesCount);
                }
                $objectTypes[] = [sprintf('%s %s', strtolower($classReflection->getClassTypeDescription()), $classReflection->getDisplayName(\false)), $templateTypesList];
                return $type;
            }
            return $traverse($type);
        });
        return $objectTypes;
    }
    /**
     * @return Type[]
     */
    public function getCallablesWithMissingSignature(Type $type): array
    {
        if (!$this->checkMissingCallableSignature) {
            return [];
        }
        $result = [];
        TypeTraverser::map($type, static function (Type $type, callable $traverse) use (&$result): Type {
            if ($type instanceof CallableType && $type->isCommonCallable() || $type instanceof ClosureType && $type->isCommonCallable() || $type instanceof ObjectType && $type->getClassName() === Closure::class) {
                $result[] = $type;
            }
            return $traverse($type);
        });
        return $result;
    }
}
