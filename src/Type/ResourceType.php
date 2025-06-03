<?php

declare (strict_types=1);
namespace PHPStan\Type;

use PHPStan\Php\PhpVersion;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Traits\NonArrayTypeTrait;
use PHPStan\Type\Traits\NonCallableTypeTrait;
use PHPStan\Type\Traits\NonGeneralizableTypeTrait;
use PHPStan\Type\Traits\NonGenericTypeTrait;
use PHPStan\Type\Traits\NonIterableTypeTrait;
use PHPStan\Type\Traits\NonObjectTypeTrait;
use PHPStan\Type\Traits\NonOffsetAccessibleTypeTrait;
use PHPStan\Type\Traits\NonRemoveableTypeTrait;
use PHPStan\Type\Traits\TruthyBooleanTypeTrait;
use PHPStan\Type\Traits\UndecidedComparisonTypeTrait;
/** @api */
class ResourceType implements \PHPStan\Type\Type
{
    use \PHPStan\Type\JustNullableTypeTrait;
    use NonArrayTypeTrait;
    use NonCallableTypeTrait;
    use NonIterableTypeTrait;
    use NonObjectTypeTrait;
    use TruthyBooleanTypeTrait;
    use NonGenericTypeTrait;
    use UndecidedComparisonTypeTrait;
    use NonOffsetAccessibleTypeTrait;
    use NonRemoveableTypeTrait;
    use NonGeneralizableTypeTrait;
    /** @api */
    public function __construct()
    {
    }
    public function describe(\PHPStan\Type\VerbosityLevel $level): string
    {
        return 'resource';
    }
    public function getConstantStrings(): array
    {
        return [];
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
        return new \PHPStan\Type\StringType();
    }
    public function toInteger(): \PHPStan\Type\Type
    {
        return new \PHPStan\Type\IntegerType();
    }
    public function toFloat(): \PHPStan\Type\Type
    {
        return new \PHPStan\Type\ErrorType();
    }
    public function toArray(): \PHPStan\Type\Type
    {
        return new ConstantArrayType([new ConstantIntegerType(0)], [$this], [1], [], TrinaryLogic::createYes());
    }
    public function toArrayKey(): \PHPStan\Type\Type
    {
        return new \PHPStan\Type\ErrorType();
    }
    public function toCoercedArgumentType(bool $strictTypes): \PHPStan\Type\Type
    {
        return $this;
    }
    public function isOffsetAccessLegal(): TrinaryLogic
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
        return new IdentifierTypeNode('resource');
    }
}
