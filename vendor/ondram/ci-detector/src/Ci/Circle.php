<?php

declare (strict_types=1);
namespace _PHPStan_checksum\OndraM\CiDetector\Ci;

use _PHPStan_checksum\OndraM\CiDetector\CiDetector;
use _PHPStan_checksum\OndraM\CiDetector\Env;
use _PHPStan_checksum\OndraM\CiDetector\TrinaryLogic;
class Circle extends AbstractCi
{
    public static function isDetected(Env $env): bool
    {
        return $env->get('CIRCLECI') !== \false;
    }
    public function getCiName(): string
    {
        return CiDetector::CI_CIRCLE;
    }
    public function isPullRequest(): TrinaryLogic
    {
        return TrinaryLogic::createFromBoolean($this->env->getString('CI_PULL_REQUEST') !== '');
    }
    public function getBuildNumber(): string
    {
        return $this->env->getString('CIRCLE_BUILD_NUM');
    }
    public function getBuildUrl(): string
    {
        return $this->env->getString('CIRCLE_BUILD_URL');
    }
    public function getGitCommit(): string
    {
        return $this->env->getString('CIRCLE_SHA1');
    }
    public function getGitBranch(): string
    {
        return $this->env->getString('CIRCLE_BRANCH');
    }
    public function getRepositoryName(): string
    {
        return sprintf('%s/%s', $this->env->getString('CIRCLE_PROJECT_USERNAME'), $this->env->getString('CIRCLE_PROJECT_REPONAME'));
    }
    public function getRepositoryUrl(): string
    {
        return $this->env->getString('CIRCLE_REPOSITORY_URL');
    }
}
