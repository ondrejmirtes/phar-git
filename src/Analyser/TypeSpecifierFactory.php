<?php

declare (strict_types=1);
namespace PHPStan\Analyser;

use PHPStan\Broker\BrokerFactory;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\Container;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\ReflectionProvider;
use function array_merge;
#[AutowiredService('typeSpecifierFactory')]
final class TypeSpecifierFactory
{
    private Container $container;
    public const FUNCTION_TYPE_SPECIFYING_EXTENSION_TAG = 'phpstan.typeSpecifier.functionTypeSpecifyingExtension';
    public const METHOD_TYPE_SPECIFYING_EXTENSION_TAG = 'phpstan.typeSpecifier.methodTypeSpecifyingExtension';
    public const STATIC_METHOD_TYPE_SPECIFYING_EXTENSION_TAG = 'phpstan.typeSpecifier.staticMethodTypeSpecifyingExtension';
    public function __construct(Container $container)
    {
        $this->container = $container;
    }
    public function create(): \PHPStan\Analyser\TypeSpecifier
    {
        $typeSpecifier = new \PHPStan\Analyser\TypeSpecifier($this->container->getByType(ExprPrinter::class), $this->container->getByType(ReflectionProvider::class), $this->container->getByType(PhpVersion::class), $this->container->getServicesByTag(self::FUNCTION_TYPE_SPECIFYING_EXTENSION_TAG), $this->container->getServicesByTag(self::METHOD_TYPE_SPECIFYING_EXTENSION_TAG), $this->container->getServicesByTag(self::STATIC_METHOD_TYPE_SPECIFYING_EXTENSION_TAG), $this->container->getParameter('rememberPossiblyImpureFunctionValues'));
        foreach (array_merge($this->container->getServicesByTag(BrokerFactory::PROPERTIES_CLASS_REFLECTION_EXTENSION_TAG), $this->container->getServicesByTag(BrokerFactory::METHODS_CLASS_REFLECTION_EXTENSION_TAG), $this->container->getServicesByTag(BrokerFactory::DYNAMIC_METHOD_RETURN_TYPE_EXTENSION_TAG), $this->container->getServicesByTag(BrokerFactory::DYNAMIC_STATIC_METHOD_RETURN_TYPE_EXTENSION_TAG), $this->container->getServicesByTag(BrokerFactory::DYNAMIC_FUNCTION_RETURN_TYPE_EXTENSION_TAG)) as $extension) {
            if (!$extension instanceof \PHPStan\Analyser\TypeSpecifierAwareExtension) {
                continue;
            }
            $extension->setTypeSpecifier($typeSpecifier);
        }
        return $typeSpecifier;
    }
}
