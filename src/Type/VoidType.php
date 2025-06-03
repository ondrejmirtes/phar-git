<?php

declare (strict_types=1);
namespace PHPStan\Type;

use PHPStan\Php\PhpVersion;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Traits\FalseyBooleanTypeTrait;
use PHPStan\Type\Traits\NonArrayTypeTrait;
use PHPStan\Type\Traits\NonCallableTypeTrait;
use PHPStan\Type\Traits\NonGeneralizableTypeTrait;
use PHPStan\Type\Traits\NonGenericTypeTrait;
use PHPStan\Type\Traits\NonIterableTypeTrait;
use PHPStan\Type\Traits\NonObjectTypeTrait;
use PHPStan\Type\Traits\NonOffsetAccessibleTypeTrait;
use PHPStan\Type\Traits\NonRemoveableTypeTrait;
use PHPStan\Type\Traits\UndecidedComparisonTypeTrait;
/** @api */
class VoidType implements \PHPStan\Type\Type
{
    use NonArrayTypeTrait;
    use NonCallableTypeTrait;
    use NonIterableTypeTrait;
    use NonObjectTypeTrait;
    use NonOffsetAccessibleTypeTrait;
    use FalseyBooleanTypeTrait;
    use NonGenericTypeTrait;
    use UndecidedComparisonTypeTrait;
    use NonRemoveableTypeTrait;
    use NonGeneralizableTypeTrait;
    /** @api */
    public function __construct()
    {
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
    public function accepts(\PHPStan\Type\Type $type, bool $strictTypes): \PHPStan\Type\AcceptsResult
    {
        if ($type instanceof \PHPStan\Type\CompoundType) {
            return $type->isAcceptedBy($this, $strictTypes);
        }
        return new \PHPStan\Type\AcceptsResult($type->isVoid()->or($type->isNull()), []);
    }
    public function isSuperTypeOf(\PHPStan\Type\Type $type): \PHPStan\Type\IsSuperTypeOfResult
    {
        if ($type instanceof self) {
            return \PHPStan\Type\IsSuperTypeOfResult::createYes();
        }
        if ($type instanceof \PHPStan\Type\CompoundType) {
            return $type->isSubTypeOf($this);
        }
        return \PHPStan\Type\IsSuperTypeOfResult::createNo();
    }
    public function equals(\PHPStan\Type\Type $type): bool
    {
        return $type instanceof self;
    }
    public function describe(\PHPStan\Type\VerbosityLevel $level): string
    {
        return 'void';
    }
    public function toNumber(): \PHPStan\Type\Type
    {
        return new \PHPStan\Type\ErrorType();
    }
    public function toAbsoluteNumber(): \PHPStan\Type\Type
    {
        return new \PHPStan\Type\ErrorType();
    }
    public function toString(): \PHPStan\Type\Type
    {
        return new \PHPStan\Type\ErrorType();
    }
    public function toInteger(): \PHPStan\Type\Type
    {
        return new \PHPStan\Type\ErrorType();
    }
    public function toFloat(): \PHPStan\Type\Type
    {
        return new \PHPStan\Type\ErrorType();
    }
    public function toArray(): \PHPStan\Type\Type
    {
        return new \PHPStan\Type\ErrorType();
    }
    public function toArrayKey(): \PHPStan\Type\Type
    {
        return new \PHPStan\Type\ErrorType();
    }
    public function toCoercedArgumentType(bool $strictTypes): \PHPStan\Type\Type
    {
        return new \PHPStan\Type\NullType();
    }
    public function isOffsetAccessLegal(): TrinaryLogic
    {
        return TrinaryLogic::createYes();
    }
    public function isNull(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isConstantValue(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isConstantScalarValue(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function getConstantScalarTypes(): array
    {
        return [];
    }
    public function getConstantScalarValues(): array
    {
        return [];
    }
    public function isTrue(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isFalse(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isBoolean(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isFloat(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isInteger(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isString(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isNumericString(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isNonEmptyString(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isNonFalsyString(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isLiteralString(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isLowercaseString(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isUppercaseString(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isClassString(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function getClassStringObjectType(): \PHPStan\Type\Type
    {
        return new \PHPStan\Type\ErrorType();
    }
    public function getObjectTypeOrClassStringObjectType(): \PHPStan\Type\Type
    {
        return new \PHPStan\Type\ErrorType();
    }
    public function isVoid(): TrinaryLogic
    {
        return TrinaryLogic::createYes();
    }
    public function isScalar(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function looseCompare(\PHPStan\Type\Type $type, PhpVersion $phpVersion): \PHPStan\Type\BooleanType
    {
        return new \PHPStan\Type\BooleanType();
    }
    public function traverse(callable $cb): \PHPStan\Type\Type
    {
        return $this;
    }
    public function traverseSimultaneously(\PHPStan\Type\Type $right, callable $cb): \PHPStan\Type\Type
    {
        return $this;
    }
    public function exponentiate(\PHPStan\Type\Type $exponent): \PHPStan\Type\Type
    {
        return new \PHPStan\Type\ErrorType();
    }
    public function getFiniteTypes(): array
    {
        return [];
    }
    public function toPhpDocNode(): TypeNode
    {
        return new IdentifierTypeNode('void');
    }
}
