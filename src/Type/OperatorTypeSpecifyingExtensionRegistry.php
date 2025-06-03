<?php

declare (strict_types=1);
namespace PHPStan\Type;

use function array_filter;
use function array_values;
final class OperatorTypeSpecifyingExtensionRegistry
{
    /**
     * @var OperatorTypeSpecifyingExtension[]
     */
    private array $extensions;
    /**
     * @param OperatorTypeSpecifyingExtension[] $extensions
     */
    public function __construct(array $extensions)
    {
        $this->extensions = $extensions;
    }
    /**
     * @return OperatorTypeSpecifyingExtension[]
     */
    public function getOperatorTypeSpecifyingExtensions(string $operator, \PHPStan\Type\Type $leftType, \PHPStan\Type\Type $rightType): array
    {
        return array_values(array_filter($this->extensions, static fn(\PHPStan\Type\OperatorTypeSpecifyingExtension $extension): bool => $extension->isOperatorSupported($operator, $leftType, $rightType)));
    }
}
