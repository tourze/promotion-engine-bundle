<?php

namespace PromotionEngineBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Tourze\BundleDependency\BundleDependencyInterface;
use Tourze\EasyAdmin\Attribute\Permission\AsPermission;

#[AsPermission(title: '促销引擎')]
class PromotionEngineBundle extends Bundle implements BundleDependencyInterface
{
    public static function getBundleDependencies(): array
    {
        return [
            \AntdCpBundle\AntdCpBundle::class => ['all' => true],
            \ProductBundle\ProductBundle::class => ['all' => true],
        ];
    }
}
