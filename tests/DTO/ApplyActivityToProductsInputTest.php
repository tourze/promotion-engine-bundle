<?php

declare(strict_types=1);

namespace PromotionEngineBundle\Tests\DTO;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PromotionEngineBundle\DTO\ApplyActivityProductInput;
use PromotionEngineBundle\DTO\ApplyActivityToProductsInput;

/**
 * @internal
 */
#[CoversClass(ApplyActivityToProductsInput::class)]
class ApplyActivityToProductsInputTest extends TestCase
{
    public function testConstructor(): void
    {
        $product1 = new ApplyActivityProductInput('prod-1', '99.99');
        $product2 = new ApplyActivityProductInput('prod-2', '199.99');
        $products = [$product1, $product2];

        $input = new ApplyActivityToProductsInput('activity-123', $products);

        $this->assertEquals('activity-123', $input->activityId);
        $this->assertEquals($products, $input->products);
        $this->assertCount(2, $input->products);
    }

    public function testConstructorWithEmptyProducts(): void
    {
        $input = new ApplyActivityToProductsInput('activity-123', []);

        $this->assertEquals('activity-123', $input->activityId);
        $this->assertEmpty($input->products);
    }

    public function testHasProductsWithProducts(): void
    {
        $product = new ApplyActivityProductInput('prod-1', '99.99');
        $input = new ApplyActivityToProductsInput('activity-123', [$product]);

        $this->assertTrue($input->hasProducts());
    }

    public function testHasProductsWithoutProducts(): void
    {
        $input = new ApplyActivityToProductsInput('activity-123', []);

        $this->assertFalse($input->hasProducts());
    }

    public function testGetProductIds(): void
    {
        $product1 = new ApplyActivityProductInput('prod-1', '99.99');
        $product2 = new ApplyActivityProductInput('prod-2', '199.99');
        $product3 = new ApplyActivityProductInput('prod-3', '299.99');
        $products = [$product1, $product2, $product3];

        $input = new ApplyActivityToProductsInput('activity-123', $products);
        $productIds = $input->getProductIds();

        $this->assertEquals(['prod-1', 'prod-2', 'prod-3'], $productIds);
    }

    public function testGetProductIdsWithEmptyProducts(): void
    {
        $input = new ApplyActivityToProductsInput('activity-123', []);
        $productIds = $input->getProductIds();

        $this->assertEquals([], $productIds);
    }

    public function testGetProductByIdFound(): void
    {
        $product1 = new ApplyActivityProductInput('prod-1', '99.99');
        $product2 = new ApplyActivityProductInput('prod-2', '199.99');
        $products = [$product1, $product2];

        $input = new ApplyActivityToProductsInput('activity-123', $products);
        $foundProduct = $input->getProductById('prod-2');

        $this->assertNotNull($foundProduct);
        $this->assertEquals('prod-2', $foundProduct->productId);
        $this->assertEquals('199.99', $foundProduct->activityPrice);
    }

    public function testGetProductByIdNotFound(): void
    {
        $product1 = new ApplyActivityProductInput('prod-1', '99.99');
        $product2 = new ApplyActivityProductInput('prod-2', '199.99');
        $products = [$product1, $product2];

        $input = new ApplyActivityToProductsInput('activity-123', $products);
        $foundProduct = $input->getProductById('prod-999');

        $this->assertNull($foundProduct);
    }

    public function testGetProductByIdWithEmptyProducts(): void
    {
        $input = new ApplyActivityToProductsInput('activity-123', []);
        $foundProduct = $input->getProductById('prod-1');

        $this->assertNull($foundProduct);
    }
}
