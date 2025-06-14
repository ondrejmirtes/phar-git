<?php

declare (strict_types=1);
namespace PHPStan\Analyser\Ignore;

use _PHPStan_checksum\Nette\Utils\Strings;
use PHPStan\Analyser\Error;
use PHPStan\DependencyInjection\AutowiredService;
use function implode;
use const PREG_SET_ORDER;
#[AutowiredService]
final class IgnoreLexer
{
    public const TOKEN_WHITESPACE = 1;
    public const TOKEN_END = 2;
    public const TOKEN_IDENTIFIER = 3;
    public const TOKEN_COMMA = 4;
    public const TOKEN_OPEN_PARENTHESIS = 5;
    public const TOKEN_CLOSE_PARENTHESIS = 6;
    public const TOKEN_OTHER = 7;
    private const LABELS = [self::TOKEN_WHITESPACE => 'T_WHITESPACE', self::TOKEN_END => 'end', self::TOKEN_IDENTIFIER => 'identifier', self::TOKEN_COMMA => 'comma (,)', self::TOKEN_OPEN_PARENTHESIS => 'T_OPEN_PARENTHESIS', self::TOKEN_CLOSE_PARENTHESIS => 'T_CLOSE_PARENTHESIS', self::TOKEN_OTHER => 'T_OTHER'];
    public const VALUE_OFFSET = 0;
    public const TYPE_OFFSET = 1;
    public const LINE_OFFSET = 2;
    private ?string $regexp = null;
    /**
     * @return list<array{string, self::TOKEN_*, int}>
     */
    public function tokenize(string $input): array
    {
        if ($this->regexp === null) {
            $this->regexp = $this->generateRegexp();
        }
        $matches = Strings::matchAll($input, $this->regexp, PREG_SET_ORDER);
        $tokens = [];
        $line = 1;
        foreach ($matches as $match) {
            /** @var self::TOKEN_* $type */
            $type = (int) $match['MARK'];
            $tokens[] = [$match[0], $type, $line];
            if ($type !== self::TOKEN_END) {
                continue;
            }
            $line++;
        }
        if (($type ?? null) !== self::TOKEN_END) {
            $tokens[] = ['', self::TOKEN_END, $line];
            // ensure ending token is present
        }
        return $tokens;
    }
    /**
     * @param self::TOKEN_* $type
     */
    public function getLabel(int $type): string
    {
        return self::LABELS[$type];
    }
    private function generateRegexp(): string
    {
        $patterns = [
            self::TOKEN_WHITESPACE => '[\x09\x20]++',
            self::TOKEN_END => '(\r?+\n[\x09\x20]*+(?:\*(?!/)\x20?+)?|\*/)',
            self::TOKEN_IDENTIFIER => Error::PATTERN_IDENTIFIER,
            self::TOKEN_COMMA => ',',
            self::TOKEN_OPEN_PARENTHESIS => '\(',
            self::TOKEN_CLOSE_PARENTHESIS => '\)',
            // everything except whitespaces and parentheses
            self::TOKEN_OTHER => '([^\s\)\(])++',
        ];
        foreach ($patterns as $type => &$pattern) {
            $pattern = '(?:' . $pattern . ')(*MARK:' . $type . ')';
        }
        return '~' . implode('|', $patterns) . '~Asi';
    }
}
