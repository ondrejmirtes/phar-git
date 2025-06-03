<?php

declare (strict_types=1);
namespace PHPStan\Analyser\Ignore;

use PHPStan\Analyser\Error;
final class IgnoredErrorHelperProcessedResult
{
    /**
     * @var list<Error>
     */
    private array $notIgnoredErrors;
    /**
     * @var list<array{Error, mixed[]|string}>
     */
    private array $ignoredErrors;
    /**
     * @var list<string>
     */
    private array $otherIgnoreMessages;
    /**
     * @param list<Error> $notIgnoredErrors
     * @param list<array{Error, mixed[]|string}> $ignoredErrors
     * @param list<string> $otherIgnoreMessages
     */
    public function __construct(array $notIgnoredErrors, array $ignoredErrors, array $otherIgnoreMessages)
    {
        $this->notIgnoredErrors = $notIgnoredErrors;
        $this->ignoredErrors = $ignoredErrors;
        $this->otherIgnoreMessages = $otherIgnoreMessages;
    }
    /**
     * @return list<Error>
     */
    public function getNotIgnoredErrors() : array
    {
        return $this->notIgnoredErrors;
    }
    /**
     * @return list<array{Error, mixed[]|string}>
     */
    public function getIgnoredErrors() : array
    {
        return $this->ignoredErrors;
    }
    /**
     * @return list<string>
     */
    public function getOtherIgnoreMessages() : array
    {
        return $this->otherIgnoreMessages;
    }
}
