<?php

declare (strict_types=1);
namespace PHPStan\Node\Property;

use PHPStan\Analyser\Scope;
use PHPStan\Node\PropertyAssignNode;
/**
 * @api
 */
final class PropertyAssign
{
    private PropertyAssignNode $assign;
    private Scope $scope;
    public function __construct(PropertyAssignNode $assign, Scope $scope)
    {
        $this->assign = $assign;
        $this->scope = $scope;
    }
    public function getAssign(): PropertyAssignNode
    {
        return $this->assign;
    }
    public function getScope(): Scope
    {
        return $this->scope;
    }
}
