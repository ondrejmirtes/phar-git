<?php

declare (strict_types=1);
namespace PHPStan\PhpDoc;

use PHPStan\BetterReflection\Reflection\Adapter\ReflectionParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Type\FileTypeMapper;
use function array_map;
use function strtolower;
#[\PHPStan\DependencyInjection\AutowiredService]
final class PhpDocInheritanceResolver
{
    private FileTypeMapper $fileTypeMapper;
    private \PHPStan\PhpDoc\StubPhpDocProvider $stubPhpDocProvider;
    public function __construct(FileTypeMapper $fileTypeMapper, \PHPStan\PhpDoc\StubPhpDocProvider $stubPhpDocProvider)
    {
        $this->fileTypeMapper = $fileTypeMapper;
        $this->stubPhpDocProvider = $stubPhpDocProvider;
    }
    public function resolvePhpDocForProperty(?string $docComment, ClassReflection $classReflection, ?string $classReflectionFileName, ?string $declaringTraitName, string $propertyName) : \PHPStan\PhpDoc\ResolvedPhpDocBlock
    {
        $phpDocBlock = \PHPStan\PhpDoc\PhpDocBlock::resolvePhpDocBlockForProperty($docComment, $classReflection, null, $propertyName, $classReflectionFileName);
        return $this->docBlockTreeToResolvedDocBlock($phpDocBlock, $declaringTraitName, null, $propertyName, null);
    }
    public function resolvePhpDocForConstant(?string $docComment, ClassReflection $classReflection, ?string $classReflectionFileName, string $constantName) : \PHPStan\PhpDoc\ResolvedPhpDocBlock
    {
        $phpDocBlock = \PHPStan\PhpDoc\PhpDocBlock::resolvePhpDocBlockForConstant($docComment, $classReflection, $constantName, $classReflectionFileName);
        return $this->docBlockTreeToResolvedDocBlock($phpDocBlock, null, null, null, $constantName);
    }
    /**
     * @param array<int, string> $positionalParameterNames
     */
    public function resolvePhpDocForMethod(?string $docComment, ?string $fileName, ClassReflection $classReflection, ?string $declaringTraitName, string $methodName, array $positionalParameterNames) : \PHPStan\PhpDoc\ResolvedPhpDocBlock
    {
        $phpDocBlock = \PHPStan\PhpDoc\PhpDocBlock::resolvePhpDocBlockForMethod($docComment, $classReflection, $declaringTraitName, $methodName, $fileName, $positionalParameterNames, $positionalParameterNames);
        return $this->docBlockTreeToResolvedDocBlock($phpDocBlock, $phpDocBlock->getTrait(), $methodName, null, null);
    }
    private function docBlockTreeToResolvedDocBlock(\PHPStan\PhpDoc\PhpDocBlock $phpDocBlock, ?string $traitName, ?string $functionName, ?string $propertyName, ?string $constantName) : \PHPStan\PhpDoc\ResolvedPhpDocBlock
    {
        $parents = [];
        $parentPhpDocBlocks = [];
        foreach ($phpDocBlock->getParents() as $parentPhpDocBlock) {
            if ($functionName !== null && strtolower($functionName) === '__construct' && $parentPhpDocBlock->getClassReflection()->isBuiltin()) {
                continue;
            }
            $parents[] = $this->docBlockTreeToResolvedDocBlock($parentPhpDocBlock, $parentPhpDocBlock->getTrait(), $functionName, $propertyName, $constantName);
            $parentPhpDocBlocks[] = $parentPhpDocBlock;
        }
        $oneResolvedDockBlock = $this->docBlockToResolvedDocBlock($phpDocBlock, $traitName, $functionName, $propertyName, $constantName);
        return $oneResolvedDockBlock->merge($parents, $parentPhpDocBlocks);
    }
    private function docBlockToResolvedDocBlock(\PHPStan\PhpDoc\PhpDocBlock $phpDocBlock, ?string $traitName, ?string $functionName, ?string $propertyName, ?string $constantName) : \PHPStan\PhpDoc\ResolvedPhpDocBlock
    {
        $classReflection = $phpDocBlock->getClassReflection();
        if ($functionName !== null && $classReflection->getNativeReflection()->hasMethod($functionName)) {
            $methodReflection = $classReflection->getNativeReflection()->getMethod($functionName);
            $stub = $this->stubPhpDocProvider->findMethodPhpDoc($classReflection->getName(), $classReflection->getName(), $functionName, array_map(static fn(ReflectionParameter $parameter): string => $parameter->getName(), $methodReflection->getParameters()));
            if ($stub !== null) {
                return $stub;
            }
        }
        if ($propertyName !== null && $classReflection->getNativeReflection()->hasProperty($propertyName)) {
            $stub = $this->stubPhpDocProvider->findPropertyPhpDoc($classReflection->getName(), $propertyName);
            if ($stub === null) {
                $propertyReflection = $classReflection->getNativeReflection()->getProperty($propertyName);
                $propertyDeclaringClass = $propertyReflection->getBetterReflection()->getDeclaringClass();
                if ($propertyDeclaringClass->isTrait() && (!$propertyReflection->getDeclaringClass()->isTrait() || $propertyReflection->getDeclaringClass()->getName() !== $propertyDeclaringClass->getName())) {
                    $stub = $this->stubPhpDocProvider->findPropertyPhpDoc($propertyDeclaringClass->getName(), $propertyName);
                }
            }
            if ($stub !== null) {
                return $stub;
            }
        }
        if ($constantName !== null && $classReflection->getNativeReflection()->hasConstant($constantName)) {
            $stub = $this->stubPhpDocProvider->findClassConstantPhpDoc($classReflection->getName(), $constantName);
            if ($stub !== null) {
                return $stub;
            }
        }
        return $this->fileTypeMapper->getResolvedPhpDoc($phpDocBlock->getFile(), $classReflection->getName(), $traitName, $functionName, $phpDocBlock->getDocComment());
    }
}
