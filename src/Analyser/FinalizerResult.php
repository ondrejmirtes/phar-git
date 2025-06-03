<?php

declare (strict_types=1);
namespace PHPStan\Analyser;

final class FinalizerResult
{
    private \PHPStan\Analyser\AnalyserResult $analyserResult;
    /**
     * @var list<Error>
     */
    private array $collectorErrors;
    /**
     * @var list<Error>
     */
    private array $locallyIgnoredCollectorErrors;
    /**
     * @param list<Error> $collectorErrors
     * @param list<Error> $locallyIgnoredCollectorErrors
     */
    public function __construct(\PHPStan\Analyser\AnalyserResult $analyserResult, array $collectorErrors, array $locallyIgnoredCollectorErrors)
    {
        $this->analyserResult = $analyserResult;
        $this->collectorErrors = $collectorErrors;
        $this->locallyIgnoredCollectorErrors = $locallyIgnoredCollectorErrors;
    }
    /**
     * @return list<Error>
     */
    public function getErrors(): array
    {
        return $this->analyserResult->getErrors();
    }
    public function getAnalyserResult(): \PHPStan\Analyser\AnalyserResult
    {
        return $this->analyserResult;
    }
    /**
     * @return list<Error>
     */
    public function getCollectorErrors(): array
    {
        return $this->collectorErrors;
    }
    /**
     * @return list<Error>
     */
    public function getLocallyIgnoredCollectorErrors(): array
    {
        return $this->locallyIgnoredCollectorErrors;
    }
}
