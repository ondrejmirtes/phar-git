<?php

declare (strict_types=1);
namespace PHPStan\Reflection;

use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;
/** @api */
interface MethodReflection extends \PHPStan\Reflection\ClassMemberReflection
{
    public function getName(): string;
    public function getPrototype(): \PHPStan\Reflection\ClassMemberReflection;
    /**
     * @return list<ParametersAcceptor>
     */
    public function getVariants(): array;
    public function isDeprecated(): TrinaryLogic;
    public function getDeprecatedDescription(): ?string;
    public function isFinal(): TrinaryLogic;
    public function isInternal(): TrinaryLogic;
    public function getThrowType(): ?Type;
    public function hasSideEffects(): TrinaryLogic;
}
