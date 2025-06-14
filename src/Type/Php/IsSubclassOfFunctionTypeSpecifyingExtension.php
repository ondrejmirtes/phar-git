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
use PHPStan\Type\FunctionTypeSpecifyingExtension;
use PHPStan\Type\Generic\GenericClassStringType;
use function count;
use function strtolower;
#[AutowiredService]
final class IsSubclassOfFunctionTypeSpecifyingExtension implements FunctionTypeSpecifyingExtension, TypeSpecifierAwareExtension
{
    private \PHPStan\Type\Php\IsAFunctionTypeSpecifyingHelper $isAFunctionTypeSpecifyingHelper;
    private TypeSpecifier $typeSpecifier;
    public function __construct(\PHPStan\Type\Php\IsAFunctionTypeSpecifyingHelper $isAFunctionTypeSpecifyingHelper)
    {
        $this->isAFunctionTypeSpecifyingHelper = $isAFunctionTypeSpecifyingHelper;
    }
    public function isFunctionSupported(FunctionReflection $functionReflection, FuncCall $node, TypeSpecifierContext $context): bool
    {
        return strtolower($functionReflection->getName()) === 'is_subclass_of' && !$context->null();
    }
    public function specifyTypes(FunctionReflection $functionReflection, FuncCall $node, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes
    {
        if (!$context->true() || count($node->getArgs()) < 2) {
            return new SpecifiedTypes();
        }
        $objectOrClassType = $scope->getType($node->getArgs()[0]->value);
        $classType = $scope->getType($node->getArgs()[1]->value);
        $allowStringType = isset($node->getArgs()[2]) ? $scope->getType($node->getArgs()[2]->value) : new ConstantBooleanType(\true);
        $allowString = !$allowStringType->equals(new ConstantBooleanType(\false));
        // prevent false-positives in IsAFunctionTypeSpecifyingHelper
        if ($objectOrClassType instanceof GenericClassStringType && $classType instanceof GenericClassStringType) {
            return new SpecifiedTypes([], []);
        }
        $resultType = $this->isAFunctionTypeSpecifyingHelper->determineType($objectOrClassType, $classType, $allowString, \false);
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
