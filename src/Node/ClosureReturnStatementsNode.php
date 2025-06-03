<?php

declare (strict_types=1);
namespace PHPStan\Node;

use PhpParser\Node;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\Yield_;
use PhpParser\Node\Expr\YieldFrom;
use PhpParser\NodeAbstract;
use PHPStan\Analyser\ImpurePoint;
use PHPStan\Analyser\StatementResult;
use function count;
/**
 * @api
 */
final class ClosureReturnStatementsNode extends NodeAbstract implements \PHPStan\Node\ReturnStatementsNode
{
    /**
     * @var list<ReturnStatement>
     */
    private array $returnStatements;
    /**
     * @var list<Yield_|YieldFrom>
     */
    private array $yieldStatements;
    private StatementResult $statementResult;
    /**
     * @var list<ExecutionEndNode>
     */
    private array $executionEnds;
    /**
     * @var ImpurePoint[]
     */
    private array $impurePoints;
    private Node\Expr\Closure $closureExpr;
    /**
     * @param list<ReturnStatement> $returnStatements
     * @param list<Yield_|YieldFrom> $yieldStatements
     * @param list<ExecutionEndNode> $executionEnds
     * @param ImpurePoint[] $impurePoints
     */
    public function __construct(Closure $closureExpr, array $returnStatements, array $yieldStatements, StatementResult $statementResult, array $executionEnds, array $impurePoints)
    {
        $this->returnStatements = $returnStatements;
        $this->yieldStatements = $yieldStatements;
        $this->statementResult = $statementResult;
        $this->executionEnds = $executionEnds;
        $this->impurePoints = $impurePoints;
        parent::__construct($closureExpr->getAttributes());
        $this->closureExpr = $closureExpr;
    }
    public function getClosureExpr() : Closure
    {
        return $this->closureExpr;
    }
    public function hasNativeReturnTypehint() : bool
    {
        return $this->closureExpr->returnType !== null;
    }
    public function getReturnStatements() : array
    {
        return $this->returnStatements;
    }
    public function getExecutionEnds() : array
    {
        return $this->executionEnds;
    }
    public function getImpurePoints() : array
    {
        return $this->impurePoints;
    }
    public function getYieldStatements() : array
    {
        return $this->yieldStatements;
    }
    public function isGenerator() : bool
    {
        return count($this->yieldStatements) > 0;
    }
    public function getStatementResult() : StatementResult
    {
        return $this->statementResult;
    }
    public function returnsByRef() : bool
    {
        return $this->closureExpr->byRef;
    }
    public function getType() : string
    {
        return 'PHPStan_Node_ClosureReturnStatementsNode';
    }
    /**
     * @return string[]
     */
    public function getSubNodeNames() : array
    {
        return [];
    }
}
