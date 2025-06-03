<?php

declare (strict_types=1);
namespace PHPStan\Cache;

use function var_export;
final class MemoryCacheStorage implements \PHPStan\Cache\CacheStorage
{
    /** @var array<string, CacheItem> */
    private array $storage = [];
    /**
     * @return mixed|null
     */
    public function load(string $key, string $variableKey)
    {
        if (!isset($this->storage[$key])) {
            return null;
        }
        $item = $this->storage[$key];
        if (!$item->isVariableKeyValid($variableKey)) {
            return null;
        }
        return $item->getData();
    }
    /**
     * @param mixed $data
     */
    public function save(string $key, string $variableKey, $data) : void
    {
        $item = new \PHPStan\Cache\CacheItem($variableKey, $data);
        @var_export($item, \true);
        $this->storage[$key] = $item;
    }
}
