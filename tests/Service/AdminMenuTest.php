<?php

namespace PromotionEngineBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PromotionEngineBundle\Service\AdminMenu;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminMenuTestCase;

/**
 * @internal
 */
#[CoversClass(AdminMenu::class)]
#[RunTestsInSeparateProcesses]
final class AdminMenuTest extends AbstractEasyAdminMenuTestCase
{
    public function testServiceExists(): void
    {
        $service = self::getService(AdminMenu::class);
        $this->assertInstanceOf(AdminMenu::class, $service);
    }

    public function testServiceIsCallable(): void
    {
        $service = self::getService(AdminMenu::class);
        $this->assertIsCallable($service);
    }
}
