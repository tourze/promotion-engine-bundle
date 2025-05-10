<?php

namespace PromotionEngineBundle\Tests\Enum;

use PHPUnit\Framework\TestCase;
use PromotionEngineBundle\Enum\DiscountType;
use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\Selectable;

class DiscountTypeTest extends TestCase
{
    /**
     * 测试枚举值的完整性
     */
    public function testEnumValues(): void
    {
        $this->assertEquals('reduction', DiscountType::REDUCTION->value);
        $this->assertEquals('discount', DiscountType::DISCOUNT->value);
        $this->assertEquals('free-freight', DiscountType::FREE_FREIGHT->value);
        $this->assertEquals('buy-give', DiscountType::BUY_GIVE->value);
        $this->assertEquals('buy_n_get_m', DiscountType::BUY_N_GET_M->value);
        $this->assertEquals('progressive_discount_scheme', DiscountType::PROGRESSIVE_DISCOUNT_SCHEME->value);
        $this->assertEquals('spend_threshold_with_add_on', DiscountType::SPEND_THRESHOLD_WITH_ADD_ON->value);
    }

    /**
     * 测试 getLabel 方法
     */
    public function testGetLabel(): void
    {
        $this->assertEquals('整单减价', DiscountType::REDUCTION->getLabel());
        $this->assertEquals('整单打折', DiscountType::DISCOUNT->getLabel());
        $this->assertEquals('免邮费', DiscountType::FREE_FREIGHT->getLabel());
        $this->assertEquals('赠品', DiscountType::BUY_GIVE->getLabel());
        $this->assertEquals('买N送M', DiscountType::BUY_N_GET_M->getLabel());
        $this->assertEquals('累进折扣', DiscountType::PROGRESSIVE_DISCOUNT_SCHEME->getLabel());
        $this->assertEquals('加价购', DiscountType::SPEND_THRESHOLD_WITH_ADD_ON->getLabel());
    }

    /**
     * 测试枚举是否实现了正确的接口
     */
    public function testImplementsInterfaces(): void
    {
        $this->assertInstanceOf(Itemable::class, DiscountType::REDUCTION);
        $this->assertInstanceOf(Selectable::class, DiscountType::REDUCTION);
    }

    /**
     * 测试从值创建枚举实例
     */
    public function testFromValue(): void
    {
        $type = DiscountType::from('reduction');
        $this->assertSame(DiscountType::REDUCTION, $type);
        
        $type = DiscountType::from('discount');
        $this->assertSame(DiscountType::DISCOUNT, $type);
    }

    /**
     * 测试尝试从无效值创建枚举实例会抛出异常
     */
    public function testFromInvalidValue(): void
    {
        $this->expectException(\ValueError::class);
        DiscountType::from('invalid_value');
    }

    /**
     * 测试 tryFrom 方法
     */
    public function testTryFrom(): void
    {
        $type = DiscountType::tryFrom('reduction');
        $this->assertSame(DiscountType::REDUCTION, $type);
        
        $type = DiscountType::tryFrom('invalid_value');
        $this->assertNull($type);
    }
} 