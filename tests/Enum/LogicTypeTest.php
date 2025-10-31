<?php

namespace PromotionEngineBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use PromotionEngineBundle\Enum\LogicType;
use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\Selectable;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;

/**
 * @internal
 */
#[CoversClass(LogicType::class)]
final class LogicTypeTest extends AbstractEnumTestCase
{
    /**
     * 测试枚举值的完整性
     */
    public function testEnumValues(): void
    {
        $this->assertEquals('and', LogicType::LOGIC_AND->value);
    }

    /**
     * 测试 getLabel 方法
     */
    public function testGetLabel(): void
    {
        $this->assertEquals('逻辑与', LogicType::LOGIC_AND->getLabel());
    }

    /**
     * 测试枚举是否实现了正确的接口
     */
    public function testImplementsInterfaces(): void
    {
        $this->assertInstanceOf(Itemable::class, LogicType::LOGIC_AND);
        $this->assertInstanceOf(Selectable::class, LogicType::LOGIC_AND);
    }

    /**
     * 测试从值创建枚举实例
     */
    public function testFromValue(): void
    {
        $type = LogicType::from('and');
        $this->assertSame(LogicType::LOGIC_AND, $type);
    }

    /**
     * 测试尝试从无效值创建枚举实例会抛出异常
     */
    public function testFromInvalidValue(): void
    {
        $this->expectException(\ValueError::class);
        LogicType::from('invalid_value');
    }

    /**
     * 测试 tryFrom 方法
     */
    public function testTryFrom(): void
    {
        $type = LogicType::tryFrom('and');
        $this->assertSame(LogicType::LOGIC_AND, $type);

        $type = LogicType::tryFrom('invalid_value');
        $this->assertNull($type);
    }

    /**
     * 测试 toArray 方法 (来自 ItemTrait)
     */
    public function testToArray(): void
    {
        $case = LogicType::LOGIC_AND;
        $result = $case->toArray();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('value', $result);
        $this->assertArrayHasKey('label', $result);
        $this->assertSame('and', $result['value']);
        $this->assertSame('逻辑与', $result['label']);
    }
}
