<?php

declare (strict_types=1);
namespace PHPStan\Command\ErrorFormatter;

use _PHPStan_checksum\OndraM\CiDetector\CiDetector;
use _PHPStan_checksum\OndraM\CiDetector\Exception\CiNotDetectedException;
use PHPStan\Command\AnalysisResult;
use PHPStan\Command\Output;
/**
 * @api
 */
final class CiDetectedErrorFormatter implements \PHPStan\Command\ErrorFormatter\ErrorFormatter
{
    private \PHPStan\Command\ErrorFormatter\GithubErrorFormatter $githubErrorFormatter;
    private \PHPStan\Command\ErrorFormatter\TeamcityErrorFormatter $teamcityErrorFormatter;
    public function __construct(\PHPStan\Command\ErrorFormatter\GithubErrorFormatter $githubErrorFormatter, \PHPStan\Command\ErrorFormatter\TeamcityErrorFormatter $teamcityErrorFormatter)
    {
        $this->githubErrorFormatter = $githubErrorFormatter;
        $this->teamcityErrorFormatter = $teamcityErrorFormatter;
    }
    public function formatErrors(AnalysisResult $analysisResult, Output $output): int
    {
        $ciDetector = new CiDetector();
        try {
            $ci = $ciDetector->detect();
            if ($ci->getCiName() === CiDetector::CI_GITHUB_ACTIONS) {
                return $this->githubErrorFormatter->formatErrors($analysisResult, $output);
            } elseif ($ci->getCiName() === CiDetector::CI_TEAMCITY) {
                return $this->teamcityErrorFormatter->formatErrors($analysisResult, $output);
            }
        } catch (CiNotDetectedException $e) {
            // pass
        }
        if (!$analysisResult->hasErrors() && !$analysisResult->hasWarnings()) {
            return 0;
        }
        return $analysisResult->getTotalErrorsCount() > 0 ? 1 : 0;
    }
}
