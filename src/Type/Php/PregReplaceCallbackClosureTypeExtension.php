<?php

declare (strict_types=1);
namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\Native\NativeParameterReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\ClosureType;
use PHPStan\Type\FunctionParameterClosureTypeExtension;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
#[AutowiredService]
final class PregReplaceCallbackClosureTypeExtension implements FunctionParameterClosureTypeExtension
{
    private \PHPStan\Type\Php\RegexArrayShapeMatcher $regexShapeMatcher;
    public function __construct(\PHPStan\Type\Php\RegexArrayShapeMatcher $regexShapeMatcher)
    {
        $this->regexShapeMatcher = $regexShapeMatcher;
    }
    public function isFunctionSupported(FunctionReflection $functionReflection, ParameterReflection $parameter): bool
    {
        return $functionReflection->getName() === 'preg_replace_callback' && $parameter->getName() === 'callback';
    }
    public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, ParameterReflection $parameter, Scope $scope): ?Type
    {
        $args = $functionCall->getArgs();
        $patternArg = $args[0] ?? null;
        $flagsArg = $args[5] ?? null;
        if ($patternArg === null) {
            return null;
        }
        $flagsType = null;
        if ($flagsArg !== null) {
            $flagsType = $scope->getType($flagsArg->value);
        }
        $matchesType = $this->regexShapeMatcher->matchExpr($patternArg->value, $flagsType, TrinaryLogic::createYes(), $scope);
        if ($matchesType === null) {
            return null;
        }
        return new ClosureType([new NativeParameterReflection($parameter->getName(), $parameter->isOptional(), $matchesType, $parameter->passedByReference(), $parameter->isVariadic(), $parameter->getDefaultValue())], new StringType());
    }
}
