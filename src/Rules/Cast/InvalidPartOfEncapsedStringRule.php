<?php

declare (strict_types=1);
namespace PHPStan\Rules\Cast;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use function sprintf;
/**
 * @implements Rule<Node\Scalar\InterpolatedString>
 */
final class InvalidPartOfEncapsedStringRule implements Rule
{
    private ExprPrinter $exprPrinter;
    private RuleLevelHelper $ruleLevelHelper;
    public function __construct(ExprPrinter $exprPrinter, RuleLevelHelper $ruleLevelHelper)
    {
        $this->exprPrinter = $exprPrinter;
        $this->ruleLevelHelper = $ruleLevelHelper;
    }
    public function getNodeType(): string
    {
        return Node\Scalar\InterpolatedString::class;
    }
    public function processNode(Node $node, Scope $scope): array
    {
        $messages = [];
        foreach ($node->parts as $part) {
            if ($part instanceof Node\InterpolatedStringPart) {
                continue;
            }
            $typeResult = $this->ruleLevelHelper->findTypeToCheck($scope, $part, '', static fn(Type $type): bool => !$type->toString() instanceof ErrorType);
            $partType = $typeResult->getType();
            if ($partType instanceof ErrorType) {
                continue;
            }
            $stringPartType = $partType->toString();
            if (!$stringPartType instanceof ErrorType) {
                continue;
            }
            $messages[] = RuleErrorBuilder::message(sprintf('Part %s (%s) of encapsed string cannot be cast to string.', $this->exprPrinter->printExpr($part), $partType->describe(VerbosityLevel::value())))->identifier('encapsedStringPart.nonString')->line($part->getStartLine())->build();
        }
        return $messages;
    }
}
