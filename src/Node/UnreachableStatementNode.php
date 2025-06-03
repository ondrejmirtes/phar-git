<?php

declare (strict_types=1);
namespace PHPStan\Node;

use PhpParser\Node\Stmt;
/**
 * @api
 */
final class UnreachableStatementNode extends Stmt implements \PHPStan\Node\VirtualNode
{
    private Stmt $originalStatement;
    /**
     * @var Stmt[]
     */
    private array $nextStatements;
    /** @param Stmt[] $nextStatements */
    public function __construct(Stmt $originalStatement, array $nextStatements = [])
    {
        $this->originalStatement = $originalStatement;
        $this->nextStatements = $nextStatements;
        parent::__construct($originalStatement->getAttributes());
    }
    public function getOriginalStatement() : Stmt
    {
        return $this->originalStatement;
    }
    public function getType() : string
    {
        return 'PHPStan_Stmt_UnreachableStatementNode';
    }
    /**
     * @return string[]
     */
    public function getSubNodeNames() : array
    {
        return [];
    }
    /**
     * @return Stmt[]
     */
    public function getNextStatements() : array
    {
        return $this->nextStatements;
    }
}
