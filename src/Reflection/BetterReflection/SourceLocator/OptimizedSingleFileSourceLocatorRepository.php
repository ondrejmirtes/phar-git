<?php

declare (strict_types=1);
namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use PHPStan\DependencyInjection\AutowiredService;
use function array_key_exists;
#[\PHPStan\DependencyInjection\AutowiredService]
final class OptimizedSingleFileSourceLocatorRepository
{
    private \PHPStan\Reflection\BetterReflection\SourceLocator\OptimizedSingleFileSourceLocatorFactory $factory;
    /** @var array<string, OptimizedSingleFileSourceLocator> */
    private array $locators = [];
    public function __construct(\PHPStan\Reflection\BetterReflection\SourceLocator\OptimizedSingleFileSourceLocatorFactory $factory)
    {
        $this->factory = $factory;
    }
    public function getOrCreate(string $fileName) : \PHPStan\Reflection\BetterReflection\SourceLocator\OptimizedSingleFileSourceLocator
    {
        if (array_key_exists($fileName, $this->locators)) {
            return $this->locators[$fileName];
        }
        $this->locators[$fileName] = $this->factory->create($fileName);
        return $this->locators[$fileName];
    }
}
