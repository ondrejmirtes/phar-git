<?php

declare (strict_types=1);
namespace PHPStan\Type;

use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParameterReflection;
/**
 * This is the interface dynamic parameter out type extensions implement for static methods.
 *
 * To register it in the configuration file use the `phpstan.staticMethodParameterOutTypeExtension` service tag:
 *
 * ```
 * services:
 * 	-
 *		class: App\PHPStan\MyExtension
 *		tags:
 *			- phpstan.staticMethodParameterOutTypeExtension
 * ```
 *
 * @api
 */
interface StaticMethodParameterOutTypeExtension
{
    public function isStaticMethodSupported(MethodReflection $methodReflection, ParameterReflection $parameter): bool;
    public function getParameterOutTypeFromStaticMethodCall(MethodReflection $methodReflection, StaticCall $methodCall, ParameterReflection $parameter, Scope $scope): ?\PHPStan\Type\Type;
}
