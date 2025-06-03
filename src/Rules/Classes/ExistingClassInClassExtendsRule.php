<?php

declare (strict_types=1);
namespace PHPStan\Rules\Classes;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\ClassNameCheck;
use PHPStan\Rules\ClassNameNodePair;
use PHPStan\Rules\ClassNameUsageLocation;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function sprintf;
/**
 * @implements Rule<Node\Stmt\Class_>
 */
final class ExistingClassInClassExtendsRule implements Rule
{
    private ClassNameCheck $classCheck;
    private ReflectionProvider $reflectionProvider;
    private bool $discoveringSymbolsTip;
    public function __construct(ClassNameCheck $classCheck, ReflectionProvider $reflectionProvider, bool $discoveringSymbolsTip)
    {
        $this->classCheck = $classCheck;
        $this->reflectionProvider = $reflectionProvider;
        $this->discoveringSymbolsTip = $discoveringSymbolsTip;
    }
    public function getNodeType() : string
    {
        return Node\Stmt\Class_::class;
    }
    public function processNode(Node $node, Scope $scope) : array
    {
        if ($node->extends === null) {
            return [];
        }
        $extendedClassName = (string) $node->extends;
        $currentClassName = null;
        if (isset($node->namespacedName)) {
            $currentClassName = (string) $node->namespacedName;
        }
        $messages = $this->classCheck->checkClassNames($scope, [new ClassNameNodePair($extendedClassName, $node->extends)], ClassNameUsageLocation::from(ClassNameUsageLocation::CLASS_EXTENDS, ['currentClassName' => $currentClassName]));
        if (!$this->reflectionProvider->hasClass($extendedClassName)) {
            if (!$scope->isInClassExists($extendedClassName)) {
                $errorBuilder = RuleErrorBuilder::message(sprintf('%s extends unknown class %s.', $currentClassName !== null ? sprintf('Class %s', $currentClassName) : 'Anonymous class', $extendedClassName))->identifier('class.notFound')->nonIgnorable();
                if ($this->discoveringSymbolsTip) {
                    $errorBuilder->discoveringSymbolsTip();
                }
                $messages[] = $errorBuilder->build();
            }
        } else {
            $reflection = $this->reflectionProvider->getClass($extendedClassName);
            if ($reflection->isInterface()) {
                $messages[] = RuleErrorBuilder::message(sprintf('%s extends interface %s.', $currentClassName !== null ? sprintf('Class %s', $currentClassName) : 'Anonymous class', $reflection->getDisplayName()))->identifier('class.extendsInterface')->nonIgnorable()->build();
            } elseif ($reflection->isTrait()) {
                $messages[] = RuleErrorBuilder::message(sprintf('%s extends trait %s.', $currentClassName !== null ? sprintf('Class %s', $currentClassName) : 'Anonymous class', $reflection->getDisplayName()))->identifier('class.extendsTrait')->nonIgnorable()->build();
            } elseif ($reflection->isEnum()) {
                $messages[] = RuleErrorBuilder::message(sprintf('%s extends enum %s.', $currentClassName !== null ? sprintf('Class %s', $currentClassName) : 'Anonymous class', $reflection->getDisplayName()))->identifier('class.extendsEnum')->nonIgnorable()->build();
            } elseif ($reflection->isFinalByKeyword()) {
                $messages[] = RuleErrorBuilder::message(sprintf('%s extends final class %s.', $currentClassName !== null ? sprintf('Class %s', $currentClassName) : 'Anonymous class', $reflection->getDisplayName()))->identifier('class.extendsFinal')->nonIgnorable()->build();
            } elseif ($reflection->isFinal()) {
                $messages[] = RuleErrorBuilder::message(sprintf('%s extends @final class %s.', $currentClassName !== null ? sprintf('Class %s', $currentClassName) : 'Anonymous class', $reflection->getDisplayName()))->identifier('class.extendsFinalByPhpDoc')->build();
            }
            if ($reflection->isClass()) {
                if ($node->isReadonly()) {
                    if (!$reflection->isReadOnly()) {
                        $messages[] = RuleErrorBuilder::message(sprintf('%s extends non-readonly class %s.', $currentClassName !== null ? sprintf('Readonly class %s', $currentClassName) : 'Anonymous readonly class', $reflection->getDisplayName()))->identifier('class.readOnly')->nonIgnorable()->build();
                    }
                } elseif ($reflection->isReadOnly()) {
                    $messages[] = RuleErrorBuilder::message(sprintf('%s extends readonly class %s.', $currentClassName !== null ? sprintf('Non-readonly class %s', $currentClassName) : 'Anonymous non-readonly class', $reflection->getDisplayName()))->identifier('class.nonReadOnly')->nonIgnorable()->build();
                }
            }
        }
        return $messages;
    }
}
