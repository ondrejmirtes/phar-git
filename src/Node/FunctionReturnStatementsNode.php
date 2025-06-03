<?php

declare (strict_types=1);
namespace PHPStan\Node;

use PhpParser\Node\Expr\Yield_;
use PhpParser\Node\Expr\YieldFrom;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Function_;
use PhpParser\NodeAbstract;
use PHPStan\Analyser\ImpurePoint;
use PHPStan\Analyser\StatementResult;
use PHPStan\Reflection\Php\PhpFunctionFromParserNodeReflection;
use function count;
/**
 * @api
 */
final class FunctionReturnStatementsNode extends NodeAbstract implements \PHPStan\Node\ReturnStatementsNode
{
    private Function_ $function;
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
    private PhpFunctionFromParserNodeReflection $functionReflection;
    /**
     * @param list<ReturnStatement> $returnStatements
     * @param list<Yield_|YieldFrom> $yieldStatements
     * @param list<ExecutionEndNode> $executionEnds
     * @param ImpurePoint[] $impurePoints
     */
    public function __construct(Function_ $function, array $returnStatements, array $yieldStatements, StatementResult $statementResult, array $executionEnds, array $impurePoints, PhpFunctionFromParserNodeReflection $functionReflection)
    {
        $this->function = $function;
        $this->returnStatements = $returnStatements;
        $this->yieldStatements = $yieldStatements;
        $this->statementResult = $statementResult;
        $this->executionEnds = $executionEnds;
        $this->impurePoints = $impurePoints;
        $this->functionReflection = $functionReflection;
        parent::__construct($function->getAttributes());
    }
    public function getReturnStatements() : array
    {
        return $this->returnStatements;
    }
    public function getStatementResult() : StatementResult
    {
        return $this->statementResult;
    }
    public function getExecutionEnds() : array
    {
        return $this->executionEnds;
    }
    public function getImpurePoints() : array
    {
        return $this->impurePoints;
    }
    public function returnsByRef() : bool
    {
        return $this->function->byRef;
    }
    public function hasNativeReturnTypehint() : bool
    {
        return $this->function->returnType !== null;
    }
    public function getYieldStatements() : array
    {
        return $this->yieldStatements;
    }
    public function isGenerator() : bool
    {
        return count($this->yieldStatements) > 0;
    }
    public function getType() : string
    {
        return 'PHPStan_Node_FunctionReturnStatementsNode';
    }
    /**
     * @return string[]
     */
    public function getSubNodeNames() : array
    {
        return [];
    }
    public function getFunctionReflection() : PhpFunctionFromParserNodeReflection
    {
        return $this->functionReflection;
    }
    /**
     * @return Stmt[]
     */
    public function getStatements() : array
    {
        return $this->function->getStmts();
    }
}
