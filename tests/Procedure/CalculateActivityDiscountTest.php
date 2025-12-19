<?php

declare(strict_types=1);

namespace PromotionEngineBundle\Tests\Procedure;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PromotionEngineBundle\Param\CalculateActivityDiscountParam;
use PromotionEngineBundle\Procedure\CalculateActivityDiscount;
use Tourze\PHPUnitJsonRPC\AbstractProcedureTestCase;

/**
 * @internal
 */
#[CoversClass(CalculateActivityDiscount::class)]
#[RunTestsInSeparateProcesses]
final class CalculateActivityDiscountTest extends AbstractProcedureTestCase
{
    private CalculateActivityDiscount $procedure;

    protected function onSetUp(): void
    {
        $this->procedure = self::getService(CalculateActivityDiscount::class);
    }

    public function testSuccessfulCalculateDiscount(): void
    {
        $param = new CalculateActivityDiscountParam(
            items: [
                [
                    'productId' => 'product_1',
                    'skuId' => 'sku_1',
                    'quantity' => 2,
                    'price' => 100.0,
                ],
            ],
            userId: 'user_123',
        );

        $result = $this->procedure->execute($param);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('items', $result);
        $this->assertArrayHasKey('originalTotalAmount', $result);
        $this->assertArrayHasKey('finalTotalAmount', $result);
    }

    public function testEmptyItemsList(): void
    {
        $param = new CalculateActivityDiscountParam(
            items: [],
        );

        $result = $this->procedure->execute($param);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('message', $result);
        $this->assertIsString($result['message']);
        $this->assertStringContainsString('商品列表为空', $result['message']);
    }

    public function testBuildInputWithValidData(): void
    {
        $param = new CalculateActivityDiscountParam(
            items: [
                [
                    'productId' => 'product_1',
                    'skuId' => 'sku_1',
                    'quantity' => 1,
                    'price' => 50.0,
                ],
                [
                    'productId' => 'product_2',
                    'skuId' => 'sku_2',
                    'quantity' => 3,
                    'price' => 30.0,
                ],
            ],
            userId: 'user_456',
        );

        $result = $this->procedure->execute($param);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('items', $result);
        $this->assertIsArray($result['items']);
        $this->assertCount(2, $result['items']);
    }

    public function testExecuteMethodDirectly(): void
    {
        $param = new CalculateActivityDiscountParam(
            items: [
                [
                    'productId' => 'direct_product_1',
                    'skuId' => 'direct_sku_1',
                    'quantity' => 1,
                    'price' => 80.0,
                ],
            ],
            userId: 'direct_user_123',
        );

        // 直接调用execute()方法
        $result = $this->procedure->execute($param);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('items', $result);
        $this->assertArrayHasKey('originalTotalAmount', $result);
        $this->assertArrayHasKey('finalTotalAmount', $result);

        // 验证返回结果的基本结构
        if (true === $result['success']) {
            $this->assertIsArray($result['items']);
            $this->assertIsNumeric($result['originalTotalAmount']);
            $this->assertIsNumeric($result['finalTotalAmount']);
        }
    }

    public function testExecuteWithEmptyItemsReturnError(): void
    {
        $param = new CalculateActivityDiscountParam(
            items: [],
            userId: 'test_user',
        );

        // 直接调用execute()方法测试空商品列表情况
        $result = $this->procedure->execute($param);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('message', $result);
        $this->assertIsString($result['message']);
        $this->assertStringContainsString('商品列表为空', $result['message']);
    }
}
