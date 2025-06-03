<?php

declare (strict_types=1);
namespace PHPStan\Broker;

use PHPStan\AnalysedCodeException;
use function sprintf;
/**
 * @api
 *
 * Unchecked exception thrown from `ReflectionProvider`
 * in case the user does not check the existence of the constant beforehand
 * with `hasConstant()`.
 */
final class ConstantNotFoundException extends AnalysedCodeException
{
    private string $constantName;
    public function __construct(string $constantName)
    {
        $this->constantName = $constantName;
        parent::__construct(sprintf('Constant %s not found.', $constantName));
    }
    public function getConstantName() : string
    {
        return $this->constantName;
    }
    public function getTip() : string
    {
        return 'Learn more at https://phpstan.org/user-guide/discovering-symbols';
    }
}
