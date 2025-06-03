<?php

declare (strict_types=1);
namespace PHPStan\Rules;

use PhpParser\Node\Expr;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\CallableType;
use PHPStan\Type\ClosureType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Generic\TemplateMixedType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\NullType;
use PHPStan\Type\StrictMixedType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\UnionType;
use function count;
use function sprintf;
final class RuleLevelHelper
{
    private ReflectionProvider $reflectionProvider;
    private bool $checkNullables;
    private bool $checkThisOnly;
    private bool $checkUnionTypes;
    private bool $checkExplicitMixed;
    private bool $checkImplicitMixed;
    private bool $checkBenevolentUnionTypes;
    private bool $discoveringSymbolsTip;
    public function __construct(ReflectionProvider $reflectionProvider, bool $checkNullables, bool $checkThisOnly, bool $checkUnionTypes, bool $checkExplicitMixed, bool $checkImplicitMixed, bool $checkBenevolentUnionTypes, bool $discoveringSymbolsTip)
    {
        $this->reflectionProvider = $reflectionProvider;
        $this->checkNullables = $checkNullables;
        $this->checkThisOnly = $checkThisOnly;
        $this->checkUnionTypes = $checkUnionTypes;
        $this->checkExplicitMixed = $checkExplicitMixed;
        $this->checkImplicitMixed = $checkImplicitMixed;
        $this->checkBenevolentUnionTypes = $checkBenevolentUnionTypes;
        $this->discoveringSymbolsTip = $discoveringSymbolsTip;
    }
    /** @api */
    public function isThis(Expr $expression): bool
    {
        return $expression instanceof Expr\Variable && $expression->name === 'this';
    }
    private function transformCommonType(Type $type): Type
    {
        if (!$this->checkExplicitMixed && !$this->checkImplicitMixed) {
            return $type;
        }
        return TypeTraverser::map($type, function (Type $type, callable $traverse) {
            if ($type instanceof TemplateMixedType) {
                if ($this->checkExplicitMixed) {
                    return $type->toStrictMixedType();
                }
            }
            if ($type instanceof MixedType && ($type->isExplicitMixed() && $this->checkExplicitMixed || !$type->isExplicitMixed() && $this->checkImplicitMixed)) {
                return new StrictMixedType();
            }
            return $traverse($type);
        });
    }
    /**
     * @return array{Type, bool}
     */
    private function transformAcceptedType(Type $acceptingType, Type $acceptedType): array
    {
        $checkForUnion = $this->checkUnionTypes;
        $acceptedType = TypeTraverser::map($acceptedType, function (Type $acceptedType, callable $traverse) use ($acceptingType, &$checkForUnion): Type {
            if ($acceptedType instanceof CallableType) {
                if ($acceptedType->isCommonCallable()) {
                    return $acceptedType;
                }
                return new CallableType($acceptedType->getParameters(), $traverse($this->transformCommonType($acceptedType->getReturnType())), $acceptedType->isVariadic(), $acceptedType->getTemplateTypeMap(), $acceptedType->getResolvedTemplateTypeMap(), $acceptedType->getTemplateTags(), $acceptedType->isPure());
            }
            if ($acceptedType instanceof ClosureType) {
                if ($acceptedType->isCommonCallable()) {
                    return $acceptedType;
                }
                return new ClosureType($acceptedType->getParameters(), $traverse($this->transformCommonType($acceptedType->getReturnType())), $acceptedType->isVariadic(), $acceptedType->getTemplateTypeMap(), $acceptedType->getResolvedTemplateTypeMap(), $acceptedType->getCallSiteVarianceMap(), $acceptedType->getTemplateTags(), $acceptedType->getThrowPoints(), $acceptedType->getImpurePoints(), $acceptedType->getInvalidateExpressions(), $acceptedType->getUsedVariables(), $acceptedType->acceptsNamedArguments());
            }
            if (!$this->checkNullables && !$acceptingType instanceof NullType && !$acceptedType instanceof NullType && !$acceptedType instanceof BenevolentUnionType) {
                return $traverse(TypeCombinator::removeNull($acceptedType));
            }
            if ($this->checkBenevolentUnionTypes) {
                if ($acceptedType instanceof BenevolentUnionType) {
                    $checkForUnion = \true;
                    return $traverse(TypeUtils::toStrictUnion($acceptedType));
                }
            }
            return $traverse($this->transformCommonType($acceptedType));
        });
        return [$acceptedType, $checkForUnion];
    }
    /** @api */
    public function accepts(Type $acceptingType, Type $acceptedType, bool $strictTypes): \PHPStan\Rules\RuleLevelHelperAcceptsResult
    {
        [$acceptedType, $checkForUnion] = $this->transformAcceptedType($acceptingType, $acceptedType);
        $acceptingType = $this->transformCommonType($acceptingType);
        $accepts = $acceptingType->accepts($acceptedType, $strictTypes);
        return new \PHPStan\Rules\RuleLevelHelperAcceptsResult($checkForUnion ? $accepts->yes() : !$accepts->no(), $accepts->reasons);
    }
    /**
     * @api
     * @param callable(Type $type): bool $unionTypeCriteriaCallback
     */
    public function findTypeToCheck(Scope $scope, Expr $var, string $unknownClassErrorPattern, callable $unionTypeCriteriaCallback): \PHPStan\Rules\FoundTypeResult
    {
        if ($this->checkThisOnly && !$this->isThis($var)) {
            return new \PHPStan\Rules\FoundTypeResult(new ErrorType(), [], [], null);
        }
        $type = $scope->getType($var);
        return $this->findTypeToCheckImplementation($scope, $var, $type, $unknownClassErrorPattern, $unionTypeCriteriaCallback, \true);
    }
    /** @param callable(Type $type): bool $unionTypeCriteriaCallback */
    private function findTypeToCheckImplementation(Scope $scope, Expr $var, Type $type, string $unknownClassErrorPattern, callable $unionTypeCriteriaCallback, bool $isTopLevel = \false): \PHPStan\Rules\FoundTypeResult
    {
        if (!$this->checkNullables && !$type->isNull()->yes()) {
            $type = TypeCombinator::removeNull($type);
        }
        if (($this->checkExplicitMixed || $this->checkImplicitMixed) && $type instanceof MixedType && ($type->isExplicitMixed() ? $this->checkExplicitMixed : $this->checkImplicitMixed)) {
            return new \PHPStan\Rules\FoundTypeResult($type instanceof TemplateMixedType ? $type->toStrictMixedType() : new StrictMixedType(), [], [], null);
        }
        if ($type instanceof MixedType || $type instanceof NeverType) {
            return new \PHPStan\Rules\FoundTypeResult(new ErrorType(), [], [], null);
        }
        $errors = [];
        $hasClassExistsClass = \false;
        $directClassNames = [];
        if ($isTopLevel) {
            $directClassNames = $type->getObjectClassNames();
            foreach ($directClassNames as $referencedClass) {
                if ($this->reflectionProvider->hasClass($referencedClass)) {
                    $classReflection = $this->reflectionProvider->getClass($referencedClass);
                    if (!$classReflection->isTrait()) {
                        continue;
                    }
                }
                if ($scope->isInClassExists($referencedClass)) {
                    $hasClassExistsClass = \true;
                    continue;
                }
                $errorBuilder = \PHPStan\Rules\RuleErrorBuilder::message(sprintf($unknownClassErrorPattern, $referencedClass))->line($var->getStartLine())->identifier('class.notFound');
                if ($this->discoveringSymbolsTip) {
                    $errorBuilder->discoveringSymbolsTip();
                }
                $errors[] = $errorBuilder->build();
            }
        }
        if (count($errors) > 0 || $hasClassExistsClass) {
            return new \PHPStan\Rules\FoundTypeResult(new ErrorType(), [], $errors, null);
        }
        if (!$this->checkUnionTypes && $type->isObject()->yes() && count($type->getObjectClassNames()) === 0) {
            return new \PHPStan\Rules\FoundTypeResult(new ErrorType(), [], [], null);
        }
        if ($type instanceof UnionType) {
            $shouldFilterUnion = !$this->checkUnionTypes && !$type instanceof BenevolentUnionType || !$this->checkBenevolentUnionTypes && $type instanceof BenevolentUnionType;
            $newTypes = [];
            foreach ($type->getTypes() as $innerType) {
                if ($shouldFilterUnion && !$unionTypeCriteriaCallback($innerType)) {
                    continue;
                }
                $newTypes[] = $this->findTypeToCheckImplementation($scope, $var, $innerType, $unknownClassErrorPattern, $unionTypeCriteriaCallback)->getType();
            }
            if (count($newTypes) > 0) {
                $newUnion = TypeCombinator::union(...$newTypes);
                if (!$this->checkBenevolentUnionTypes && $type instanceof BenevolentUnionType) {
                    $newUnion = TypeUtils::toBenevolentUnion($newUnion);
                }
                return new \PHPStan\Rules\FoundTypeResult($newUnion, $directClassNames, [], null);
            }
        }
        if ($type instanceof IntersectionType) {
            $newTypes = [];
            $changed = \false;
            foreach ($type->getTypes() as $innerType) {
                if ($innerType instanceof TemplateMixedType) {
                    $changed = \true;
                    $newTypes[] = $this->findTypeToCheckImplementation($scope, $var, $innerType->toStrictMixedType(), $unknownClassErrorPattern, $unionTypeCriteriaCallback)->getType();
                    continue;
                }
                $newTypes[] = $innerType;
            }
            if ($changed) {
                return new \PHPStan\Rules\FoundTypeResult(TypeCombinator::intersect(...$newTypes), $directClassNames, [], null);
            }
        }
        $tip = null;
        if ($type instanceof UnionType && count($type->getTypes()) === 2 && $type->isObject()->yes() && $type->getTypes()[0]->getObjectClassNames() === ['PhpParser\Node\Arg'] && $type->getTypes()[1]->getObjectClassNames() === ['PhpParser\Node\VariadicPlaceholder'] && !$unionTypeCriteriaCallback($type)) {
            $tip = 'Use <fg=cyan>->getArgs()</> instead of <fg=cyan>->args</>.';
        }
        return new \PHPStan\Rules\FoundTypeResult($type, $directClassNames, [], $tip);
    }
}
