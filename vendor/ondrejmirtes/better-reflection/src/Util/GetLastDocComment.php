<?php

declare (strict_types=1);
namespace PHPStan\BetterReflection\Util;

use PhpParser\NodeAbstract;
use function assert;
/** @internal */
final class GetLastDocComment
{
    /**
     * @return non-empty-string|null
     *
     * @psalm-pure
     */
    public static function forNode(NodeAbstract $node): ?string
    {
        /** @psalm-suppress ImpureMethodCall */
        $docComment = $node->getDocComment();
        if ($docComment === null) {
            return null;
        }
        /** @psalm-suppress ImpureMethodCall */
        $comment = $docComment->getReformattedText();
        assert($comment !== '');
        return $comment;
    }
}
