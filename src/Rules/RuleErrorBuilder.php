<?php

declare (strict_types=1);
namespace PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Analyser\Error;
use PHPStan\ShouldNotHappenException;
use function array_map;
use function class_exists;
use function count;
use function implode;
use function is_file;
use function sprintf;
/**
 * @api
 * @template-covariant T of RuleError
 */
final class RuleErrorBuilder
{
    private const TYPE_MESSAGE = 1;
    private const TYPE_LINE = 2;
    private const TYPE_FILE = 4;
    private const TYPE_TIP = 8;
    private const TYPE_IDENTIFIER = 16;
    private const TYPE_METADATA = 32;
    private const TYPE_NON_IGNORABLE = 64;
    private const TYPE_FIXABLE_NODE = 128;
    private int $type;
    /** @var mixed[] */
    private array $properties;
    /** @var list<string> */
    private array $tips = [];
    private function __construct(string $message)
    {
        $this->properties['message'] = $message;
        $this->type = self::TYPE_MESSAGE;
    }
    /**
     * @return array<int, array{string, array<array{string|null, string|null, string|null}>}>
     */
    public static function getRuleErrorTypes(): array
    {
        return [self::TYPE_MESSAGE => [\PHPStan\Rules\RuleError::class, [[
            'message',
            // property name
            'string',
            // native type
            'string',
        ]]], self::TYPE_LINE => [\PHPStan\Rules\LineRuleError::class, [['line', 'int', 'int']]], self::TYPE_FILE => [\PHPStan\Rules\FileRuleError::class, [['file', 'string', 'string'], ['fileDescription', 'string', 'string']]], self::TYPE_TIP => [\PHPStan\Rules\TipRuleError::class, [['tip', 'string', 'string']]], self::TYPE_IDENTIFIER => [\PHPStan\Rules\IdentifierRuleError::class, [['identifier', 'string', 'string']]], self::TYPE_METADATA => [\PHPStan\Rules\MetadataRuleError::class, [['metadata', 'array', 'mixed[]']]], self::TYPE_NON_IGNORABLE => [\PHPStan\Rules\NonIgnorableRuleError::class, []], self::TYPE_FIXABLE_NODE => [\PHPStan\Rules\FixableNodeRuleError::class, [['originalNode', '\PhpParser\Node', '\PhpParser\Node'], ['newNodeCallable', null, 'callable(\PhpParser\Node): \PhpParser\Node']]]];
    }
    /**
     * @return self<RuleError>
     */
    public static function message(string $message): self
    {
        return new self($message);
    }
    /**
     * @phpstan-this-out self<T&LineRuleError>
     * @return self<T&LineRuleError>
     */
    public function line(int $line): self
    {
        $this->properties['line'] = $line;
        $this->type |= self::TYPE_LINE;
        return $this;
    }
    /**
     * @phpstan-this-out self<T&FileRuleError>
     * @return self<T&FileRuleError>
     */
    public function file(string $file, ?string $fileDescription = null): self
    {
        if (!is_file($file)) {
            throw new ShouldNotHappenException(sprintf('File %s does not exist.', $file));
        }
        $this->properties['file'] = $file;
        $this->properties['fileDescription'] = $fileDescription ?? $file;
        $this->type |= self::TYPE_FILE;
        return $this;
    }
    /**
     * @phpstan-this-out self<T&TipRuleError>
     * @return self<T&TipRuleError>
     */
    public function tip(string $tip): self
    {
        $this->tips = [$tip];
        $this->type |= self::TYPE_TIP;
        return $this;
    }
    /**
     * @phpstan-this-out self<T&TipRuleError>
     * @return self<T&TipRuleError>
     */
    public function addTip(string $tip): self
    {
        $this->tips[] = $tip;
        $this->type |= self::TYPE_TIP;
        return $this;
    }
    /**
     * @phpstan-this-out self<T&TipRuleError>
     * @return self<T&TipRuleError>
     */
    public function discoveringSymbolsTip(): self
    {
        return $this->tip('Learn more at https://phpstan.org/user-guide/discovering-symbols');
    }
    /**
     * @param list<string> $reasons
     * @phpstan-this-out self<T&TipRuleError>
     * @return self<T&TipRuleError>
     */
    public function acceptsReasonsTip(array $reasons): self
    {
        foreach ($reasons as $reason) {
            $this->addTip($reason);
        }
        return $this;
    }
    /**
     * @phpstan-this-out self<T&TipRuleError>
     * @return self<T&TipRuleError>
     */
    public function treatPhpDocTypesAsCertainTip(): self
    {
        return $this->tip('Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.');
    }
    /**
     * Sets an error identifier.
     *
     * List of all current error identifiers in PHPStan: https://phpstan.org/error-identifiers
     *
     * @phpstan-this-out self<T&IdentifierRuleError>
     * @return self<T&IdentifierRuleError>
     */
    public function identifier(string $identifier): self
    {
        if (!Error::validateIdentifier($identifier)) {
            throw new ShouldNotHappenException(sprintf('Invalid identifier: %s, error identifiers must match /%s/', $identifier, Error::PATTERN_IDENTIFIER));
        }
        $this->properties['identifier'] = $identifier;
        $this->type |= self::TYPE_IDENTIFIER;
        return $this;
    }
    /**
     * @param mixed[] $metadata
     * @phpstan-this-out self<T&MetadataRuleError>
     * @return self<T&MetadataRuleError>
     */
    public function metadata(array $metadata): self
    {
        $this->properties['metadata'] = $metadata;
        $this->type |= self::TYPE_METADATA;
        return $this;
    }
    /**
     * @phpstan-this-out self<T&NonIgnorableRuleError>
     * @return self<T&NonIgnorableRuleError>
     */
    public function nonIgnorable(): self
    {
        $this->type |= self::TYPE_NON_IGNORABLE;
        return $this;
    }
    /**
     * @internal Experimental
     * @template TNode of Node
     * @param TNode $node
     * @param callable(TNode): Node $cb
     * @phpstan-this-out self<T&FixableNodeRuleError>
     * @return self<T&FixableNodeRuleError>
     */
    public function fixNode(Node $node, callable $cb): self
    {
        $this->properties['originalNode'] = $node;
        $this->properties['newNodeCallable'] = $cb;
        $this->type |= self::TYPE_FIXABLE_NODE;
        return $this;
    }
    /**
     * @return T
     */
    public function build(): \PHPStan\Rules\RuleError
    {
        /** @var class-string<T> $className */
        $className = sprintf('PHPStan\Rules\RuleErrors\RuleError%d', $this->type);
        if (!class_exists($className)) {
            throw new ShouldNotHappenException(sprintf('Class %s does not exist.', $className));
        }
        $ruleError = new $className();
        foreach ($this->properties as $propertyName => $value) {
            $ruleError->{$propertyName} = $value;
        }
        if (count($this->tips) > 0) {
            if (count($this->tips) === 1) {
                $ruleError->tip = $this->tips[0];
            } else {
                $ruleError->tip = implode("\n", array_map(static fn(string $tip) => sprintf('• %s', $tip), $this->tips));
            }
        }
        return $ruleError;
    }
}
