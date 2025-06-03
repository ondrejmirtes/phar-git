<?php

declare (strict_types=1);
namespace PHPStan\Node;

/**
 * @api
 */
final class ClassMethod
{
    private \PhpParser\Node\Stmt\ClassMethod $node;
    private bool $isDeclaredInTrait;
    public function __construct(\PhpParser\Node\Stmt\ClassMethod $node, bool $isDeclaredInTrait)
    {
        $this->node = $node;
        $this->isDeclaredInTrait = $isDeclaredInTrait;
    }
    public function getNode() : \PhpParser\Node\Stmt\ClassMethod
    {
        return $this->node;
    }
    public function isDeclaredInTrait() : bool
    {
        return $this->isDeclaredInTrait;
    }
}
