<?php

declare (strict_types=1);
namespace PHPStan\PhpDocParser\Ast\Type;

use PHPStan\PhpDocParser\Ast\NodeAttributes;
use function sprintf;
class ConditionalTypeForParameterNode implements \PHPStan\PhpDocParser\Ast\Type\TypeNode
{
    use NodeAttributes;
    public string $parameterName;
    public \PHPStan\PhpDocParser\Ast\Type\TypeNode $targetType;
    public \PHPStan\PhpDocParser\Ast\Type\TypeNode $if;
    public \PHPStan\PhpDocParser\Ast\Type\TypeNode $else;
    public bool $negated;
    public function __construct(string $parameterName, \PHPStan\PhpDocParser\Ast\Type\TypeNode $targetType, \PHPStan\PhpDocParser\Ast\Type\TypeNode $if, \PHPStan\PhpDocParser\Ast\Type\TypeNode $else, bool $negated)
    {
        $this->parameterName = $parameterName;
        $this->targetType = $targetType;
        $this->if = $if;
        $this->else = $else;
        $this->negated = $negated;
    }
    public function __toString(): string
    {
        return sprintf('(%s %s %s ? %s : %s)', $this->parameterName, $this->negated ? 'is not' : 'is', $this->targetType, $this->if, $this->else);
    }
}
