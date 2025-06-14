<?php

declare (strict_types=1);
namespace PHPStan\DependencyInjection\Nette;

use PHPStan\DependencyInjection\Container;
use PHPStan\DependencyInjection\MissingServiceException;
use PHPStan\DependencyInjection\ParameterNotFoundException;
use function array_key_exists;
use function array_keys;
use function array_map;
/**
 * @internal
 */
final class NetteContainer implements Container
{
    private \_PHPStan_checksum\Nette\DI\Container $container;
    public function __construct(\_PHPStan_checksum\Nette\DI\Container $container)
    {
        $this->container = $container;
    }
    public function hasService(string $serviceName): bool
    {
        return $this->container->hasService($serviceName);
    }
    /**
     * @return mixed
     */
    public function getService(string $serviceName)
    {
        try {
            return $this->container->getService($serviceName);
        } catch (\_PHPStan_checksum\Nette\DI\MissingServiceException $e) {
            throw new MissingServiceException($e->getMessage(), 0, $e);
        }
    }
    /**
     * @template T of object
     * @param class-string<T> $className
     * @return T
     */
    public function getByType(string $className)
    {
        try {
            return $this->container->getByType($className);
        } catch (\_PHPStan_checksum\Nette\DI\MissingServiceException $e) {
            throw new MissingServiceException($e->getMessage(), 0, $e);
        }
    }
    /**
     * @param class-string $className
     * @return string[]
     */
    public function findServiceNamesByType(string $className): array
    {
        return $this->container->findByType($className);
    }
    /**
     * @return mixed[]
     */
    public function getServicesByTag(string $tagName): array
    {
        return $this->tagsToServices($this->container->findByTag($tagName));
    }
    /**
     * @return mixed[]
     */
    public function getParameters(): array
    {
        return $this->container->getParameters();
    }
    public function hasParameter(string $parameterName): bool
    {
        return array_key_exists($parameterName, $this->container->getParameters());
    }
    /**
     * @return mixed
     */
    public function getParameter(string $parameterName)
    {
        if (!$this->hasParameter($parameterName)) {
            throw new ParameterNotFoundException($parameterName);
        }
        return $this->container->getParameter($parameterName);
    }
    /**
     * @param mixed[] $tags
     * @return mixed[]
     */
    private function tagsToServices(array $tags): array
    {
        return array_map(fn(string $serviceName) => $this->getService($serviceName), array_keys($tags));
    }
}
