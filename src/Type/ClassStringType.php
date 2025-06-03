<?php

declare (strict_types=1);
namespace PHPStan\Type;

use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\TrinaryLogic;
/** @api */
class ClassStringType extends \PHPStan\Type\StringType
{
    /** @api */
    public function __construct()
    {
        parent::__construct();
    }
    public function describe(\PHPStan\Type\VerbosityLevel $level) : string
    {
        return 'class-string';
    }
    public function accepts(\PHPStan\Type\Type $type, bool $strictTypes) : \PHPStan\Type\AcceptsResult
    {
        if ($type instanceof \PHPStan\Type\CompoundType) {
            return $type->isAcceptedBy($this, $strictTypes);
        }
        return new \PHPStan\Type\AcceptsResult($type->isClassString(), []);
    }
    public function isSuperTypeOf(\PHPStan\Type\Type $type) : \PHPStan\Type\IsSuperTypeOfResult
    {
        if ($type instanceof \PHPStan\Type\CompoundType) {
            return $type->isSubTypeOf($this);
        }
        return new \PHPStan\Type\IsSuperTypeOfResult($type->isClassString(), []);
    }
    public function isString() : TrinaryLogic
    {
        return TrinaryLogic::createYes();
    }
    public function isNumericString() : TrinaryLogic
    {
        return TrinaryLogic::createMaybe();
    }
    public function isNonEmptyString() : TrinaryLogic
    {
        return TrinaryLogic::createYes();
    }
    public function isNonFalsyString() : TrinaryLogic
    {
        return TrinaryLogic::createYes();
    }
    public function isLiteralString() : TrinaryLogic
    {
        return TrinaryLogic::createMaybe();
    }
    public function isLowercaseString() : TrinaryLogic
    {
        return TrinaryLogic::createMaybe();
    }
    public function isUppercaseString() : TrinaryLogic
    {
        return TrinaryLogic::createMaybe();
    }
    public function isClassString() : TrinaryLogic
    {
        return TrinaryLogic::createYes();
    }
    public function getClassStringObjectType() : \PHPStan\Type\Type
    {
        return new \PHPStan\Type\ObjectWithoutClassType();
    }
    public function getObjectTypeOrClassStringObjectType() : \PHPStan\Type\Type
    {
        return new \PHPStan\Type\ObjectWithoutClassType();
    }
    public function toPhpDocNode() : TypeNode
    {
        return new IdentifierTypeNode('class-string');
    }
}
