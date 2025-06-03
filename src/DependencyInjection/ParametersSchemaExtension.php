<?php

declare (strict_types=1);
namespace PHPStan\DependencyInjection;

use _PHPStan_checksum\Nette\DI\CompilerExtension;
use _PHPStan_checksum\Nette\DI\Definitions\Statement;
use _PHPStan_checksum\Nette\Schema\Expect;
use _PHPStan_checksum\Nette\Schema\Schema;
final class ParametersSchemaExtension extends CompilerExtension
{
    public function getConfigSchema() : Schema
    {
        return Expect::arrayOf(Expect::type(Statement::class))->min(1);
    }
}
