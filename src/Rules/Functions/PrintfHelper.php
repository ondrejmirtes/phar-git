<?php

declare (strict_types=1);
namespace PHPStan\Rules\Functions;

use _PHPStan_checksum\Nette\Utils\Strings;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Php\PhpVersion;
use function array_filter;
use function count;
use function max;
use function sprintf;
use function strlen;
use const PREG_SET_ORDER;
#[AutowiredService]
final class PrintfHelper
{
    private PhpVersion $phpVersion;
    public function __construct(PhpVersion $phpVersion)
    {
        $this->phpVersion = $phpVersion;
    }
    public function getPrintfPlaceholdersCount(string $format): int
    {
        return $this->getPlaceholdersCount('(?:[bs%s]|l?[cdeEgfFGouxX])', $format);
    }
    public function getScanfPlaceholdersCount(string $format): int
    {
        return $this->getPlaceholdersCount('(?:[cdDeEfinosuxX%s]|\[[^\]]+\])', $format);
    }
    private function getPlaceholdersCount(string $specifiersPattern, string $format): int
    {
        $addSpecifier = '';
        if ($this->phpVersion->supportsHhPrintfSpecifier()) {
            $addSpecifier .= 'hH';
        }
        $specifiers = sprintf($specifiersPattern, $addSpecifier);
        $pattern = '~(?<before>%*)%(?:(?<position>\d+)\$)?[-+]?(?:[ 0]|(?:\'[^%]))?(?<width>\*)?-?\d*(?:\.(?:\d+|(?<precision>\*))?)?' . $specifiers . '~';
        $matches = Strings::matchAll($format, $pattern, PREG_SET_ORDER);
        if (count($matches) === 0) {
            return 0;
        }
        $placeholders = array_filter($matches, static fn(array $match): bool => strlen($match['before']) % 2 === 0);
        if (count($placeholders) === 0) {
            return 0;
        }
        $maxPositionedNumber = 0;
        $maxOrdinaryNumber = 0;
        foreach ($placeholders as $placeholder) {
            if (isset($placeholder['width']) && $placeholder['width'] !== '') {
                $maxOrdinaryNumber++;
            }
            if (isset($placeholder['precision']) && $placeholder['precision'] !== '') {
                $maxOrdinaryNumber++;
            }
            if (isset($placeholder['position']) && $placeholder['position'] !== '') {
                $maxPositionedNumber = max((int) $placeholder['position'], $maxPositionedNumber);
            } else {
                $maxOrdinaryNumber++;
            }
        }
        return max($maxPositionedNumber, $maxOrdinaryNumber);
    }
}
