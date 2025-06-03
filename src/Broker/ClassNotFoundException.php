<?php

declare (strict_types=1);
namespace PHPStan\Broker;

use PHPStan\AnalysedCodeException;
use function sprintf;
/**
 * @api
 *
 * Unchecked exception thrown from `ReflectionProvider` and other places
 * in case the user does not check the existence of the class beforehand
 * with `hasClass()` or similar.
 */
final class ClassNotFoundException extends AnalysedCodeException
{
    private string $className;
    public function __construct(string $className)
    {
        $this->className = $className;
        parent::__construct(sprintf('Class %s was not found while trying to analyse it - discovering symbols is probably not configured properly.', $className));
    }
    public function getClassName(): string
    {
        return $this->className;
    }
    public function getTip(): string
    {
        return 'Learn more at https://phpstan.org/user-guide/discovering-symbols';
    }
}
