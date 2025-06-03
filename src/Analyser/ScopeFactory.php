<?php

declare (strict_types=1);
namespace PHPStan\Analyser;

use PHPStan\DependencyInjection\AutowiredService;
/**
 * @api
 */
#[\PHPStan\DependencyInjection\AutowiredService]
final class ScopeFactory
{
    private \PHPStan\Analyser\InternalScopeFactory $internalScopeFactory;
    public function __construct(\PHPStan\Analyser\InternalScopeFactory $internalScopeFactory)
    {
        $this->internalScopeFactory = $internalScopeFactory;
    }
    public function create(\PHPStan\Analyser\ScopeContext $context): \PHPStan\Analyser\MutatingScope
    {
        return $this->internalScopeFactory->create($context);
    }
}
