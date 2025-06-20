<?php

declare (strict_types=1);
namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierAwareExtension;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\FunctionTypeSpecifyingExtension;
use function count;
use function strtolower;
#[AutowiredService]
final class IsAFunctionTypeSpecifyingExtension implements FunctionTypeSpecifyingExtension, TypeSpecifierAwareExtension
{
    private \PHPStan\Type\Php\IsAFunctionTypeSpecifyingHelper $isAFunctionTypeSpecifyingHelper;
    private TypeSpecifier $typeSpecifier;
    public function __construct(\PHPStan\Type\Php\IsAFunctionTypeSpecifyingHelper $isAFunctionTypeSpecifyingHelper)
    {
        $this->isAFunctionTypeSpecifyingHelper = $isAFunctionTypeSpecifyingHelper;
    }
    public function isFunctionSupported(FunctionReflection $functionReflection, FuncCall $node, TypeSpecifierContext $context): bool
    {
        return strtolower($functionReflection->getName()) === 'is_a' && !$context->null();
    }
    public function specifyTypes(FunctionReflection $functionReflection, FuncCall $node, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes
    {
        if (count($node->getArgs()) < 2) {
            return new SpecifiedTypes();
        }
        $classType = $scope->getType($node->getArgs()[1]->value);
        if (!$classType instanceof ConstantStringType && !$context->true()) {
            return new SpecifiedTypes([], []);
        }
        $objectOrClassType = $scope->getType($node->getArgs()[0]->value);
        $allowStringType = isset($node->getArgs()[2]) ? $scope->getType($node->getArgs()[2]->value) : new ConstantBooleanType(\false);
        $allowString = !$allowStringType->equals(new ConstantBooleanType(\false));
        $resultType = $this->isAFunctionTypeSpecifyingHelper->determineType($objectOrClassType, $classType, $allowString, \true);
        // prevent false-positives in IsAFunctionTypeSpecifyingHelper
        if ($classType->getConstantStrings() === [] && $resultType->isSuperTypeOf($objectOrClassType)->yes()) {
            return new SpecifiedTypes([], []);
        }
        return $this->typeSpecifier->create($node->getArgs()[0]->value, $resultType, $context, $scope);
    }
    public function setTypeSpecifier(TypeSpecifier $typeSpecifier): void
    {
        $this->typeSpecifier = $typeSpecifier;
    }
}
