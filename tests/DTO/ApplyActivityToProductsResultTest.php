<?php

declare(strict_types=1);

namespace PromotionEngineBundle\Tests\DTO;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PromotionEngineBundle\DTO\ApplyActivityToProductsResult;

/**
 * @internal
 */
#[CoversClass(ApplyActivityToProductsResult::class)]
class ApplyActivityToProductsResultTest extends TestCase
{
    public function testConstructor(): void
    {
        $result = new ApplyActivityToProductsResult(
            success: true,
            message: 'Test message',
            addedProductIds: ['prod-1', 'prod-2'],
            failedProductIds: ['prod-3']
        );

        $this->assertTrue($result->success);
        $this->assertEquals('Test message', $result->message);
        $this->assertEquals(['prod-1', 'prod-2'], $result->addedProductIds);
        $this->assertEquals(['prod-3'], $result->failedProductIds);
    }

    public function testConstructorWithDefaults(): void
    {
        $result = new ApplyActivityToProductsResult(
            success: false,
            message: 'Error message'
        );

        $this->assertFalse($result->success);
        $this->assertEquals('Error message', $result->message);
        $this->assertEquals([], $result->addedProductIds);
        $this->assertEquals([], $result->failedProductIds);
    }

    public function testSuccessWithAllProductsAdded(): void
    {
        $result = ApplyActivityToProductsResult::success(5, 5);

        $this->assertTrue($result->success);
        $this->assertEquals('成功将 5 个商品添加到活动中', $result->message);
        $this->assertEquals([], $result->addedProductIds);
        $this->assertEquals([], $result->failedProductIds);
    }

    public function testSuccessWithPartialProductsAdded(): void
    {
        $result = ApplyActivityToProductsResult::success(3, 5);

        $this->assertTrue($result->success);
        $this->assertEquals('成功添加 3 个商品，2 个商品添加失败', $result->message);
        $this->assertEquals([], $result->addedProductIds);
        $this->assertEquals([], $result->failedProductIds);
    }

    public function testSuccessWithZeroProductsAdded(): void
    {
        $result = ApplyActivityToProductsResult::success(0, 3);

        $this->assertTrue($result->success);
        $this->assertEquals('成功添加 0 个商品，3 个商品添加失败', $result->message);
        $this->assertEquals([], $result->addedProductIds);
        $this->assertEquals([], $result->failedProductIds);
    }

    public function testPartial(): void
    {
        $addedIds = ['prod-1', 'prod-2'];
        $failedIds = ['prod-3', 'prod-4', 'prod-5'];

        $result = ApplyActivityToProductsResult::partial($addedIds, $failedIds);

        $this->assertTrue($result->success);
        $this->assertEquals('成功添加 2 个商品，3 个商品添加失败', $result->message);
        $this->assertEquals($addedIds, $result->addedProductIds);
        $this->assertEquals($failedIds, $result->failedProductIds);
    }

    public function testPartialWithEmptyArrays(): void
    {
        $result = ApplyActivityToProductsResult::partial([], []);

        $this->assertTrue($result->success);
        $this->assertEquals('成功添加 0 个商品，0 个商品添加失败', $result->message);
        $this->assertEquals([], $result->addedProductIds);
        $this->assertEquals([], $result->failedProductIds);
    }

    public function testFailure(): void
    {
        $errorMessage = '活动不存在';
        $result = ApplyActivityToProductsResult::failure($errorMessage);

        $this->assertFalse($result->success);
        $this->assertEquals($errorMessage, $result->message);
        $this->assertEquals([], $result->addedProductIds);
        $this->assertEquals([], $result->failedProductIds);
    }

    public function testToArray(): void
    {
        $result = new ApplyActivityToProductsResult(
            success: true,
            message: 'Test message',
            addedProductIds: ['prod-1', 'prod-2'],
            failedProductIds: ['prod-3']
        );

        $expected = [
            'success' => true,
            'message' => 'Test message',
            'addedProductIds' => ['prod-1', 'prod-2'],
            'failedProductIds' => ['prod-3'],
            'addedCount' => 2,
            'failedCount' => 1,
        ];

        $this->assertEquals($expected, $result->toArray());
    }

    public function testToArrayWithEmptyArrays(): void
    {
        $result = new ApplyActivityToProductsResult(
            success: false,
            message: 'Error'
        );

        $expected = [
            'success' => false,
            'message' => 'Error',
            'addedProductIds' => [],
            'failedProductIds' => [],
            'addedCount' => 0,
            'failedCount' => 0,
        ];

        $this->assertEquals($expected, $result->toArray());
    }
}
