<?php

declare (strict_types=1);
namespace PHPStan\Rules;

/** @api */
interface FileRuleError extends \PHPStan\Rules\RuleError
{
    public function getFile() : string;
    public function getFileDescription() : string;
}
