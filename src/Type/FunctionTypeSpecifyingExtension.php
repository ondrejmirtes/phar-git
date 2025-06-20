<?php

declare (strict_types=1);
namespace PHPStan\Type;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\Reflection\FunctionReflection;
/**
 * This is the interface type-specifying extensions implement for functions.
 *
 * To register it in the configuration file use the `phpstan.typeSpecifier.functionTypeSpecifyingExtension` service tag:
 *
 * ```
 * services:
 * 	-
 *		class: App\PHPStan\MyExtension
 *		tags:
 *			- phpstan.typeSpecifier.functionTypeSpecifyingExtension
 * ```
 *
 * Learn more: https://phpstan.org/developing-extensions/type-specifying-extensions
 *
 * @api
 */
interface FunctionTypeSpecifyingExtension
{
    public function isFunctionSupported(FunctionReflection $functionReflection, FuncCall $node, TypeSpecifierContext $context): bool;
    public function specifyTypes(FunctionReflection $functionReflection, FuncCall $node, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes;
}
