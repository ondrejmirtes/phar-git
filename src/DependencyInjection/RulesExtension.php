<?php

declare (strict_types=1);
namespace PHPStan\DependencyInjection;

use _PHPStan_checksum\Nette\DI\CompilerExtension;
use _PHPStan_checksum\Nette\Schema\Expect;
use _PHPStan_checksum\Nette\Schema\Schema;
use PHPStan\Rules\LazyRegistry;
final class RulesExtension extends CompilerExtension
{
    public function getConfigSchema(): Schema
    {
        return Expect::listOf('string');
    }
    public function loadConfiguration(): void
    {
        /** @var mixed[] $config */
        $config = $this->config;
        $builder = $this->getContainerBuilder();
        foreach ($config as $key => $rule) {
            $builder->addDefinition($this->prefix((string) $key))->setFactory($rule)->setAutowired($rule)->addTag(LazyRegistry::RULE_TAG);
        }
    }
}
