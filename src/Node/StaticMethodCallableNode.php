<?php

declare (strict_types=1);
namespace PHPStan\Node;

use PhpParser\Node\Expr;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
/**
 * @api
 */
final class StaticMethodCallableNode extends Expr implements \PHPStan\Node\VirtualNode
{
    /**
     * @var \PhpParser\Node\Name|\PhpParser\Node\Expr
     */
    private $class;
    /**
     * @var \PhpParser\Node\Identifier|\PhpParser\Node\Expr
     */
    private $name;
    private Expr\StaticCall $originalNode;
    /**
     * @param \PhpParser\Node\Name|\PhpParser\Node\Expr $class
     * @param \PhpParser\Node\Identifier|\PhpParser\Node\Expr $name
     */
    public function __construct($class, $name, Expr\StaticCall $originalNode)
    {
        $this->class = $class;
        $this->name = $name;
        $this->originalNode = $originalNode;
        parent::__construct($originalNode->getAttributes());
    }
    /**
     * @return Expr|Name
     */
    public function getClass()
    {
        return $this->class;
    }
    /**
     * @return Identifier|Expr
     */
    public function getName()
    {
        return $this->name;
    }
    public function getOriginalNode(): Expr\StaticCall
    {
        return $this->originalNode;
    }
    public function getType(): string
    {
        return 'PHPStan_Node_StaticMethodCallableNode';
    }
    /**
     * @return string[]
     */
    public function getSubNodeNames(): array
    {
        return [];
    }
}
