<?php

declare (strict_types=1);
namespace PHPStan\Type\Accessory;

use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Reflection\ClassMemberAccessAnswerer;
use PHPStan\Reflection\Dummy\DummyMethodReflection;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\TrivialParametersAcceptor;
use PHPStan\Reflection\Type\CallbackUnresolvedMethodPrototypeReflection;
use PHPStan\Reflection\Type\UnresolvedMethodPrototypeReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\AcceptsResult;
use PHPStan\Type\CompoundType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\IsSuperTypeOfResult;
use PHPStan\Type\StringType;
use PHPStan\Type\Traits\NonGeneralizableTypeTrait;
use PHPStan\Type\Traits\NonGenericTypeTrait;
use PHPStan\Type\Traits\NonRemoveableTypeTrait;
use PHPStan\Type\Traits\ObjectTypeTrait;
use PHPStan\Type\Traits\UndecidedComparisonCompoundTypeTrait;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use function sprintf;
use function strtolower;
class HasMethodType implements \PHPStan\Type\Accessory\AccessoryType, CompoundType
{
    private string $methodName;
    use ObjectTypeTrait;
    use NonGenericTypeTrait;
    use UndecidedComparisonCompoundTypeTrait;
    use NonRemoveableTypeTrait;
    use NonGeneralizableTypeTrait;
    /** @api */
    public function __construct(string $methodName)
    {
        $this->methodName = $methodName;
    }
    public function getReferencedClasses(): array
    {
        return [];
    }
    public function getObjectClassNames(): array
    {
        return [];
    }
    public function getObjectClassReflections(): array
    {
        return [];
    }
    private function getCanonicalMethodName(): string
    {
        return strtolower($this->methodName);
    }
    public function accepts(Type $type, bool $strictTypes): AcceptsResult
    {
        if ($type instanceof CompoundType) {
            return $type->isAcceptedBy($this, $strictTypes);
        }
        return AcceptsResult::createFromBoolean($this->equals($type));
    }
    public function isSuperTypeOf(Type $type): IsSuperTypeOfResult
    {
        return new IsSuperTypeOfResult($type->hasMethod($this->methodName), []);
    }
    public function isSubTypeOf(Type $otherType): IsSuperTypeOfResult
    {
        if ($otherType instanceof UnionType || $otherType instanceof IntersectionType) {
            return $otherType->isSuperTypeOf($this);
        }
        if ($this->isCallable()->yes() && $otherType->isCallable()->yes()) {
            return IsSuperTypeOfResult::createYes();
        }
        if ($otherType instanceof self) {
            $limit = IsSuperTypeOfResult::createYes();
        } else {
            $limit = IsSuperTypeOfResult::createMaybe();
        }
        return $limit->and(new IsSuperTypeOfResult($otherType->hasMethod($this->methodName), []));
    }
    public function isAcceptedBy(Type $acceptingType, bool $strictTypes): AcceptsResult
    {
        return $this->isSubTypeOf($acceptingType)->toAcceptsResult();
    }
    public function equals(Type $type): bool
    {
        return $type instanceof self && $this->getCanonicalMethodName() === $type->getCanonicalMethodName();
    }
    public function describe(VerbosityLevel $level): string
    {
        return sprintf('hasMethod(%s)', $this->methodName);
    }
    public function isOffsetAccessLegal(): TrinaryLogic
    {
        return TrinaryLogic::createMaybe();
    }
    public function hasMethod(string $methodName): TrinaryLogic
    {
        if ($this->getCanonicalMethodName() === strtolower($methodName)) {
            return TrinaryLogic::createYes();
        }
        return TrinaryLogic::createMaybe();
    }
    public function getMethod(string $methodName, ClassMemberAccessAnswerer $scope): ExtendedMethodReflection
    {
        return $this->getUnresolvedMethodPrototype($methodName, $scope)->getTransformedMethod();
    }
    public function getUnresolvedMethodPrototype(string $methodName, ClassMemberAccessAnswerer $scope): UnresolvedMethodPrototypeReflection
    {
        $method = new DummyMethodReflection($this->methodName);
        return new CallbackUnresolvedMethodPrototypeReflection($method, $method->getDeclaringClass(), \false, static fn(Type $type): Type => $type);
    }
    public function isCallable(): TrinaryLogic
    {
        if ($this->getCanonicalMethodName() === '__invoke') {
            return TrinaryLogic::createYes();
        }
        return TrinaryLogic::createMaybe();
    }
    public function toString(): Type
    {
        if ($this->getCanonicalMethodName() === '__tostring') {
            return new StringType();
        }
        return new ErrorType();
    }
    public function getCallableParametersAcceptors(ClassMemberAccessAnswerer $scope): array
    {
        return [new TrivialParametersAcceptor()];
    }
    public function getEnumCases(): array
    {
        return [];
    }
    public function traverse(callable $cb): Type
    {
        return $this;
    }
    public function traverseSimultaneously(Type $right, callable $cb): Type
    {
        return $this;
    }
    public function exponentiate(Type $exponent): Type
    {
        return new ErrorType();
    }
    public function getFiniteTypes(): array
    {
        return [];
    }
    public function toPhpDocNode(): TypeNode
    {
        return new IdentifierTypeNode('');
        // no PHPDoc representation
    }
}
