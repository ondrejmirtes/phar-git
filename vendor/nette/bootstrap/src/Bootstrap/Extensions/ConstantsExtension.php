<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */
declare (strict_types=1);
namespace _PHPStan_checksum\Nette\Bootstrap\Extensions;

use _PHPStan_checksum\Nette;
/**
 * Constant definitions.
 */
final class ConstantsExtension extends Nette\DI\CompilerExtension
{
    public function loadConfiguration()
    {
        foreach ($this->getConfig() as $name => $value) {
            $this->initialization->addBody('define(?, ?);', [$name, $value]);
        }
    }
}
