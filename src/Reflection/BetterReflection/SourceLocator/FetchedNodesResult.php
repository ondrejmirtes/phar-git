<?php

declare (strict_types=1);
namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use PhpParser\Node;
final class FetchedNodesResult
{
    /**
     * @var array<string, array<FetchedNode<Node\Stmt\ClassLike>>>
     */
    private array $classNodes;
    /**
     * @var array<string, array<FetchedNode<Node\Stmt\Function_>>>
     */
    private array $functionNodes;
    /**
     * @var array<string, array<FetchedNode<Node\Stmt\Const_|Node\Expr\FuncCall>>>
     */
    private array $constantNodes;
    /**
     * @param array<string, array<FetchedNode<Node\Stmt\ClassLike>>> $classNodes
     * @param array<string, array<FetchedNode<Node\Stmt\Function_>>> $functionNodes
     * @param array<string, array<FetchedNode<Node\Stmt\Const_|Node\Expr\FuncCall>>> $constantNodes
     */
    public function __construct(array $classNodes, array $functionNodes, array $constantNodes)
    {
        $this->classNodes = $classNodes;
        $this->functionNodes = $functionNodes;
        $this->constantNodes = $constantNodes;
    }
    /**
     * @return array<string, array<FetchedNode<Node\Stmt\ClassLike>>>
     */
    public function getClassNodes(): array
    {
        return $this->classNodes;
    }
    /**
     * @return array<string, array<FetchedNode<Node\Stmt\Function_>>>
     */
    public function getFunctionNodes(): array
    {
        return $this->functionNodes;
    }
    /**
     * @return array<string, array<FetchedNode<Node\Stmt\Const_|Node\Expr\FuncCall>>>
     */
    public function getConstantNodes(): array
    {
        return $this->constantNodes;
    }
}
