<?php

declare (strict_types=1);
namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\FunctionParameterOutTypeExtension;
use PHPStan\Type\Type;
use function in_array;
use function strtolower;
#[\PHPStan\DependencyInjection\AutowiredService]
final class PregMatchParameterOutTypeExtension implements FunctionParameterOutTypeExtension
{
    private \PHPStan\Type\Php\RegexArrayShapeMatcher $regexShapeMatcher;
    public function __construct(\PHPStan\Type\Php\RegexArrayShapeMatcher $regexShapeMatcher)
    {
        $this->regexShapeMatcher = $regexShapeMatcher;
    }
    public function isFunctionSupported(FunctionReflection $functionReflection, ParameterReflection $parameter) : bool
    {
        return in_array(strtolower($functionReflection->getName()), ['preg_match', 'preg_match_all'], \true) && in_array($parameter->getName(), ['subpatterns', 'matches'], \true);
    }
    public function getParameterOutTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $funcCall, ParameterReflection $parameter, Scope $scope) : ?Type
    {
        $args = $funcCall->getArgs();
        $patternArg = $args[0] ?? null;
        $matchesArg = $args[2] ?? null;
        $flagsArg = $args[3] ?? null;
        if ($patternArg === null || $matchesArg === null) {
            return null;
        }
        $flagsType = null;
        if ($flagsArg !== null) {
            $flagsType = $scope->getType($flagsArg->value);
        }
        if ($functionReflection->getName() === 'preg_match') {
            return $this->regexShapeMatcher->matchExpr($patternArg->value, $flagsType, TrinaryLogic::createMaybe(), $scope);
        }
        return $this->regexShapeMatcher->matchAllExpr($patternArg->value, $flagsType, TrinaryLogic::createMaybe(), $scope);
    }
}
