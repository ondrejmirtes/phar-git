<?php

declare (strict_types=1);
namespace PHPStan\Rules\RestrictedUsage;

/**
 * @api
 */
final class RestrictedUsage
{
    /**
     * @readonly
     */
    public string $errorMessage;
    /**
     * @readonly
     */
    public string $identifier;
    private function __construct(string $errorMessage, string $identifier)
    {
        $this->errorMessage = $errorMessage;
        $this->identifier = $identifier;
    }
    public static function create(string $errorMessage, string $identifier): self
    {
        return new self($errorMessage, $identifier);
    }
}
