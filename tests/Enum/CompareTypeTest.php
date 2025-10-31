<?php

namespace PromotionEngineBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use PromotionEngineBundle\Enum\CompareType;
use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\Selectable;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;

/**
 * @internal
 */
#[CoversClass(CompareType::class)]
final class CompareTypeTest extends AbstractEnumTestCase
{
    /**
     * 测试枚举值的完整性
     */
    public function testEnumValues(): void
    {
        $this->assertEquals('equal', CompareType::EQUAL->value);
        $this->assertEquals('not-equal', CompareType::NOT_EQUAL->value);
        $this->assertEquals('gte', CompareType::GTE->value);
        $this->assertEquals('lte', CompareType::LTE->value);
        $this->assertEquals('in', CompareType::IN->value);
        $this->assertEquals('not-in', CompareType::NOT_IN->value);
    }

    /**
     * 测试 getLabel 方法
     */
    public function testGetLabel(): void
    {
        $this->assertEquals('等于', CompareType::EQUAL->getLabel());
        $this->assertEquals('不等于', CompareType::NOT_EQUAL->getLabel());
        $this->assertEquals('大于等于', CompareType::GTE->getLabel());
        $this->assertEquals('小于等于', CompareType::LTE->getLabel());
        $this->assertEquals('包含于', CompareType::IN->getLabel());
        $this->assertEquals('不包含于', CompareType::NOT_IN->getLabel());
    }

    /**
     * 测试枚举是否实现了正确的接口
     */
    public function testImplementsInterfaces(): void
    {
        $this->assertInstanceOf(Itemable::class, CompareType::EQUAL);
        $this->assertInstanceOf(Selectable::class, CompareType::EQUAL);
    }

    /**
     * 测试从值创建枚举实例
     */
    public function testFromValue(): void
    {
        $type = CompareType::from('equal');
        $this->assertSame(CompareType::EQUAL, $type);

        $type = CompareType::from('gte');
        $this->assertSame(CompareType::GTE, $type);
    }

    /**
     * 测试尝试从无效值创建枚举实例会抛出异常
     */
    public function testFromInvalidValue(): void
    {
        $this->expectException(\ValueError::class);
        CompareType::from('invalid_value');
    }

    /**
     * 测试 tryFrom 方法
     */
    public function testTryFrom(): void
    {
        $type = CompareType::tryFrom('equal');
        $this->assertSame(CompareType::EQUAL, $type);

        $type = CompareType::tryFrom('invalid_value');
        $this->assertNull($type);
    }

    /**
     * 测试 toArray 方法 (来自 ItemTrait)
     */
    public function testToArray(): void
    {
        $case = CompareType::EQUAL;
        $result = $case->toArray();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('value', $result);
        $this->assertArrayHasKey('label', $result);
        $this->assertSame('equal', $result['value']);
        $this->assertSame('等于', $result['label']);

        $case = CompareType::GTE;
        $result = $case->toArray();
        $this->assertSame('gte', $result['value']);
        $this->assertSame('大于等于', $result['label']);
    }
}
