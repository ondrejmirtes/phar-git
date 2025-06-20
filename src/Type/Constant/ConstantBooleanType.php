<?php

declare (strict_types=1);
namespace PHPStan\Type\Constant;

use PHPStan\Php\PhpVersion;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\TrinaryLogic;
use PHPStan\Type\BooleanType;
use PHPStan\Type\ConstantScalarType;
use PHPStan\Type\GeneralizePrecision;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\StaticTypeFactory;
use PHPStan\Type\Traits\ConstantScalarTypeTrait;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\VerbosityLevel;
/** @api */
class ConstantBooleanType extends BooleanType implements ConstantScalarType
{
    private bool $value;
    use ConstantScalarTypeTrait {
        looseCompare as private scalarLooseCompare;
    }
    /** @api */
    public function __construct(bool $value)
    {
        $this->value = $value;
        parent::__construct();
    }
    public function getValue(): bool
    {
        return $this->value;
    }
    public function describe(VerbosityLevel $level): string
    {
        return $this->value ? 'true' : 'false';
    }
    public function getSmallerType(PhpVersion $phpVersion): Type
    {
        if ($this->value) {
            return StaticTypeFactory::falsey();
        }
        return new NeverType();
    }
    public function getSmallerOrEqualType(PhpVersion $phpVersion): Type
    {
        if ($this->value) {
            return new MixedType();
        }
        return StaticTypeFactory::falsey();
    }
    public function getGreaterType(PhpVersion $phpVersion): Type
    {
        if ($this->value) {
            return new NeverType();
        }
        return StaticTypeFactory::truthy();
    }
    public function getGreaterOrEqualType(PhpVersion $phpVersion): Type
    {
        if ($this->value) {
            return StaticTypeFactory::truthy();
        }
        return new MixedType();
    }
    public function toBoolean(): BooleanType
    {
        return $this;
    }
    public function toNumber(): Type
    {
        return new \PHPStan\Type\Constant\ConstantIntegerType((int) $this->value);
    }
    public function toAbsoluteNumber(): Type
    {
        return $this->toNumber()->toAbsoluteNumber();
    }
    public function toString(): Type
    {
        return new \PHPStan\Type\Constant\ConstantStringType((string) $this->value);
    }
    public function toInteger(): Type
    {
        return new \PHPStan\Type\Constant\ConstantIntegerType((int) $this->value);
    }
    public function toFloat(): Type
    {
        return new \PHPStan\Type\Constant\ConstantFloatType((float) $this->value);
    }
    public function toArrayKey(): Type
    {
        return new \PHPStan\Type\Constant\ConstantIntegerType((int) $this->value);
    }
    public function toCoercedArgumentType(bool $strictTypes): Type
    {
        if (!$strictTypes) {
            return TypeCombinator::union($this->toInteger(), $this->toFloat(), $this->toString(), $this);
        }
        return $this;
    }
    public function isTrue(): TrinaryLogic
    {
        return TrinaryLogic::createFromBoolean($this->value === \true);
    }
    public function isFalse(): TrinaryLogic
    {
        return TrinaryLogic::createFromBoolean($this->value === \false);
    }
    public function generalize(GeneralizePrecision $precision): Type
    {
        return new BooleanType();
    }
    public function toPhpDocNode(): TypeNode
    {
        return new IdentifierTypeNode($this->value ? 'true' : 'false');
    }
    public function looseCompare(Type $type, PhpVersion $phpVersion): BooleanType
    {
        if ($type->isObject()->yes()) {
            return $this;
        }
        return $this->scalarLooseCompare($type, $phpVersion);
    }
}
