<?php

declare (strict_types=1);
namespace PHPStan\Rules\Properties;

use PhpParser\Node;
use PhpParser\Node\Expr\PropertyFetch;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
/**
 * @implements Rule<Node\Expr\PropertyFetch>
 */
final class AccessPropertiesRule implements Rule
{
    private \PHPStan\Rules\Properties\AccessPropertiesCheck $check;
    public function __construct(\PHPStan\Rules\Properties\AccessPropertiesCheck $check)
    {
        $this->check = $check;
    }
    public function getNodeType() : string
    {
        return PropertyFetch::class;
    }
    public function processNode(Node $node, Scope $scope) : array
    {
        return $this->check->check($node, $scope, \false);
    }
}
