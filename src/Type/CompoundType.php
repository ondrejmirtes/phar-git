<?php

declare (strict_types=1);
namespace PHPStan\Type;

use PHPStan\Php\PhpVersion;
use PHPStan\TrinaryLogic;
/** @api */
interface CompoundType extends \PHPStan\Type\Type
{
    public function isAcceptedBy(\PHPStan\Type\Type $acceptingType, bool $strictTypes) : \PHPStan\Type\AcceptsResult;
    public function isSubTypeOf(\PHPStan\Type\Type $otherType) : \PHPStan\Type\IsSuperTypeOfResult;
    public function isGreaterThan(\PHPStan\Type\Type $otherType, PhpVersion $phpVersion) : TrinaryLogic;
    public function isGreaterThanOrEqual(\PHPStan\Type\Type $otherType, PhpVersion $phpVersion) : TrinaryLogic;
}
