<?php

declare (strict_types=1);
namespace PHPStan\Type;

/**
 * @template-covariant T of Type
 */
final class TypeResult
{
    /**
     * @readonly
     */
    public \PHPStan\Type\Type $type;
    /** @var list<string>
     *  @readonly */
    public array $reasons;
    /**
     * @param T $type
     * @param list<string> $reasons
     */
    public function __construct(\PHPStan\Type\Type $type, array $reasons)
    {
        $this->type = $type;
        $this->reasons = $reasons;
    }
}
