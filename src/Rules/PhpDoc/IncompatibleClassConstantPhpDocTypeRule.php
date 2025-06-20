<?php

declare (strict_types=1);
namespace PHPStan\Rules\PhpDoc;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Internal\SprintfHelper;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\Generics\GenericObjectTypeCheck;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\ParserNodeTypeToPHPStanType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use function array_merge;
use function sprintf;
/**
 * @implements Rule<Node\Stmt\ClassConst>
 */
final class IncompatibleClassConstantPhpDocTypeRule implements Rule
{
    private GenericObjectTypeCheck $genericObjectTypeCheck;
    private \PHPStan\Rules\PhpDoc\UnresolvableTypeHelper $unresolvableTypeHelper;
    public function __construct(GenericObjectTypeCheck $genericObjectTypeCheck, \PHPStan\Rules\PhpDoc\UnresolvableTypeHelper $unresolvableTypeHelper)
    {
        $this->genericObjectTypeCheck = $genericObjectTypeCheck;
        $this->unresolvableTypeHelper = $unresolvableTypeHelper;
    }
    public function getNodeType(): string
    {
        return Node\Stmt\ClassConst::class;
    }
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$scope->isInClass()) {
            throw new ShouldNotHappenException();
        }
        $nativeType = null;
        if ($node->type !== null) {
            $nativeType = ParserNodeTypeToPHPStanType::resolve($node->type, $scope->getClassReflection());
        }
        $errors = [];
        foreach ($node->consts as $const) {
            $constantName = $const->name->toString();
            $errors = array_merge($errors, $this->processSingleConstant($scope->getClassReflection(), $nativeType, $constantName));
        }
        return $errors;
    }
    /**
     * @return list<IdentifierRuleError>
     */
    private function processSingleConstant(ClassReflection $classReflection, ?Type $nativeType, string $constantName): array
    {
        $constantReflection = $classReflection->getConstant($constantName);
        $phpDocType = $constantReflection->getPhpDocType();
        if ($phpDocType === null) {
            return [];
        }
        $errors = [];
        if ($this->unresolvableTypeHelper->containsUnresolvableType($phpDocType)) {
            $errors[] = RuleErrorBuilder::message(sprintf('PHPDoc tag @var for constant %s::%s contains unresolvable type.', $constantReflection->getDeclaringClass()->getName(), $constantName))->identifier('classConstant.unresolvableType')->build();
        } elseif ($nativeType !== null) {
            $isSuperType = $nativeType->isSuperTypeOf($phpDocType);
            if ($isSuperType->no()) {
                $errors[] = RuleErrorBuilder::message(sprintf('PHPDoc tag @var for constant %s::%s with type %s is incompatible with native type %s.', $constantReflection->getDeclaringClass()->getDisplayName(), $constantName, $phpDocType->describe(VerbosityLevel::typeOnly()), $nativeType->describe(VerbosityLevel::typeOnly())))->identifier('classConstant.phpDocType')->build();
            } elseif ($isSuperType->maybe()) {
                $errors[] = RuleErrorBuilder::message(sprintf('PHPDoc tag @var for constant %s::%s with type %s is not subtype of native type %s.', $constantReflection->getDeclaringClass()->getDisplayName(), $constantName, $phpDocType->describe(VerbosityLevel::typeOnly()), $nativeType->describe(VerbosityLevel::typeOnly())))->identifier('classConstant.phpDocType')->build();
            }
        }
        $className = SprintfHelper::escapeFormatString($constantReflection->getDeclaringClass()->getDisplayName());
        $escapedConstantName = SprintfHelper::escapeFormatString($constantName);
        return array_merge($errors, $this->genericObjectTypeCheck->check($phpDocType, sprintf('PHPDoc tag @var for constant %s::%s contains generic type %%s but %%s %%s is not generic.', $className, $escapedConstantName), sprintf('Generic type %%s in PHPDoc tag @var for constant %s::%s does not specify all template types of %%s %%s: %%s', $className, $escapedConstantName), sprintf('Generic type %%s in PHPDoc tag @var for constant %s::%s specifies %%d template types, but %%s %%s supports only %%d: %%s', $className, $escapedConstantName), sprintf('Type %%s in generic type %%s in PHPDoc tag @var for constant %s::%s is not subtype of template type %%s of %%s %%s.', $className, $escapedConstantName), sprintf('Call-site variance of %%s in generic type %%s in PHPDoc tag @var for constant %s::%s is in conflict with %%s template type %%s of %%s %%s.', $className, $escapedConstantName), sprintf('Call-site variance of %%s in generic type %%s in PHPDoc tag @var for constant %s::%s is redundant, template type %%s of %%s %%s has the same variance.', $className, $escapedConstantName)));
    }
}
