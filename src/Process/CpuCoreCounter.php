<?php

declare (strict_types=1);
namespace PHPStan\Process;

use _PHPStan_checksum\Fidry\CpuCoreCounter\CpuCoreCounter as FidryCpuCoreCounter;
use _PHPStan_checksum\Fidry\CpuCoreCounter\NumberOfCpuCoreNotFound;
use PHPStan\DependencyInjection\AutowiredService;
#[\PHPStan\DependencyInjection\AutowiredService]
final class CpuCoreCounter
{
    private ?int $count = null;
    public function getNumberOfCpuCores() : int
    {
        if ($this->count !== null) {
            return $this->count;
        }
        try {
            $this->count = (new FidryCpuCoreCounter())->getCount();
        } catch (NumberOfCpuCoreNotFound $e) {
            $this->count = 1;
        }
        return $this->count;
    }
}
