<?php

declare(strict_types=1);

namespace PromotionEngineBundle\Tests\DTO;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PromotionEngineBundle\DTO\CalculateActivityDiscountInput;
use PromotionEngineBundle\DTO\CalculateActivityDiscountItem;

/**
 * @internal
 */
#[CoversClass(CalculateActivityDiscountInput::class)]
class CalculateActivityDiscountInputTest extends TestCase
{
    public function testConstructorWithValidData(): void
    {
        $items = [
            new CalculateActivityDiscountItem('product1', 'sku1', 2, 100.0),
            new CalculateActivityDiscountItem('product2', 'sku2', 1, 50.0),
        ];

        $input = new CalculateActivityDiscountInput($items, 'user123');

        $this->assertSame($items, $input->items);
        $this->assertSame('user123', $input->userId);
    }

    public function testConstructorWithNullUserId(): void
    {
        $items = [
            new CalculateActivityDiscountItem('product1', 'sku1', 1, 100.0),
        ];

        $input = new CalculateActivityDiscountInput($items);

        $this->assertSame($items, $input->items);
        $this->assertNull($input->userId);
    }

    public function testHasItemsWithItems(): void
    {
        $items = [
            new CalculateActivityDiscountItem('product1', 'sku1', 1, 100.0),
        ];

        $input = new CalculateActivityDiscountInput($items);

        $this->assertTrue($input->hasItems());
    }

    public function testHasItemsWithEmptyArray(): void
    {
        $input = new CalculateActivityDiscountInput([]);

        $this->assertFalse($input->hasItems());
    }

    public function testGetProductIds(): void
    {
        $items = [
            new CalculateActivityDiscountItem('product1', 'sku1', 1, 100.0),
            new CalculateActivityDiscountItem('product2', 'sku2', 1, 50.0),
            new CalculateActivityDiscountItem('product1', 'sku3', 1, 75.0),
        ];

        $input = new CalculateActivityDiscountInput($items);
        $productIds = $input->getProductIds();

        $this->assertCount(3, $productIds);
        $this->assertSame(['product1', 'product2', 'product1'], $productIds);
    }

    public function testGetSkuIds(): void
    {
        $items = [
            new CalculateActivityDiscountItem('product1', 'sku1', 1, 100.0),
            new CalculateActivityDiscountItem('product2', 'sku2', 1, 50.0),
        ];

        $input = new CalculateActivityDiscountInput($items);
        $skuIds = $input->getSkuIds();

        $this->assertCount(2, $skuIds);
        $this->assertSame(['sku1', 'sku2'], $skuIds);
    }

    public function testGetItemByProductIdFound(): void
    {
        $item1 = new CalculateActivityDiscountItem('product1', 'sku1', 1, 100.0);
        $item2 = new CalculateActivityDiscountItem('product2', 'sku2', 1, 50.0);

        $input = new CalculateActivityDiscountInput([$item1, $item2]);
        $foundItem = $input->getItemByProductId('product1');

        $this->assertSame($item1, $foundItem);
    }

    public function testGetItemByProductIdNotFound(): void
    {
        $item1 = new CalculateActivityDiscountItem('product1', 'sku1', 1, 100.0);

        $input = new CalculateActivityDiscountInput([$item1]);
        $foundItem = $input->getItemByProductId('product999');

        $this->assertNull($foundItem);
    }

    public function testGetTotalAmount(): void
    {
        $items = [
            new CalculateActivityDiscountItem('product1', 'sku1', 2, 100.0),
            new CalculateActivityDiscountItem('product2', 'sku2', 1, 50.0),
            new CalculateActivityDiscountItem('product3', 'sku3', 3, 25.0),
        ];

        $input = new CalculateActivityDiscountInput($items);
        $totalAmount = $input->getTotalAmount();

        $this->assertSame(325.0, $totalAmount);
    }

    public function testGetTotalAmountWithEmptyItems(): void
    {
        $input = new CalculateActivityDiscountInput([]);
        $totalAmount = $input->getTotalAmount();

        $this->assertSame(0.0, $totalAmount);
    }

    public function testGetTotalQuantity(): void
    {
        $items = [
            new CalculateActivityDiscountItem('product1', 'sku1', 2, 100.0),
            new CalculateActivityDiscountItem('product2', 'sku2', 1, 50.0),
            new CalculateActivityDiscountItem('product3', 'sku3', 3, 25.0),
        ];

        $input = new CalculateActivityDiscountInput($items);
        $totalQuantity = $input->getTotalQuantity();

        $this->assertSame(6, $totalQuantity);
    }

    public function testGetTotalQuantityWithEmptyItems(): void
    {
        $input = new CalculateActivityDiscountInput([]);
        $totalQuantity = $input->getTotalQuantity();

        $this->assertSame(0, $totalQuantity);
    }
}
