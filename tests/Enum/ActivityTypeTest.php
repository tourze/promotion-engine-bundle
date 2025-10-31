<?php

namespace PromotionEngineBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use PromotionEngineBundle\Enum\ActivityType;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;

/**
 * @internal
 */
#[CoversClass(ActivityType::class)]
final class ActivityTypeTest extends AbstractEnumTestCase
{
    // AbstractEnumTestCase会自动从CoversClass中提取Enum类
    // 无需重写getEnumClass()方法

    public function testToArray(): void
    {
        $type = ActivityType::LIMITED_TIME_DISCOUNT;
        $result = $type->toArray();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('value', $result);
        $this->assertArrayHasKey('label', $result);
        $this->assertEquals('limited_time_discount', $result['value']);
        $this->assertEquals('限时折扣', $result['label']);
    }

    public function testToArrayForAllCases(): void
    {
        foreach (ActivityType::cases() as $type) {
            $result = $type->toArray();
            $this->assertIsArray($result);
            $this->assertArrayHasKey('value', $result);
            $this->assertArrayHasKey('label', $result);
            $this->assertEquals($type->value, $result['value']);
            $this->assertEquals($type->getLabel(), $result['label']);
        }
    }
}
