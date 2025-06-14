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
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\FunctionTypeSpecifyingExtension;
use PHPStan\Type\MixedType;
use function count;
#[AutowiredService]
final class DefinedConstantTypeSpecifyingExtension implements FunctionTypeSpecifyingExtension, TypeSpecifierAwareExtension
{
    private \PHPStan\Type\Php\ConstantHelper $constantHelper;
    private TypeSpecifier $typeSpecifier;
    public function __construct(\PHPStan\Type\Php\ConstantHelper $constantHelper)
    {
        $this->constantHelper = $constantHelper;
    }
    public function setTypeSpecifier(TypeSpecifier $typeSpecifier): void
    {
        $this->typeSpecifier = $typeSpecifier;
    }
    public function isFunctionSupported(FunctionReflection $functionReflection, FuncCall $node, TypeSpecifierContext $context): bool
    {
        return $functionReflection->getName() === 'defined' && count($node->getArgs()) >= 1 && $context->true();
    }
    public function specifyTypes(FunctionReflection $functionReflection, FuncCall $node, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes
    {
        $constantName = $scope->getType($node->getArgs()[0]->value);
        if (!$constantName instanceof ConstantStringType || $constantName->getValue() === '') {
            return new SpecifiedTypes([], []);
        }
        $expr = $this->constantHelper->createExprFromConstantName($constantName->getValue());
        if ($expr === null) {
            return new SpecifiedTypes([], []);
        }
        return $this->typeSpecifier->create($expr, new MixedType(), $context, $scope);
    }
}
