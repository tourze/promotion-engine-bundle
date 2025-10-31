<?php

namespace PromotionEngineBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use PromotionEngineBundle\Enum\LimitType;
use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\Selectable;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;

/**
 * @internal
 */
#[CoversClass(LimitType::class)]
final class LimitTypeTest extends AbstractEnumTestCase
{
    /**
     * 测试枚举值的完整性
     */
    public function testEnumValues(): void
    {
        $this->assertEquals('order-price', LimitType::ORDER_PRICE->value);
        $this->assertEquals('first-purchase-user', LimitType::FIRST_PURCHASE_USER->value);
        $this->assertEquals('secondary-purchase-user', LimitType::SECONDARY_PURCHASE_USER->value);
        $this->assertEquals('repurchase-user', LimitType::REPURCHASE_USER->value);
        $this->assertEquals('spu-id', LimitType::SPU_ID->value);
        $this->assertEquals('sku-id', LimitType::SKU_ID->value);
        $this->assertEquals('spu-per-quantity', LimitType::SPU_PER_QUANTITY->value);
        $this->assertEquals('sku-per-quantity', LimitType::SKU_PER_QUANTITY->value);
    }

    /**
     * 测试 getLabel 方法
     */
    public function testGetLabel(): void
    {
        $this->assertEquals('整单价格', LimitType::ORDER_PRICE->getLabel());
        $this->assertEquals('首次购买用户', LimitType::FIRST_PURCHASE_USER->getLabel());
        $this->assertEquals('二次购买用户', LimitType::SECONDARY_PURCHASE_USER->getLabel());
        $this->assertEquals('复购用户', LimitType::REPURCHASE_USER->getLabel());
        $this->assertEquals('SPU ID', LimitType::SPU_ID->getLabel());
        $this->assertEquals('SKU ID', LimitType::SKU_ID->getLabel());
        $this->assertEquals('SPU单品数量', LimitType::SPU_PER_QUANTITY->getLabel());
        $this->assertEquals('SKU单品数量', LimitType::SKU_PER_QUANTITY->getLabel());
    }

    /**
     * 测试枚举是否实现了正确的接口
     */
    public function testImplementsInterfaces(): void
    {
        $this->assertInstanceOf(Itemable::class, LimitType::ORDER_PRICE);
        $this->assertInstanceOf(Selectable::class, LimitType::ORDER_PRICE);
    }

    /**
     * 测试从值创建枚举实例
     */
    public function testFromValue(): void
    {
        $type = LimitType::from('order-price');
        $this->assertSame(LimitType::ORDER_PRICE, $type);

        $type = LimitType::from('sku-id');
        $this->assertSame(LimitType::SKU_ID, $type);
    }

    /**
     * 测试尝试从无效值创建枚举实例会抛出异常
     */
    public function testFromInvalidValue(): void
    {
        $this->expectException(\ValueError::class);
        LimitType::from('invalid_value');
    }

    /**
     * 测试 tryFrom 方法
     */
    public function testTryFrom(): void
    {
        $type = LimitType::tryFrom('order-price');
        $this->assertSame(LimitType::ORDER_PRICE, $type);

        $type = LimitType::tryFrom('invalid_value');
        $this->assertNull($type);
    }

    /**
     * 测试 toArray 方法 (来自 ItemTrait)
     */
    public function testToArray(): void
    {
        $case = LimitType::ORDER_PRICE;
        $result = $case->toArray();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('value', $result);
        $this->assertArrayHasKey('label', $result);
        $this->assertSame('order-price', $result['value']);
        $this->assertSame('整单价格', $result['label']);

        $case = LimitType::SKU_ID;
        $result = $case->toArray();
        $this->assertSame('sku-id', $result['value']);
        $this->assertSame('SKU ID', $result['label']);
    }
}
