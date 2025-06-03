<?php

declare (strict_types=1);
namespace PHPStan\Type\Generic;

use PHPStan\Type\AcceptsResult;
use PHPStan\Type\Type;
interface TemplateTypeStrategy
{
    public function accepts(\PHPStan\Type\Generic\TemplateType $left, Type $right, bool $strictTypes) : AcceptsResult;
    public function isArgument() : bool;
}
