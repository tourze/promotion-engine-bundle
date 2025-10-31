<?php

namespace PromotionEngineBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use PromotionEngineBundle\Enum\DiscountType;
use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\Selectable;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;

/**
 * @internal
 */
#[CoversClass(DiscountType::class)]
final class DiscountTypeTest extends AbstractEnumTestCase
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

    /**
     * 测试 toArray 方法 (来自 ItemTrait)
     */
    public function testToArray(): void
    {
        $case = DiscountType::REDUCTION;
        $result = $case->toArray();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('value', $result);
        $this->assertArrayHasKey('label', $result);
        $this->assertSame('reduction', $result['value']);
        $this->assertSame('整单减价', $result['label']);

        $case = DiscountType::DISCOUNT;
        $result = $case->toArray();
        $this->assertSame('discount', $result['value']);
        $this->assertSame('整单打折', $result['label']);
    }
}
