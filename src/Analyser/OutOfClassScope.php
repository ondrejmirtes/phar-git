<?php

declare (strict_types=1);
namespace PHPStan\Analyser;

use PHPStan\Reflection\ClassConstantReflection;
use PHPStan\Reflection\ClassMemberAccessAnswerer;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ExtendedPropertyReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\PropertyReflection;
final class OutOfClassScope implements ClassMemberAccessAnswerer
{
    /** @api */
    public function __construct()
    {
    }
    public function isInClass() : bool
    {
        return \false;
    }
    public function getClassReflection() : ?ClassReflection
    {
        return null;
    }
    public function canAccessProperty(PropertyReflection $propertyReflection) : bool
    {
        return $propertyReflection->isPublic();
    }
    public function canReadProperty(ExtendedPropertyReflection $propertyReflection) : bool
    {
        return $propertyReflection->isPublic();
    }
    public function canWriteProperty(ExtendedPropertyReflection $propertyReflection) : bool
    {
        return $propertyReflection->isPublic() && !$propertyReflection->isProtectedSet() && !$propertyReflection->isPrivateSet();
    }
    public function canCallMethod(MethodReflection $methodReflection) : bool
    {
        return $methodReflection->isPublic();
    }
    public function canAccessConstant(ClassConstantReflection $constantReflection) : bool
    {
        return $constantReflection->isPublic();
    }
}
