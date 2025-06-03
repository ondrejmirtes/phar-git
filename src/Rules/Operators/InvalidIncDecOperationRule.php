<?php

declare (strict_types=1);
namespace PHPStan\Rules\Operators;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\BooleanType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use function get_class;
use function sprintf;
/**
 * @implements Rule<Node\Expr>
 */
final class InvalidIncDecOperationRule implements Rule
{
    private RuleLevelHelper $ruleLevelHelper;
    public function __construct(RuleLevelHelper $ruleLevelHelper)
    {
        $this->ruleLevelHelper = $ruleLevelHelper;
    }
    public function getNodeType(): string
    {
        return Node\Expr::class;
    }
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node instanceof Node\Expr\PreInc && !$node instanceof Node\Expr\PostInc && !$node instanceof Node\Expr\PreDec && !$node instanceof Node\Expr\PostDec) {
            return [];
        }
        switch (get_class($node)) {
            case Node\Expr\PreInc::class:
                $nodeType = 'preInc';
                break;
            case Node\Expr\PostInc::class:
                $nodeType = 'postInc';
                break;
            case Node\Expr\PreDec::class:
                $nodeType = 'preDec';
                break;
            case Node\Expr\PostDec::class:
                $nodeType = 'postDec';
                break;
            default:
                throw new ShouldNotHappenException();
        }
        $operatorString = $node instanceof Node\Expr\PreInc || $node instanceof Node\Expr\PostInc ? '++' : '--';
        if (!$node->var instanceof Node\Expr\Variable && !$node->var instanceof Node\Expr\ArrayDimFetch && !$node->var instanceof Node\Expr\PropertyFetch && !$node->var instanceof Node\Expr\StaticPropertyFetch) {
            return [RuleErrorBuilder::message(sprintf('Cannot use %s on a non-variable.', $operatorString))->line($node->var->getStartLine())->identifier(sprintf('%s.expr', $nodeType))->build()];
        }
        $allowedTypes = new UnionType([new BooleanType(), new FloatType(), new IntegerType(), new StringType(), new NullType(), new ObjectType('SimpleXMLElement')]);
        $varType = $this->ruleLevelHelper->findTypeToCheck($scope, $node->var, '', static fn(Type $type): bool => $allowedTypes->isSuperTypeOf($type)->yes())->getType();
        if ($varType instanceof ErrorType || $allowedTypes->isSuperTypeOf($varType)->yes()) {
            return [];
        }
        return [RuleErrorBuilder::message(sprintf('Cannot use %s on %s.', $operatorString, $varType->describe(VerbosityLevel::value())))->line($node->var->getStartLine())->identifier(sprintf('%s.type', $nodeType))->build()];
    }
}
