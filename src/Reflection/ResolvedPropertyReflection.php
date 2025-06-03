<?php

declare (strict_types=1);
namespace PHPStan\Reflection;

use PHPStan\TrinaryLogic;
use PHPStan\Type\Generic\TemplateTypeHelper;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Generic\TemplateTypeVarianceMap;
use PHPStan\Type\Type;
final class ResolvedPropertyReflection implements \PHPStan\Reflection\WrapperPropertyReflection
{
    private \PHPStan\Reflection\ExtendedPropertyReflection $reflection;
    private TemplateTypeMap $templateTypeMap;
    private TemplateTypeVarianceMap $callSiteVarianceMap;
    private ?Type $readableType = null;
    private ?Type $writableType = null;
    public function __construct(\PHPStan\Reflection\ExtendedPropertyReflection $reflection, TemplateTypeMap $templateTypeMap, TemplateTypeVarianceMap $callSiteVarianceMap)
    {
        $this->reflection = $reflection;
        $this->templateTypeMap = $templateTypeMap;
        $this->callSiteVarianceMap = $callSiteVarianceMap;
    }
    public function getName(): string
    {
        return $this->reflection->getName();
    }
    public function getOriginalReflection(): \PHPStan\Reflection\ExtendedPropertyReflection
    {
        return $this->reflection;
    }
    public function getDeclaringClass(): \PHPStan\Reflection\ClassReflection
    {
        return $this->reflection->getDeclaringClass();
    }
    public function isStatic(): bool
    {
        return $this->reflection->isStatic();
    }
    public function isPrivate(): bool
    {
        return $this->reflection->isPrivate();
    }
    public function isPublic(): bool
    {
        return $this->reflection->isPublic();
    }
    public function hasPhpDocType(): bool
    {
        return $this->reflection->hasPhpDocType();
    }
    public function getPhpDocType(): Type
    {
        return $this->reflection->getPhpDocType();
    }
    public function hasNativeType(): bool
    {
        return $this->reflection->hasNativeType();
    }
    public function getNativeType(): Type
    {
        return $this->reflection->getNativeType();
    }
    public function getReadableType(): Type
    {
        $type = $this->readableType;
        if ($type !== null) {
            return $type;
        }
        $type = TemplateTypeHelper::resolveTemplateTypes($this->reflection->getReadableType(), $this->templateTypeMap, $this->callSiteVarianceMap, TemplateTypeVariance::createCovariant());
        $type = TemplateTypeHelper::resolveTemplateTypes($type, $this->templateTypeMap, $this->callSiteVarianceMap, TemplateTypeVariance::createCovariant());
        $this->readableType = $type;
        return $type;
    }
    public function getWritableType(): Type
    {
        $type = $this->writableType;
        if ($type !== null) {
            return $type;
        }
        $type = TemplateTypeHelper::resolveTemplateTypes($this->reflection->getWritableType(), $this->templateTypeMap, $this->callSiteVarianceMap, TemplateTypeVariance::createContravariant());
        $type = TemplateTypeHelper::resolveTemplateTypes($type, $this->templateTypeMap, $this->callSiteVarianceMap, TemplateTypeVariance::createContravariant());
        $this->writableType = $type;
        return $type;
    }
    public function canChangeTypeAfterAssignment(): bool
    {
        return $this->reflection->canChangeTypeAfterAssignment();
    }
    public function isReadable(): bool
    {
        return $this->reflection->isReadable();
    }
    public function isWritable(): bool
    {
        return $this->reflection->isWritable();
    }
    public function getDocComment(): ?string
    {
        return $this->reflection->getDocComment();
    }
    public function isDeprecated(): TrinaryLogic
    {
        return $this->reflection->isDeprecated();
    }
    public function getDeprecatedDescription(): ?string
    {
        return $this->reflection->getDeprecatedDescription();
    }
    public function isInternal(): TrinaryLogic
    {
        return $this->reflection->isInternal();
    }
    public function isAbstract(): TrinaryLogic
    {
        return $this->reflection->isAbstract();
    }
    public function isFinalByKeyword(): TrinaryLogic
    {
        return $this->reflection->isFinalByKeyword();
    }
    public function isFinal(): TrinaryLogic
    {
        return $this->reflection->isFinal();
    }
    public function isVirtual(): TrinaryLogic
    {
        return $this->reflection->isVirtual();
    }
    public function hasHook(string $hookType): bool
    {
        return $this->reflection->hasHook($hookType);
    }
    public function getHook(string $hookType): \PHPStan\Reflection\ExtendedMethodReflection
    {
        return new \PHPStan\Reflection\ResolvedMethodReflection($this->reflection->getHook($hookType), $this->templateTypeMap, $this->callSiteVarianceMap);
    }
    public function isProtectedSet(): bool
    {
        return $this->reflection->isProtectedSet();
    }
    public function isPrivateSet(): bool
    {
        return $this->reflection->isPrivateSet();
    }
    public function getAttributes(): array
    {
        return $this->reflection->getAttributes();
    }
}
