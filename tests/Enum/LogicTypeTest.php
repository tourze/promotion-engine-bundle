<?php

namespace PromotionEngineBundle\Tests\Enum;

use PHPUnit\Framework\TestCase;
use PromotionEngineBundle\Enum\LogicType;
use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\Selectable;

class LogicTypeTest extends TestCase
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
} 