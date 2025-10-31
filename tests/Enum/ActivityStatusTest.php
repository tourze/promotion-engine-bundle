<?php

namespace PromotionEngineBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use PromotionEngineBundle\Enum\ActivityStatus;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;

/**
 * @internal
 */
#[CoversClass(ActivityStatus::class)]
final class ActivityStatusTest extends AbstractEnumTestCase
{
    // AbstractEnumTestCase会自动从CoversClass中提取Enum类
    // 无需重写getEnumClass()方法

    public function testToArray(): void
    {
        $status = ActivityStatus::PENDING;
        $result = $status->toArray();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('value', $result);
        $this->assertArrayHasKey('label', $result);
        $this->assertEquals('pending', $result['value']);
        $this->assertEquals('待开始', $result['label']);
    }

    public function testToArrayForAllCases(): void
    {
        foreach (ActivityStatus::cases() as $status) {
            $result = $status->toArray();
            $this->assertIsArray($result);
            $this->assertArrayHasKey('value', $result);
            $this->assertArrayHasKey('label', $result);
            $this->assertEquals($status->value, $result['value']);
            $this->assertEquals($status->getLabel(), $result['label']);
        }
    }
}
