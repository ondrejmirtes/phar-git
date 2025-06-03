<?php

declare (strict_types=1);
namespace PHPStan\Node;

use PhpParser\Node;
use PHPStan\Reflection\Php\PhpFunctionFromParserNodeReflection;
/**
 * @api
 */
final class InFunctionNode extends Node\Stmt implements \PHPStan\Node\VirtualNode
{
    private PhpFunctionFromParserNodeReflection $functionReflection;
    private Node\Stmt\Function_ $originalNode;
    public function __construct(PhpFunctionFromParserNodeReflection $functionReflection, Node\Stmt\Function_ $originalNode)
    {
        $this->functionReflection = $functionReflection;
        $this->originalNode = $originalNode;
        parent::__construct($originalNode->getAttributes());
    }
    public function getFunctionReflection() : PhpFunctionFromParserNodeReflection
    {
        return $this->functionReflection;
    }
    public function getOriginalNode() : Node\Stmt\Function_
    {
        return $this->originalNode;
    }
    public function getType() : string
    {
        return 'PHPStan_Stmt_InFunctionNode';
    }
    /**
     * @return string[]
     */
    public function getSubNodeNames() : array
    {
        return [];
    }
}
