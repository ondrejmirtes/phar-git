<?php

declare (strict_types=1);
namespace PHPStan\Rules\Methods;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\BetterReflection\Reflector\Exception\IdentifierNotFound;
use PHPStan\Node\InClassNode;
use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Serializable;
use function sprintf;
use function strtolower;
/**
 * @implements Rule<InClassNode>
 */
final class MissingMagicSerializationMethodsRule implements Rule
{
    private PhpVersion $phpversion;
    public function __construct(PhpVersion $phpversion)
    {
        $this->phpversion = $phpversion;
    }
    public function getNodeType(): string
    {
        return InClassNode::class;
    }
    public function processNode(Node $node, Scope $scope): array
    {
        $classReflection = $node->getClassReflection();
        if (!$this->phpversion->serializableRequiresMagicMethods()) {
            return [];
        }
        if (!$classReflection->implementsInterface(Serializable::class)) {
            return [];
        }
        if ($classReflection->isAbstract() || $classReflection->isInterface() || $classReflection->isEnum()) {
            return [];
        }
        $messages = [];
        try {
            $nativeMethods = $classReflection->getNativeReflection()->getMethods();
        } catch (IdentifierNotFound $e) {
            return [];
        }
        $missingMagicSerialize = \true;
        $missingMagicUnserialize = \true;
        foreach ($nativeMethods as $method) {
            if (strtolower($method->getName()) === '__serialize') {
                $missingMagicSerialize = \false;
            }
            if (strtolower($method->getName()) !== '__unserialize') {
                continue;
            }
            $missingMagicUnserialize = \false;
        }
        if ($missingMagicSerialize) {
            $messages[] = RuleErrorBuilder::message(sprintf('Non-abstract class %s implements the Serializable interface, but does not implement __serialize().', $classReflection->getDisplayName()))->tip('See https://wiki.php.net/rfc/phase_out_serializable')->identifier('class.serializable')->build();
        }
        if ($missingMagicUnserialize) {
            $messages[] = RuleErrorBuilder::message(sprintf('Non-abstract class %s implements the Serializable interface, but does not implement __unserialize().', $classReflection->getDisplayName()))->tip('See https://wiki.php.net/rfc/phase_out_serializable')->identifier('class.serializable')->build();
        }
        return $messages;
    }
}
