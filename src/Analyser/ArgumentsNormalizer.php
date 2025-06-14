<?php

declare (strict_types=1);
namespace PHPStan\Analyser;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Node\Expr\TypeExpr;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Constant\ConstantArrayType;
use function array_key_exists;
use function array_keys;
use function count;
use function ksort;
use function max;
use function sprintf;
/**
 * @api
 */
final class ArgumentsNormalizer
{
    public const ORIGINAL_ARG_ATTRIBUTE = 'originalArg';
    /**
     * @return array{ParametersAcceptor, FuncCall, TrinaryLogic}|null
     */
    public static function reorderCallUserFuncArguments(FuncCall $callUserFuncCall, \PHPStan\Analyser\Scope $scope): ?array
    {
        $args = $callUserFuncCall->getArgs();
        if (count($args) < 1) {
            return null;
        }
        $passThruArgs = [];
        $callbackArg = null;
        foreach ($args as $i => $arg) {
            if ($callbackArg === null) {
                if ($arg->name === null && $i === 0) {
                    $callbackArg = $arg;
                    continue;
                }
                if ($arg->name !== null && $arg->name->toString() === 'callback') {
                    $callbackArg = $arg;
                    continue;
                }
            }
            $passThruArgs[] = $arg;
        }
        if ($callbackArg === null) {
            return null;
        }
        $calledOnType = $scope->getType($callbackArg->value);
        if (!$calledOnType->isCallable()->yes()) {
            return null;
        }
        $callableParametersAcceptors = $calledOnType->getCallableParametersAcceptors($scope);
        $parametersAcceptor = ParametersAcceptorSelector::selectFromArgs($scope, $passThruArgs, $callableParametersAcceptors, null);
        $acceptsNamedArguments = TrinaryLogic::createYes();
        foreach ($callableParametersAcceptors as $callableParametersAcceptor) {
            $acceptsNamedArguments = $acceptsNamedArguments->and($callableParametersAcceptor->acceptsNamedArguments());
        }
        return [$parametersAcceptor, new FuncCall($callbackArg->value, $passThruArgs, $callUserFuncCall->getAttributes()), $acceptsNamedArguments];
    }
    public static function reorderFuncArguments(ParametersAcceptor $parametersAcceptor, FuncCall $functionCall): ?FuncCall
    {
        $reorderedArgs = self::reorderArgs($parametersAcceptor, $functionCall->getArgs());
        if ($reorderedArgs === null) {
            return null;
        }
        // return identical object if not reordered, as TypeSpecifier relies on object identity
        if ($reorderedArgs === $functionCall->getArgs()) {
            return $functionCall;
        }
        return new FuncCall($functionCall->name, $reorderedArgs, $functionCall->getAttributes());
    }
    public static function reorderMethodArguments(ParametersAcceptor $parametersAcceptor, MethodCall $methodCall): ?MethodCall
    {
        $reorderedArgs = self::reorderArgs($parametersAcceptor, $methodCall->getArgs());
        if ($reorderedArgs === null) {
            return null;
        }
        // return identical object if not reordered, as TypeSpecifier relies on object identity
        if ($reorderedArgs === $methodCall->getArgs()) {
            return $methodCall;
        }
        return new MethodCall($methodCall->var, $methodCall->name, $reorderedArgs, $methodCall->getAttributes());
    }
    public static function reorderStaticCallArguments(ParametersAcceptor $parametersAcceptor, StaticCall $staticCall): ?StaticCall
    {
        $reorderedArgs = self::reorderArgs($parametersAcceptor, $staticCall->getArgs());
        if ($reorderedArgs === null) {
            return null;
        }
        // return identical object if not reordered, as TypeSpecifier relies on object identity
        if ($reorderedArgs === $staticCall->getArgs()) {
            return $staticCall;
        }
        return new StaticCall($staticCall->class, $staticCall->name, $reorderedArgs, $staticCall->getAttributes());
    }
    public static function reorderNewArguments(ParametersAcceptor $parametersAcceptor, New_ $new): ?New_
    {
        $reorderedArgs = self::reorderArgs($parametersAcceptor, $new->getArgs());
        if ($reorderedArgs === null) {
            return null;
        }
        // return identical object if not reordered, as TypeSpecifier relies on object identity
        if ($reorderedArgs === $new->getArgs()) {
            return $new;
        }
        return new New_($new->class, $reorderedArgs, $new->getAttributes());
    }
    /**
     * @param Arg[] $callArgs
     * @return ?array<int, Arg>
     */
    public static function reorderArgs(ParametersAcceptor $parametersAcceptor, array $callArgs): ?array
    {
        if (count($callArgs) === 0) {
            return [];
        }
        $signatureParameters = $parametersAcceptor->getParameters();
        $hasNamedArgs = \false;
        foreach ($callArgs as $arg) {
            if ($arg->name !== null) {
                $hasNamedArgs = \true;
                break;
            }
        }
        if (!$hasNamedArgs) {
            return $callArgs;
        }
        $hasVariadic = \false;
        $argumentPositions = [];
        foreach ($signatureParameters as $i => $parameter) {
            if ($hasVariadic) {
                // variadic parameter must be last
                return null;
            }
            $hasVariadic = $parameter->isVariadic();
            $argumentPositions[$parameter->getName()] = $i;
        }
        $reorderedArgs = [];
        $additionalNamedArgs = [];
        $appendArgs = [];
        foreach ($callArgs as $i => $arg) {
            if ($arg->name === null) {
                // add regular args as is
                $attributes = $arg->getAttributes();
                $attributes[self::ORIGINAL_ARG_ATTRIBUTE] = $arg;
                $reorderedArgs[$i] = new Arg($arg->value, $arg->byRef, $arg->unpack, $attributes, null);
            } elseif (array_key_exists($arg->name->toString(), $argumentPositions)) {
                $argName = $arg->name->toString();
                // order named args into the position the signature expects them
                $attributes = $arg->getAttributes();
                $attributes[self::ORIGINAL_ARG_ATTRIBUTE] = $arg;
                $reorderedArgs[$argumentPositions[$argName]] = new Arg($arg->value, $arg->byRef, $arg->unpack, $attributes, null);
            } else {
                if (!$hasVariadic) {
                    $attributes = $arg->getAttributes();
                    $attributes[self::ORIGINAL_ARG_ATTRIBUTE] = $arg;
                    $appendArgs[] = new Arg($arg->value, $arg->byRef, $arg->unpack, $attributes, null);
                    continue;
                }
                $attributes = $arg->getAttributes();
                $attributes[self::ORIGINAL_ARG_ATTRIBUTE] = $arg;
                $additionalNamedArgs[] = new Arg($arg->value, $arg->byRef, $arg->unpack, $attributes, null);
            }
        }
        // replace variadic parameter with additional named args, except if it is already set
        $additionalNamedArgsOffset = count($argumentPositions) - 1;
        if (array_key_exists($additionalNamedArgsOffset, $reorderedArgs)) {
            $additionalNamedArgsOffset++;
        }
        foreach ($additionalNamedArgs as $i => $additionalNamedArg) {
            $reorderedArgs[$additionalNamedArgsOffset + $i] = $additionalNamedArg;
        }
        if (count($reorderedArgs) === 0) {
            foreach ($appendArgs as $arg) {
                $reorderedArgs[] = $arg;
            }
            return $reorderedArgs;
        }
        // fill up all holes with default values until the last given argument
        for ($j = 0; $j < max(array_keys($reorderedArgs)); $j++) {
            if (array_key_exists($j, $reorderedArgs)) {
                continue;
            }
            if (!array_key_exists($j, $signatureParameters)) {
                throw new ShouldNotHappenException('Parameter signatures cannot have holes');
            }
            $parameter = $signatureParameters[$j];
            // we can only fill up optional parameters with default values
            if (!$parameter->isOptional()) {
                return null;
            }
            $defaultValue = $parameter->getDefaultValue();
            if ($defaultValue === null) {
                if (!$parameter->isVariadic()) {
                    throw new ShouldNotHappenException(sprintf('An optional parameter $%s must have a default value', $parameter->getName()));
                }
                $defaultValue = new ConstantArrayType([], []);
            }
            $reorderedArgs[$j] = new Arg(new TypeExpr($defaultValue));
        }
        ksort($reorderedArgs);
        foreach ($appendArgs as $arg) {
            $reorderedArgs[] = $arg;
        }
        return $reorderedArgs;
    }
}
