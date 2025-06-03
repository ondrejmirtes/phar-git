<?php

declare (strict_types=1);
namespace PHPStan\DependencyInjection;

use _PHPStan_checksum\Nette\DI\CompilerExtension;
use _PHPStan_checksum\Nette\Schema\Expect;
use _PHPStan_checksum\Nette\Schema\Schema;
final class ExpandRelativePathExtension extends CompilerExtension
{
    public function getConfigSchema() : Schema
    {
        return Expect::listOf('string');
    }
}
