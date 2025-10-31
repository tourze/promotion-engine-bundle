<?php

declare(strict_types=1);

namespace PromotionEngineBundle\Tests\DTO;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PromotionEngineBundle\DTO\CalculateActivityDiscountItem;

/**
 * @internal
 */
#[CoversClass(CalculateActivityDiscountItem::class)]
class CalculateActivityDiscountItemTest extends TestCase
{
    public function testConstructor(): void
    {
        $item = new CalculateActivityDiscountItem(
            productId: 'prod-123',
            skuId: 'sku-456',
            quantity: 5,
            price: 99.99
        );

        $this->assertEquals('prod-123', $item->productId);
        $this->assertEquals('sku-456', $item->skuId);
        $this->assertEquals(5, $item->quantity);
        $this->assertEquals(99.99, $item->price);
    }

    public function testConstructorWithZeroValues(): void
    {
        $item = new CalculateActivityDiscountItem(
            productId: 'prod-zero',
            skuId: 'sku-zero',
            quantity: 0,
            price: 0.0
        );

        $this->assertEquals('prod-zero', $item->productId);
        $this->assertEquals('sku-zero', $item->skuId);
        $this->assertEquals(0, $item->quantity);
        $this->assertEquals(0.0, $item->price);
    }

    public function testGetTotalAmount(): void
    {
        $item = new CalculateActivityDiscountItem(
            productId: 'prod-1',
            skuId: 'sku-1',
            quantity: 3,
            price: 25.50
        );

        $this->assertEquals(76.50, $item->getTotalAmount());
    }

    public function testGetTotalAmountWithZeroQuantity(): void
    {
        $item = new CalculateActivityDiscountItem(
            productId: 'prod-1',
            skuId: 'sku-1',
            quantity: 0,
            price: 99.99
        );

        $this->assertEquals(0.0, $item->getTotalAmount());
    }

    public function testGetTotalAmountWithZeroPrice(): void
    {
        $item = new CalculateActivityDiscountItem(
            productId: 'prod-1',
            skuId: 'sku-1',
            quantity: 5,
            price: 0.0
        );

        $this->assertEquals(0.0, $item->getTotalAmount());
    }

    public function testGetTotalAmountWithFloatCalculation(): void
    {
        $item = new CalculateActivityDiscountItem(
            productId: 'prod-1',
            skuId: 'sku-1',
            quantity: 3,
            price: 10.33
        );

        $this->assertEqualsWithDelta(30.99, $item->getTotalAmount(), 0.001);
    }

    public function testToArray(): void
    {
        $item = new CalculateActivityDiscountItem(
            productId: 'prod-123',
            skuId: 'sku-456',
            quantity: 2,
            price: 49.99
        );

        $expected = [
            'productId' => 'prod-123',
            'skuId' => 'sku-456',
            'quantity' => 2,
            'price' => 49.99,
            'totalAmount' => 99.98,
        ];

        $this->assertEquals($expected, $item->toArray());
    }

    public function testToArrayWithZeroValues(): void
    {
        $item = new CalculateActivityDiscountItem(
            productId: 'prod-zero',
            skuId: 'sku-zero',
            quantity: 0,
            price: 0.0
        );

        $expected = [
            'productId' => 'prod-zero',
            'skuId' => 'sku-zero',
            'quantity' => 0,
            'price' => 0.0,
            'totalAmount' => 0.0,
        ];

        $this->assertEquals($expected, $item->toArray());
    }

    public function testReadonlyProperties(): void
    {
        $item = new CalculateActivityDiscountItem(
            productId: 'prod-1',
            skuId: 'sku-1',
            quantity: 1,
            price: 1.0
        );

        // Test that properties are readonly by accessing them
        $this->assertEquals('prod-1', $item->productId);
        $this->assertEquals('sku-1', $item->skuId);
        $this->assertEquals(1, $item->quantity);
        $this->assertEquals(1.0, $item->price);
    }
}
