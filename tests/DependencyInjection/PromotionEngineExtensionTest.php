<?php

namespace PromotionEngineBundle\Tests\DependencyInjection;

use PHPUnit\Framework\Attributes\CoversClass;
use PromotionEngineBundle\DependencyInjection\PromotionEngineExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tourze\PHPUnitSymfonyUnitTest\AbstractDependencyInjectionExtensionTestCase;

/**
 * @internal
 */
#[CoversClass(PromotionEngineExtension::class)]
final class PromotionEngineExtensionTest extends AbstractDependencyInjectionExtensionTestCase
{
    private PromotionEngineExtension $extension;

    private ContainerBuilder $container;

    protected function setUp(): void
    {
        parent::setUp();
        $this->extension = new PromotionEngineExtension();
        $this->container = new ContainerBuilder();
        $this->container->setParameter('kernel.environment', 'test');
    }

    public function testLoad(): void
    {
        $configs = [];

        $this->extension->load($configs, $this->container);

        $this->assertNotEmpty($this->container->getDefinitions());
    }

    public function testExtensionExists(): void
    {
        $this->assertInstanceOf(PromotionEngineExtension::class, $this->extension);
    }
}
