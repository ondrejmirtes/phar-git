<?php

declare (strict_types=1);
namespace PHPStan\Type\Generic;

use function sprintf;
final class TemplateTypeScope
{
    private ?string $className;
    private ?string $functionName;
    public static function createWithAnonymousFunction(): self
    {
        return new self(null, null);
    }
    public static function createWithFunction(string $functionName): self
    {
        return new self(null, $functionName);
    }
    public static function createWithMethod(string $className, string $functionName): self
    {
        return new self($className, $functionName);
    }
    public static function createWithClass(string $className): self
    {
        return new self($className, null);
    }
    private function __construct(?string $className, ?string $functionName)
    {
        $this->className = $className;
        $this->functionName = $functionName;
    }
    /** @api */
    public function getClassName(): ?string
    {
        return $this->className;
    }
    /** @api */
    public function getFunctionName(): ?string
    {
        return $this->functionName;
    }
    /** @api */
    public function equals(self $other): bool
    {
        return $this->className === $other->className && $this->functionName === $other->functionName;
    }
    /** @api */
    public function describe(): string
    {
        if ($this->className === null && $this->functionName === null) {
            return 'anonymous function';
        }
        if ($this->className === null) {
            return sprintf('function %s()', $this->functionName);
        }
        if ($this->functionName === null) {
            return sprintf('class %s', $this->className);
        }
        return sprintf('method %s::%s()', $this->className, $this->functionName);
    }
}
