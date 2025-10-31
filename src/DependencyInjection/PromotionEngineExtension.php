<?php

namespace PromotionEngineBundle\DependencyInjection;

use Tourze\SymfonyDependencyServiceLoader\AutoExtension;

class PromotionEngineExtension extends AutoExtension
{
    protected function getConfigDir(): string
    {
        return __DIR__ . '/../Resources/config';
    }
}
