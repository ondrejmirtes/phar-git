<?php

declare (strict_types=1);
namespace PHPStan\Reflection;

/** @api */
interface ClassMemberAccessAnswerer
{
    /**
     * @phpstan-assert-if-true !null $this->getClassReflection()
     */
    public function isInClass() : bool;
    public function getClassReflection() : ?\PHPStan\Reflection\ClassReflection;
    /**
     * @deprecated Use canReadProperty() or canWriteProperty()
     */
    public function canAccessProperty(\PHPStan\Reflection\PropertyReflection $propertyReflection) : bool;
    public function canReadProperty(\PHPStan\Reflection\ExtendedPropertyReflection $propertyReflection) : bool;
    public function canWriteProperty(\PHPStan\Reflection\ExtendedPropertyReflection $propertyReflection) : bool;
    public function canCallMethod(\PHPStan\Reflection\MethodReflection $methodReflection) : bool;
    public function canAccessConstant(\PHPStan\Reflection\ClassConstantReflection $constantReflection) : bool;
}
