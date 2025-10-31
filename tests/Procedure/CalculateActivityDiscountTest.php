<?php

namespace PromotionEngineBundle\Tests\Procedure;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PromotionEngineBundle\Procedure\CalculateActivityDiscount;
use Tourze\JsonRPC\Core\Tests\AbstractProcedureTestCase;

/**
 * @internal
 */
#[CoversClass(CalculateActivityDiscount::class)]
#[RunTestsInSeparateProcesses]
class CalculateActivityDiscountTest extends AbstractProcedureTestCase
{
    private CalculateActivityDiscount $procedure;

    protected function onSetUp(): void
    {
        $this->procedure = self::getService(CalculateActivityDiscount::class);
    }

    public function testSuccessfulCalculateDiscount(): void
    {
        $this->procedure->items = [
            [
                'productId' => 'product_1',
                'skuId' => 'sku_1',
                'quantity' => 2,
                'price' => 100.0,
            ],
        ];
        $this->procedure->userId = 'user_123';

        $result = $this->procedure->execute();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('items', $result);
        $this->assertArrayHasKey('originalTotalAmount', $result);
        $this->assertArrayHasKey('finalTotalAmount', $result);
    }

    public function testEmptyItemsList(): void
    {
        $this->procedure->items = [];
        $this->procedure->userId = null;

        $result = $this->procedure->execute();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('message', $result);
        $this->assertStringContainsString('商品列表为空', $result['message']);
    }

    public function testBuildInputWithValidData(): void
    {
        $this->procedure->items = [
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
        ];
        $this->procedure->userId = 'user_456';

        $result = $this->procedure->execute();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('items', $result);
        $this->assertCount(2, $result['items']);
    }

    public function testExecuteMethodDirectly(): void
    {
        $this->procedure->items = [
            [
                'productId' => 'direct_product_1',
                'skuId' => 'direct_sku_1',
                'quantity' => 1,
                'price' => 80.0,
            ],
        ];
        $this->procedure->userId = 'direct_user_123';

        // 直接调用execute()方法
        $result = $this->procedure->execute();

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
        $this->procedure->items = [];
        $this->procedure->userId = 'test_user';

        // 直接调用execute()方法测试空商品列表情况
        $result = $this->procedure->execute();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('message', $result);
        $this->assertStringContainsString('商品列表为空', $result['message']);
    }

    public function testGetMockResult(): void
    {
        $mockResult = CalculateActivityDiscount::getMockResult();

        $this->assertIsArray($mockResult);
        $this->assertArrayHasKey('success', $mockResult);
        $this->assertTrue($mockResult['success']);
        $this->assertArrayHasKey('items', $mockResult);
        $this->assertArrayHasKey('originalTotalAmount', $mockResult);
        $this->assertArrayHasKey('finalTotalAmount', $mockResult);
        $this->assertArrayHasKey('appliedActivities', $mockResult);
    }
}
