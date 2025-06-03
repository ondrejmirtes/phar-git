<?php

declare (strict_types=1);
namespace PHPStan\Analyser;

use PhpParser\Node;
/**
 * This is the extension interface to implement if you want to ignore errors
 * based on the node and scope.
 *
 * To register it in the configuration file use the `phpstan.ignoreErrorExtension` service tag:
 *
 * ```
 * services:
 * 	-
 *		class: App\PHPStan\MyExtension
 *		tags:
 *			- phpstan.ignoreErrorExtension
 * ```
 *
 * Learn more: https://phpstan.org/developing-extensions/ignore-error-extensions
 *
 * @api
 */
interface IgnoreErrorExtension
{
    public const EXTENSION_TAG = 'phpstan.ignoreErrorExtension';
    public function shouldIgnore(\PHPStan\Analyser\Error $error, Node $node, \PHPStan\Analyser\Scope $scope): bool;
}
