<?php

declare (strict_types=1);
namespace PHPStan\Type\Generic;

use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Reflection\ReflectionProviderStaticAccessor;
use PHPStan\Type\AcceptsResult;
use PHPStan\Type\ClassStringType;
use PHPStan\Type\CompoundType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\IsSuperTypeOfResult;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\StaticType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use function count;
use function sprintf;
/** @api */
class GenericClassStringType extends ClassStringType
{
    private Type $type;
    /** @api */
    public function __construct(Type $type)
    {
        $this->type = $type;
        parent::__construct();
    }
    public function getReferencedClasses(): array
    {
        return $this->type->getReferencedClasses();
    }
    public function getGenericType(): Type
    {
        return $this->type;
    }
    public function getClassStringObjectType(): Type
    {
        return $this->getGenericType();
    }
    public function getObjectTypeOrClassStringObjectType(): Type
    {
        return $this->getClassStringObjectType();
    }
    public function describe(VerbosityLevel $level): string
    {
        return sprintf('%s<%s>', parent::describe($level), $this->type->describe($level));
    }
    public function accepts(Type $type, bool $strictTypes): AcceptsResult
    {
        if ($type instanceof CompoundType) {
            return $type->isAcceptedBy($this, $strictTypes);
        }
        if ($type instanceof ConstantStringType) {
            if (!$type->isClassString()->yes()) {
                return AcceptsResult::createNo();
            }
            $objectType = new ObjectType($type->getValue());
        } elseif ($type instanceof self) {
            $objectType = $type->type;
        } elseif ($type instanceof ClassStringType) {
            $objectType = new ObjectWithoutClassType();
        } elseif ($type instanceof StringType) {
            return AcceptsResult::createMaybe();
        } else {
            return AcceptsResult::createNo();
        }
        return $this->type->accepts($objectType, $strictTypes);
    }
    public function isSuperTypeOf(Type $type): IsSuperTypeOfResult
    {
        if ($type instanceof CompoundType) {
            return $type->isSubTypeOf($this);
        }
        if ($type instanceof ConstantStringType) {
            $genericType = $this->type;
            if ($genericType instanceof MixedType) {
                return IsSuperTypeOfResult::createYes();
            }
            if ($genericType instanceof StaticType) {
                $genericType = $genericType->getStaticObjectType();
            }
            // We are transforming constant class-string to ObjectType. But we need to filter out
            // an uncertainty originating in possible ObjectType's class subtypes.
            $objectType = new ObjectType($type->getValue());
            // Do not use TemplateType's isSuperTypeOf handling directly because it takes ObjectType
            // uncertainty into account.
            if ($genericType instanceof \PHPStan\Type\Generic\TemplateType) {
                $isSuperType = $genericType->getBound()->isSuperTypeOf($objectType);
            } else {
                $isSuperType = $genericType->isSuperTypeOf($objectType);
            }
            if (!$type->isClassString()->yes()) {
                $isSuperType = $isSuperType->and(IsSuperTypeOfResult::createMaybe());
            }
            return $isSuperType;
        } elseif ($type instanceof self) {
            return $this->type->isSuperTypeOf($type->type);
        } elseif ($type instanceof StringType) {
            return IsSuperTypeOfResult::createMaybe();
        }
        return IsSuperTypeOfResult::createNo();
    }
    public function traverse(callable $cb): Type
    {
        $newType = $cb($this->type);
        if ($newType === $this->type) {
            return $this;
        }
        return new self($newType);
    }
    public function traverseSimultaneously(Type $right, callable $cb): Type
    {
        $newType = $cb($this->type, $right->getClassStringObjectType());
        if ($newType === $this->type) {
            return $this;
        }
        return new self($newType);
    }
    public function inferTemplateTypes(Type $receivedType): \PHPStan\Type\Generic\TemplateTypeMap
    {
        if ($receivedType instanceof UnionType || $receivedType instanceof IntersectionType) {
            return $receivedType->inferTemplateTypesOn($this);
        }
        if ($receivedType instanceof ConstantStringType) {
            $typeToInfer = new ObjectType($receivedType->getValue());
        } elseif ($receivedType instanceof self) {
            $typeToInfer = $receivedType->type;
        } elseif ($receivedType->isClassString()->yes()) {
            $typeToInfer = $this->type;
            if ($typeToInfer instanceof \PHPStan\Type\Generic\TemplateType) {
                $typeToInfer = $typeToInfer->getBound();
            }
            $typeToInfer = TypeCombinator::intersect($typeToInfer, new ObjectWithoutClassType());
        } else {
            return \PHPStan\Type\Generic\TemplateTypeMap::createEmpty();
        }
        return $this->type->inferTemplateTypes($typeToInfer);
    }
    public function getReferencedTemplateTypes(\PHPStan\Type\Generic\TemplateTypeVariance $positionVariance): array
    {
        $variance = $positionVariance->compose(\PHPStan\Type\Generic\TemplateTypeVariance::createCovariant());
        return $this->type->getReferencedTemplateTypes($variance);
    }
    public function equals(Type $type): bool
    {
        if (!$type instanceof self) {
            return \false;
        }
        if (!parent::equals($type)) {
            return \false;
        }
        if (!$this->type->equals($type->type)) {
            return \false;
        }
        return \true;
    }
    public function toPhpDocNode(): TypeNode
    {
        return new GenericTypeNode(new IdentifierTypeNode('class-string'), [$this->type->toPhpDocNode()]);
    }
    public function tryRemove(Type $typeToRemove): ?Type
    {
        if ($typeToRemove instanceof ConstantStringType && $typeToRemove->isClassString()->yes()) {
            $generic = $this->getGenericType();
            $genericObjectClassNames = $generic->getObjectClassNames();
            if (count($genericObjectClassNames) === 1) {
                $classReflection = ReflectionProviderStaticAccessor::getInstance()->getClass($genericObjectClassNames[0]);
                if ($classReflection->isFinal() && $genericObjectClassNames[0] === $typeToRemove->getValue()) {
                    return new NeverType();
                }
            }
        }
        return parent::tryRemove($typeToRemove);
    }
}
