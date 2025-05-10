<?php

namespace PromotionEngineBundle\Tests;

use PHPUnit\Framework\TestCase;
use PromotionEngineBundle\PromotionEngineBundle;
use Tourze\BundleDependency\BundleDependencyInterface;
use Tourze\DoctrineSnowflakeBundle\DoctrineSnowflakeBundle;

class PromotionEngineBundleTest extends TestCase
{
    /**
     * 测试 PromotionEngineBundle 类是否实现了 BundleDependencyInterface 接口
     */
    public function testImplementsBundleDependencyInterface(): void
    {
        $bundle = new PromotionEngineBundle();
        $this->assertInstanceOf(BundleDependencyInterface::class, $bundle);
    }
    
    /**
     * 测试 getBundleDependencies 方法是否返回正确的依赖
     */
    public function testGetBundleDependencies(): void
    {
        $dependencies = PromotionEngineBundle::getBundleDependencies();
        
        $this->assertIsArray($dependencies);
        $this->assertArrayHasKey(DoctrineSnowflakeBundle::class, $dependencies);
        $this->assertEquals(['all' => true], $dependencies[DoctrineSnowflakeBundle::class]);
    }
} 