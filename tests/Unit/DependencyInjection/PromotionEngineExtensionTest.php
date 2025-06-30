<?php

namespace PromotionEngineBundle\Tests\Unit\DependencyInjection;

use PHPUnit\Framework\TestCase;
use PromotionEngineBundle\DependencyInjection\PromotionEngineExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class PromotionEngineExtensionTest extends TestCase
{
    private PromotionEngineExtension $extension;
    private ContainerBuilder $container;

    protected function setUp(): void
    {
        $this->extension = new PromotionEngineExtension();
        $this->container = new ContainerBuilder();
    }

    public function testLoad(): void
    {
        $configs = [];
        
        $this->extension->load($configs, $this->container);
        
        $this->assertNotEmpty($this->container->getDefinitions());
    }

    public function testExtensionExists(): void
    {
        $this->assertTrue(class_exists(PromotionEngineExtension::class));
    }
}