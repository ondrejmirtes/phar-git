<?php

declare (strict_types=1);
namespace PHPStan\Rules\Playground;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\Container;
use PHPStan\DependencyInjection\MissingServiceException;
use PHPStan\Rules\FixableNodeRuleError;
use PHPStan\Rules\LazyRegistry;
use PHPStan\Rules\LineRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function count;
use function get_class;
use function sprintf;
/**
 * @template TNodeType of Node
 * @implements Rule<TNodeType>
 */
final class PromoteParameterRule implements Rule
{
    /**
     * @var Rule<TNodeType>
     */
    private Rule $rule;
    private Container $container;
    /**
     * @var class-string<TNodeType>
     */
    private string $nodeType;
    private bool $parameterValue;
    private string $parameterName;
    /** @var Rule<TNodeType>|false|null */
    private $originalRule = null;
    /**
     * @param Rule<TNodeType> $rule
     * @param class-string<TNodeType>  $nodeType
     */
    public function __construct(Rule $rule, Container $container, string $nodeType, bool $parameterValue, string $parameterName)
    {
        $this->rule = $rule;
        $this->container = $container;
        $this->nodeType = $nodeType;
        $this->parameterValue = $parameterValue;
        $this->parameterName = $parameterName;
    }
    public function getNodeType(): string
    {
        return $this->nodeType;
    }
    /**
     * @return Rule<TNodeType>|null
     */
    private function getOriginalRule(): ?Rule
    {
        if ($this->originalRule === \false) {
            return null;
        }
        if ($this->originalRule !== null) {
            return $this->originalRule;
        }
        $originalRule = null;
        try {
            /** @var Rule<TNodeType> $originalRule */
            $originalRule = $this->container->getByType(get_class($this->rule));
            $taggedRules = $this->container->getServicesByTag(LazyRegistry::RULE_TAG);
            $found = \false;
            foreach ($taggedRules as $rule) {
                if ($originalRule !== $rule) {
                    continue;
                }
                $found = \true;
                break;
            }
            if (!$found) {
                $originalRule = null;
            }
        } catch (MissingServiceException $e) {
            // pass
        }
        if ($originalRule === null) {
            $this->originalRule = \false;
            return null;
        }
        return $this->originalRule = $originalRule;
    }
    public function processNode(Node $node, Scope $scope): array
    {
        if ($this->parameterValue) {
            return [];
        }
        if ($this->nodeType !== $this->rule->getNodeType()) {
            return [];
        }
        $originalRule = $this->getOriginalRule();
        if ($originalRule !== null) {
            $originalRuleErrors = $originalRule->processNode($node, $scope);
            if (count($originalRuleErrors) > 0) {
                return [];
            }
        }
        $errors = [];
        foreach ($this->rule->processNode($node, $scope) as $error) {
            $builder = RuleErrorBuilder::message($error->getMessage())->identifier('phpstanPlayground.configParameter')->tip(sprintf('This error would be reported if the <fg=cyan>%s: true</> parameter was enabled in your <fg=cyan>%%configurationFile%%</>.', $this->parameterName));
            if ($error instanceof LineRuleError) {
                $builder->line($error->getLine());
            }
            if ($error instanceof FixableNodeRuleError) {
                $builder->fixNode($error->getOriginalNode(), $error->getNewNodeCallable());
            }
            $errors[] = $builder->build();
        }
        return $errors;
    }
}
