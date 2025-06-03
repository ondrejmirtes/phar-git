<?php

declare (strict_types=1);
namespace PHPStan\Rules;

/** @api */
interface IdentifierRuleError extends \PHPStan\Rules\RuleError
{
    public function getIdentifier() : string;
}
