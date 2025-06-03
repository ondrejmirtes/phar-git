<?php

namespace _PHPStan_checksum\olvlvl\ComposerAttributeCollector\Datastore;

use _PHPStan_checksum\olvlvl\ComposerAttributeCollector\Datastore;
final class RuntimeDatastore implements Datastore
{
    /**
     * @var array<string, array<int|string, mixed>>
     */
    private array $datastore = [];
    public function get(string $key) : array
    {
        return $this->datastore[$key] ?? [];
    }
    public function set(string $key, array $data) : void
    {
        $this->datastore[$key] = $data;
    }
}
