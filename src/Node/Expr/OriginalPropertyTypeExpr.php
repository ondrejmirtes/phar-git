<?php

declare (strict_types=1);
namespace PHPStan\Node\Expr;

use PhpParser\Node\Expr;
use PHPStan\Node\VirtualNode;
final class OriginalPropertyTypeExpr extends Expr implements VirtualNode
{
    /**
     * @var \PhpParser\Node\Expr\PropertyFetch|\PhpParser\Node\Expr\StaticPropertyFetch
     */
    private $propertyFetch;
    /**
     * @param \PhpParser\Node\Expr\PropertyFetch|\PhpParser\Node\Expr\StaticPropertyFetch $propertyFetch
     */
    public function __construct($propertyFetch)
    {
        $this->propertyFetch = $propertyFetch;
        parent::__construct([]);
    }
    /**
     * @return \PhpParser\Node\Expr\PropertyFetch|\PhpParser\Node\Expr\StaticPropertyFetch
     */
    public function getPropertyFetch()
    {
        return $this->propertyFetch;
    }
    public function getType(): string
    {
        return 'PHPStan_Node_OriginalPropertyTypeExpr';
    }
    /**
     * @return string[]
     */
    public function getSubNodeNames(): array
    {
        return [];
    }
}
