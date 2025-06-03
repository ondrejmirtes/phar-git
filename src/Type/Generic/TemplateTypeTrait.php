<?php

declare (strict_types=1);
namespace PHPStan\Type\Generic;

use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\TrinaryLogic;
use PHPStan\Type\AcceptsResult;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\IsSuperTypeOfResult;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\SubtractableType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use function sprintf;
/**
 * @template TBound of Type
 */
trait TemplateTypeTrait
{
    /** @var non-empty-string */
    private string $name;
    private \PHPStan\Type\Generic\TemplateTypeScope $scope;
    private \PHPStan\Type\Generic\TemplateTypeStrategy $strategy;
    private \PHPStan\Type\Generic\TemplateTypeVariance $variance;
    /** @var TBound */
    private Type $bound;
    private ?Type $default;
    /** @return non-empty-string */
    public function getName() : string
    {
        return $this->name;
    }
    public function getScope() : \PHPStan\Type\Generic\TemplateTypeScope
    {
        return $this->scope;
    }
    /** @return TBound */
    public function getBound() : Type
    {
        return $this->bound;
    }
    public function getDefault() : ?Type
    {
        return $this->default;
    }
    public function describe(VerbosityLevel $level) : string
    {
        $basicDescription = function () use($level) : string {
            // @phpstan-ignore booleanAnd.alwaysFalse, instanceof.alwaysFalse, booleanAnd.alwaysFalse, instanceof.alwaysFalse, instanceof.alwaysTrue
            if ($this->bound instanceof MixedType && $this->bound->getSubtractedType() === null && !$this->bound instanceof \PHPStan\Type\Generic\TemplateMixedType) {
                $boundDescription = '';
            } else {
                $boundDescription = sprintf(' of %s', $this->bound->describe($level));
            }
            $defaultDescription = $this->default !== null ? sprintf(' = %s', $this->default->describe($level)) : '';
            return sprintf('%s%s%s', $this->name, $boundDescription, $defaultDescription);
        };
        return $level->handle($basicDescription, $basicDescription, fn(): string => sprintf('%s (%s, %s)', $basicDescription(), $this->scope->describe(), $this->isArgument() ? 'argument' : 'parameter'));
    }
    public function isArgument() : bool
    {
        return $this->strategy->isArgument();
    }
    public function toArgument() : \PHPStan\Type\Generic\TemplateType
    {
        return new self($this->scope, new \PHPStan\Type\Generic\TemplateTypeArgumentStrategy(), $this->variance, $this->name, \PHPStan\Type\Generic\TemplateTypeHelper::toArgument($this->getBound()), $this->default !== null ? \PHPStan\Type\Generic\TemplateTypeHelper::toArgument($this->default) : null);
    }
    public function isValidVariance(Type $a, Type $b) : IsSuperTypeOfResult
    {
        return $this->variance->isValidVariance($this, $a, $b);
    }
    public function subtract(Type $typeToRemove) : Type
    {
        $removedBound = TypeCombinator::remove($this->getBound(), $typeToRemove);
        return \PHPStan\Type\Generic\TemplateTypeFactory::create($this->getScope(), $this->getName(), $removedBound, $this->getVariance(), $this->getStrategy(), $this->getDefault());
    }
    public function getTypeWithoutSubtractedType() : Type
    {
        $bound = $this->getBound();
        if (!$bound instanceof SubtractableType) {
            // @phpstan-ignore instanceof.alwaysTrue
            return $this;
        }
        return \PHPStan\Type\Generic\TemplateTypeFactory::create($this->getScope(), $this->getName(), $bound->getTypeWithoutSubtractedType(), $this->getVariance(), $this->getStrategy(), $this->getDefault());
    }
    public function changeSubtractedType(?Type $subtractedType) : Type
    {
        $bound = $this->getBound();
        if (!$bound instanceof SubtractableType) {
            // @phpstan-ignore instanceof.alwaysTrue
            return $this;
        }
        return \PHPStan\Type\Generic\TemplateTypeFactory::create($this->getScope(), $this->getName(), $bound->changeSubtractedType($subtractedType), $this->getVariance(), $this->getStrategy(), $this->getDefault());
    }
    public function getSubtractedType() : ?Type
    {
        $bound = $this->getBound();
        if (!$bound instanceof SubtractableType) {
            // @phpstan-ignore instanceof.alwaysTrue
            return null;
        }
        return $bound->getSubtractedType();
    }
    public function equals(Type $type) : bool
    {
        return $type instanceof self && $type->scope->equals($this->scope) && $type->name === $this->name && $this->bound->equals($type->bound) && ($this->default === null && $type->default === null || $this->default !== null && $type->default !== null && $this->default->equals($type->default));
    }
    public function isAcceptedBy(Type $acceptingType, bool $strictTypes) : AcceptsResult
    {
        /** @var TBound $bound */
        $bound = $this->getBound();
        if (!$acceptingType instanceof $bound && !$this instanceof $acceptingType && !$acceptingType instanceof \PHPStan\Type\Generic\TemplateType && ($acceptingType instanceof UnionType || $acceptingType instanceof IntersectionType)) {
            return $acceptingType->accepts($this, $strictTypes);
        }
        if (!$acceptingType instanceof \PHPStan\Type\Generic\TemplateType) {
            return $acceptingType->accepts($this->getBound(), $strictTypes);
        }
        if ($this->getScope()->equals($acceptingType->getScope()) && $this->getName() === $acceptingType->getName()) {
            return $acceptingType->getBound()->accepts($this->getBound(), $strictTypes);
        }
        return $acceptingType->getBound()->accepts($this->getBound(), $strictTypes)->and(new AcceptsResult(TrinaryLogic::createMaybe(), []));
    }
    public function accepts(Type $type, bool $strictTypes) : AcceptsResult
    {
        return $this->strategy->accepts($this, $type, $strictTypes);
    }
    public function isSuperTypeOf(Type $type) : IsSuperTypeOfResult
    {
        if ($type instanceof \PHPStan\Type\Generic\TemplateType || $type instanceof IntersectionType) {
            return $type->isSubTypeOf($this);
        }
        if ($type instanceof NeverType) {
            return IsSuperTypeOfResult::createYes();
        }
        return $this->getBound()->isSuperTypeOf($type)->and(IsSuperTypeOfResult::createMaybe());
    }
    public function isSubTypeOf(Type $type) : IsSuperTypeOfResult
    {
        /** @var TBound $bound */
        $bound = $this->getBound();
        if (!$type instanceof $bound && !$this instanceof $type && !$type instanceof \PHPStan\Type\Generic\TemplateType && ($type instanceof UnionType || $type instanceof IntersectionType)) {
            return $type->isSuperTypeOf($this);
        }
        if (!$type instanceof \PHPStan\Type\Generic\TemplateType) {
            return $type->isSuperTypeOf($this->getBound());
        }
        if ($this->getScope()->equals($type->getScope()) && $this->getName() === $type->getName()) {
            return $type->getBound()->isSuperTypeOf($this->getBound());
        }
        return $type->getBound()->isSuperTypeOf($this->getBound())->and(IsSuperTypeOfResult::createMaybe());
    }
    public function toArrayKey() : Type
    {
        return $this;
    }
    public function toCoercedArgumentType(bool $strictTypes) : Type
    {
        return $this;
    }
    public function inferTemplateTypes(Type $receivedType) : \PHPStan\Type\Generic\TemplateTypeMap
    {
        if ($receivedType instanceof \PHPStan\Type\Generic\TemplateType && $this->getBound()->isSuperTypeOf($receivedType->getBound())->yes()) {
            return new \PHPStan\Type\Generic\TemplateTypeMap([$this->name => $receivedType]);
        }
        $map = $this->getBound()->inferTemplateTypes($receivedType);
        $resolvedBound = TypeUtils::resolveLateResolvableTypes(\PHPStan\Type\Generic\TemplateTypeHelper::resolveTemplateTypes($this->getBound(), $map, \PHPStan\Type\Generic\TemplateTypeVarianceMap::createEmpty(), \PHPStan\Type\Generic\TemplateTypeVariance::createStatic()));
        if ($resolvedBound->isSuperTypeOf($receivedType)->yes()) {
            return (new \PHPStan\Type\Generic\TemplateTypeMap([$this->name => $receivedType]))->union($map);
        }
        return $map;
    }
    public function getReferencedTemplateTypes(\PHPStan\Type\Generic\TemplateTypeVariance $positionVariance) : array
    {
        return [new \PHPStan\Type\Generic\TemplateTypeReference($this, $positionVariance)];
    }
    public function getVariance() : \PHPStan\Type\Generic\TemplateTypeVariance
    {
        return $this->variance;
    }
    public function getStrategy() : \PHPStan\Type\Generic\TemplateTypeStrategy
    {
        return $this->strategy;
    }
    public function traverse(callable $cb) : Type
    {
        $bound = $cb($this->getBound());
        $default = $this->getDefault() !== null ? $cb($this->getDefault()) : null;
        if ($this->getBound() === $bound && $this->getDefault() === $default) {
            return $this;
        }
        return \PHPStan\Type\Generic\TemplateTypeFactory::create($this->getScope(), $this->getName(), $bound, $this->getVariance(), $this->getStrategy(), $default);
    }
    public function traverseSimultaneously(Type $right, callable $cb) : Type
    {
        if (!$right instanceof \PHPStan\Type\Generic\TemplateType) {
            return $this;
        }
        $bound = $cb($this->getBound(), $right->getBound());
        $default = $this->getDefault() !== null && $right->getDefault() !== null ? $cb($this->getDefault(), $right->getDefault()) : null;
        if ($this->getBound() === $bound && $this->getDefault() === $default) {
            return $this;
        }
        return \PHPStan\Type\Generic\TemplateTypeFactory::create($this->getScope(), $this->getName(), $bound, $this->getVariance(), $this->getStrategy(), $default);
    }
    public function tryRemove(Type $typeToRemove) : ?Type
    {
        $bound = TypeCombinator::remove($this->getBound(), $typeToRemove);
        if ($this->getBound() === $bound) {
            return null;
        }
        return \PHPStan\Type\Generic\TemplateTypeFactory::create($this->getScope(), $this->getName(), $bound, $this->getVariance(), $this->getStrategy(), $this->getDefault());
    }
    public function toPhpDocNode() : TypeNode
    {
        return new IdentifierTypeNode($this->name);
    }
}
