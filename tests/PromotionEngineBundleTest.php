<?php

declare(strict_types=1);

namespace PromotionEngineBundle\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PromotionEngineBundle\PromotionEngineBundle;
use Tourze\PHPUnitSymfonyKernelTest\AbstractBundleTestCase;

/**
 * @internal
 */
#[CoversClass(PromotionEngineBundle::class)]
#[RunTestsInSeparateProcesses]
final class PromotionEngineBundleTest extends AbstractBundleTestCase
{
}
