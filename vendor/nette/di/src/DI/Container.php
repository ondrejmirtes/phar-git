<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */
declare (strict_types=1);
namespace _PHPStan_checksum\Nette\DI;

use _PHPStan_checksum\Nette;
/**
 * The dependency injection container default implementation.
 */
class Container
{
    use Nette\SmartObject;
    /**
     * @var mixed[]
     * @deprecated use Container::getParameter() or getParameters()
     */
    public $parameters = [];
    /** @var string[]  services name => type (complete list of available services) */
    protected $types = [];
    /** @var string[]  alias => service name */
    protected $aliases = [];
    /** @var array[]  tag name => service name => tag value */
    protected $tags = [];
    /** @var array[]  type => level => services */
    protected $wiring = [];
    /** @var object[]  service name => instance */
    private $instances = [];
    /** @var array<string, true> circular reference detector */
    private $creating;
    /** @var array<string, string|\Closure> */
    private $methods;
    public function __construct(array $params = [])
    {
        $this->parameters = $params + $this->getStaticParameters();
        $this->methods = array_flip(array_filter(get_class_methods($this), function ($s) {
            return preg_match('#^createService.#', $s);
        }));
    }
    public function getParameters(): array
    {
        return $this->parameters;
    }
    public function getParameter($key)
    {
        if (!array_key_exists($key, $this->parameters)) {
            $this->parameters[$key] = $this->preventDeadLock("%{$key}%", function () use ($key) {
                return $this->getDynamicParameter($key);
            });
        }
        return $this->parameters[$key];
    }
    protected function getStaticParameters(): array
    {
        return [];
    }
    protected function getDynamicParameter($key)
    {
        throw new Nette\InvalidStateException(sprintf("Parameter '%s' not found. Check if 'di › export › parameters' is enabled.", $key));
    }
    /**
     * Adds the service to the container.
     * @param  object  $service  service or its factory
     * @return static
     */
    public function addService(string $name, object $service)
    {
        $name = $this->aliases[$name] ?? $name;
        if (isset($this->instances[$name])) {
            throw new Nette\InvalidStateException(sprintf("Service '%s' already exists.", $name));
        }
        if ($service instanceof \Closure) {
            $rt = Nette\Utils\Type::fromReflection(new \ReflectionFunction($service));
            $type = $rt ? Helpers::ensureClassType($rt, 'return type of closure') : '';
        } else {
            $type = get_class($service);
        }
        if (!isset($this->methods[self::getMethodName($name)])) {
            $this->types[$name] = $type;
        } elseif (($expectedType = $this->getServiceType($name)) && !is_a($type, $expectedType, \true)) {
            throw new Nette\InvalidArgumentException(sprintf("Service '%s' must be instance of %s, %s.", $name, $expectedType, $type ? "{$type} given" : 'add typehint to closure'));
        }
        if ($service instanceof \Closure) {
            $this->methods[self::getMethodName($name)] = $service;
            $this->types[$name] = $type;
        } else {
            $this->instances[$name] = $service;
        }
        return $this;
    }
    /**
     * Removes the service from the container.
     */
    public function removeService(string $name): void
    {
        $name = $this->aliases[$name] ?? $name;
        unset($this->instances[$name]);
    }
    /**
     * Gets the service object by name.
     * @throws MissingServiceException
     */
    public function getService(string $name): object
    {
        if (!isset($this->instances[$name])) {
            if (isset($this->aliases[$name])) {
                return $this->getService($this->aliases[$name]);
            }
            $this->instances[$name] = $this->createService($name);
        }
        return $this->instances[$name];
    }
    /**
     * Gets the service object by name.
     * @throws MissingServiceException
     */
    public function getByName(string $name): object
    {
        return $this->getService($name);
    }
    /**
     * Gets the service type by name.
     * @throws MissingServiceException
     */
    public function getServiceType(string $name): string
    {
        $method = self::getMethodName($name);
        if (isset($this->aliases[$name])) {
            return $this->getServiceType($this->aliases[$name]);
        } elseif (isset($this->types[$name])) {
            return $this->types[$name];
        } elseif (isset($this->methods[$method])) {
            $type = (new \ReflectionMethod($this, $method))->getReturnType();
            return $type ? $type->getName() : '';
        } else {
            throw new MissingServiceException(sprintf("Service '%s' not found.", $name));
        }
    }
    /**
     * Does the service exist?
     */
    public function hasService(string $name): bool
    {
        $name = $this->aliases[$name] ?? $name;
        return isset($this->methods[self::getMethodName($name)]) || isset($this->instances[$name]);
    }
    /**
     * Is the service created?
     */
    public function isCreated(string $name): bool
    {
        if (!$this->hasService($name)) {
            throw new MissingServiceException(sprintf("Service '%s' not found.", $name));
        }
        $name = $this->aliases[$name] ?? $name;
        return isset($this->instances[$name]);
    }
    /**
     * Creates new instance of the service.
     * @throws MissingServiceException
     */
    public function createService(string $name, array $args = []): object
    {
        $name = $this->aliases[$name] ?? $name;
        $method = self::getMethodName($name);
        $callback = $this->methods[$method] ?? null;
        if ($callback === null) {
            throw new MissingServiceException(sprintf("Service '%s' not found.", $name));
        }
        $service = $this->preventDeadLock($name, function () use ($callback, $args, $method) {
            return $callback instanceof \Closure ? $callback(...$args) : $this->{$method}(...$args);
        });
        if (!is_object($service)) {
            throw new Nette\UnexpectedValueException(sprintf("Unable to create service '{$name}', value returned by %s is not object.", $callback instanceof \Closure ? 'closure' : "method {$method}()"));
        }
        return $service;
    }
    /**
     * Resolves service by type.
     * @template T of object
     * @param  class-string<T>  $type
     * @return ?T
     * @throws MissingServiceException
     */
    public function getByType(string $type, bool $throw = \true): ?object
    {
        $type = Helpers::normalizeClass($type);
        if (!empty($this->wiring[$type][0])) {
            if (count($names = $this->wiring[$type][0]) === 1) {
                return $this->getService($names[0]);
            }
            natsort($names);
            throw new MissingServiceException(sprintf("Multiple services of type {$type} found: %s.", implode(', ', $names)));
        } elseif ($throw) {
            if (!class_exists($type) && !interface_exists($type)) {
                throw new MissingServiceException(sprintf("Service of type '%s' not found. Check the class name because it cannot be found.", $type));
            }
            foreach ($this->methods as $method => $foo) {
                $methodType = (new \ReflectionMethod(static::class, $method))->getReturnType()->getName();
                if (is_a($methodType, $type, \true)) {
                    throw new MissingServiceException(sprintf("Service of type %s is not autowired or is missing in di › export › types.", $type));
                }
            }
            throw new MissingServiceException(sprintf('Service of type %s not found. Did you add it to configuration file?', $type));
        }
        return null;
    }
    /**
     * Gets the autowired service names of the specified type.
     * @return string[]
     * @internal
     */
    public function findAutowired(string $type): array
    {
        $type = Helpers::normalizeClass($type);
        return array_merge($this->wiring[$type][0] ?? [], $this->wiring[$type][1] ?? []);
    }
    /**
     * Gets the service names of the specified type.
     * @return string[]
     */
    public function findByType(string $type): array
    {
        $type = Helpers::normalizeClass($type);
        return empty($this->wiring[$type]) ? [] : array_merge(...array_values($this->wiring[$type]));
    }
    /**
     * Gets the service names of the specified tag.
     * @return array of [service name => tag attributes]
     */
    public function findByTag(string $tag): array
    {
        return $this->tags[$tag] ?? [];
    }
    private function preventDeadLock(string $key, \Closure $callback)
    {
        if (isset($this->creating[$key])) {
            throw new Nette\InvalidStateException(sprintf('Circular reference detected for: %s.', implode(', ', array_keys($this->creating))));
        }
        try {
            $this->creating[$key] = \true;
            return $callback();
        } finally {
            unset($this->creating[$key]);
        }
    }
    /********************* autowiring ****************d*g**/
    /**
     * Creates new instance using autowiring.
     * @throws Nette\InvalidArgumentException
     */
    public function createInstance(string $class, array $args = []): object
    {
        $rc = new \ReflectionClass($class);
        if (!$rc->isInstantiable()) {
            throw new ServiceCreationException(sprintf('Class %s is not instantiable.', $class));
        } elseif ($constructor = $rc->getConstructor()) {
            return $rc->newInstanceArgs($this->autowireArguments($constructor, $args));
        } elseif ($args) {
            throw new ServiceCreationException(sprintf('Unable to pass arguments, class %s has no constructor.', $class));
        }
        return new $class();
    }
    /**
     * Calls all methods starting with with "inject" using autowiring.
     */
    public function callInjects(object $service): void
    {
        Extensions\InjectExtension::callInjects($this, $service);
    }
    /**
     * Calls method using autowiring.
     * @return mixed
     */
    public function callMethod(callable $function, array $args = [])
    {
        return $function(...$this->autowireArguments(Nette\Utils\Callback::toReflection($function), $args));
    }
    private function autowireArguments(\ReflectionFunctionAbstract $function, array $args = []): array
    {
        return Resolver::autowireArguments($function, $args, function (string $type, bool $single) {
            return $single ? $this->getByType($type) : array_map([$this, 'getService'], $this->findAutowired($type));
        });
    }
    public static function getMethodName(string $name): string
    {
        if ($name === '') {
            throw new Nette\InvalidArgumentException('Service name must be a non-empty string.');
        }
        return 'createService' . str_replace('.', '__', ucfirst($name));
    }
    public function initialize(): void
    {
    }
}
