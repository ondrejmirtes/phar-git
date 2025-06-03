<?php

declare (strict_types=1);
namespace PHPStan\Php;

use PHPStan\TrinaryLogic;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\Type;
/**
 * @api
 */
final class PhpVersions
{
    private Type $phpVersions;
    public function __construct(Type $phpVersions)
    {
        $this->phpVersions = $phpVersions;
    }
    public function getType() : Type
    {
        return $this->phpVersions;
    }
    public function supportsNoncapturingCatches() : TrinaryLogic
    {
        return IntegerRangeType::fromInterval(80000, null)->isSuperTypeOf($this->phpVersions)->result;
    }
    public function producesWarningForFinalPrivateMethods() : TrinaryLogic
    {
        return IntegerRangeType::fromInterval(80000, null)->isSuperTypeOf($this->phpVersions)->result;
    }
    public function supportsNamedArguments() : TrinaryLogic
    {
        return IntegerRangeType::fromInterval(80000, null)->isSuperTypeOf($this->phpVersions)->result;
    }
    public function supportsNamedArgumentAfterUnpackedArgument() : TrinaryLogic
    {
        return IntegerRangeType::fromInterval(80100, null)->isSuperTypeOf($this->phpVersions)->result;
    }
}
