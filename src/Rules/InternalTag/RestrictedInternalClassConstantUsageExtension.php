<?php

declare (strict_types=1);
namespace PHPStan\Rules\InternalTag;

use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassConstantReflection;
use PHPStan\Rules\RestrictedUsage\RestrictedClassConstantUsageExtension;
use PHPStan\Rules\RestrictedUsage\RestrictedUsage;
use function array_slice;
use function explode;
use function sprintf;
use function strtolower;
final class RestrictedInternalClassConstantUsageExtension implements RestrictedClassConstantUsageExtension
{
    private \PHPStan\Rules\InternalTag\RestrictedInternalUsageHelper $helper;
    public function __construct(\PHPStan\Rules\InternalTag\RestrictedInternalUsageHelper $helper)
    {
        $this->helper = $helper;
    }
    public function isRestrictedClassConstantUsage(ClassConstantReflection $constantReflection, Scope $scope): ?RestrictedUsage
    {
        $isConstantInternal = $constantReflection->isInternal()->yes();
        $declaringClass = $constantReflection->getDeclaringClass();
        $isDeclaringClassInternal = $declaringClass->isInternal();
        if (!$isConstantInternal && !$isDeclaringClassInternal) {
            return null;
        }
        $declaringClassName = $declaringClass->getName();
        if (!$this->helper->shouldBeReported($scope, $declaringClassName)) {
            return null;
        }
        $namespace = array_slice(explode('\\', $declaringClassName), 0, -1)[0] ?? null;
        if ($namespace === null) {
            if (!$isConstantInternal) {
                return RestrictedUsage::create(sprintf('Access to constant %s of internal %s %s.', $constantReflection->getName(), strtolower($constantReflection->getDeclaringClass()->getClassTypeDescription()), $constantReflection->getDeclaringClass()->getDisplayName()), sprintf('classConstant.internal%s', $constantReflection->getDeclaringClass()->getClassTypeDescription()));
            }
            return RestrictedUsage::create(sprintf('Access to internal constant %s::%s.', $constantReflection->getDeclaringClass()->getDisplayName(), $constantReflection->getName()), 'classConstant.internal');
        }
        if (!$isConstantInternal) {
            return RestrictedUsage::create(sprintf('Access to constant %s of internal %s %s from outside its root namespace %s.', $constantReflection->getName(), strtolower($constantReflection->getDeclaringClass()->getClassTypeDescription()), $constantReflection->getDeclaringClass()->getDisplayName(), $namespace), sprintf('classConstant.internal%s', $constantReflection->getDeclaringClass()->getClassTypeDescription()));
        }
        return RestrictedUsage::create(sprintf('Access to internal constant %s::%s from outside its root namespace %s.', $constantReflection->getDeclaringClass()->getDisplayName(), $constantReflection->getName(), $namespace), 'classConstant.internal');
    }
}
