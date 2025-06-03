<?php

declare (strict_types=1);
namespace PHPStan\Node;

use PhpParser\Node;
use PHPStan\Reflection\ClassReflection;
/**
 * @api
 */
final class InTraitNode extends Node\Stmt implements \PHPStan\Node\VirtualNode
{
    private Node\Stmt\Trait_ $originalNode;
    private ClassReflection $traitReflection;
    private ClassReflection $implementingClassReflection;
    public function __construct(Node\Stmt\Trait_ $originalNode, ClassReflection $traitReflection, ClassReflection $implementingClassReflection)
    {
        $this->originalNode = $originalNode;
        $this->traitReflection = $traitReflection;
        $this->implementingClassReflection = $implementingClassReflection;
        parent::__construct($originalNode->getAttributes());
    }
    public function getOriginalNode(): Node\Stmt\Trait_
    {
        return $this->originalNode;
    }
    public function getTraitReflection(): ClassReflection
    {
        return $this->traitReflection;
    }
    public function getImplementingClassReflection(): ClassReflection
    {
        return $this->implementingClassReflection;
    }
    public function getType(): string
    {
        return 'PHPStan_Stmt_InTraitNode';
    }
    /**
     * @return string[]
     */
    public function getSubNodeNames(): array
    {
        return [];
    }
}
