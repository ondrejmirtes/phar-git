<?php

declare (strict_types=1);
namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\IntegerType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use SplFileObject;
use function in_array;
#[AutowiredService]
final class StatDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension, DynamicMethodReturnTypeExtension
{
    public function isFunctionSupported(FunctionReflection $functionReflection): bool
    {
        return in_array($functionReflection->getName(), ['stat', 'lstat', 'fstat', 'ssh2_sftp_stat'], \true);
    }
    public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): Type
    {
        return TypeCombinator::union($this->getReturnType(), new ConstantBooleanType(\false));
    }
    public function getClass(): string
    {
        return SplFileObject::class;
    }
    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'fstat';
    }
    public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): Type
    {
        return $this->getReturnType();
    }
    private function getReturnType(): Type
    {
        $valueType = new IntegerType();
        $builder = ConstantArrayTypeBuilder::createEmpty();
        $keys = ['dev', 'ino', 'mode', 'nlink', 'uid', 'gid', 'rdev', 'size', 'atime', 'mtime', 'ctime', 'blksize', 'blocks'];
        foreach ($keys as $key) {
            $builder->setOffsetValueType(null, $valueType);
        }
        foreach ($keys as $key) {
            $builder->setOffsetValueType(new ConstantStringType($key), $valueType);
        }
        return $builder->getArray();
    }
}
