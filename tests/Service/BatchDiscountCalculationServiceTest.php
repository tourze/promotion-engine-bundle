<?php

namespace PromotionEngineBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PromotionEngineBundle\DTO\CalculateActivityDiscountInput;
use PromotionEngineBundle\DTO\CalculateActivityDiscountItem;
use PromotionEngineBundle\Service\BatchDiscountCalculationService;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[RunTestsInSeparateProcesses]
#[CoversClass(BatchDiscountCalculationService::class)]
class BatchDiscountCalculationServiceTest extends AbstractIntegrationTestCase
{
    private BatchDiscountCalculationService $service;

    protected function onSetUp(): void
    {
        $this->service = self::getService(BatchDiscountCalculationService::class);
    }

    public function testBatchCalculateDiscounts(): void
    {
        $items = [
            new CalculateActivityDiscountItem(
                productId: 'product_1',
                skuId: 'sku_1',
                quantity: 2,
                price: 100.0
            ),
            new CalculateActivityDiscountItem(
                productId: 'product_2',
                skuId: 'sku_2',
                quantity: 1,
                price: 50.0
            ),
        ];

        $input = new CalculateActivityDiscountInput(
            items: $items,
            userId: 'user_123'
        );

        $result = $this->service->batchCalculateDiscounts([$input]);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertArrayHasKey(0, $result);
    }

    public function testBatchCalculateDiscountsWithEmptyInput(): void
    {
        $result = $this->service->batchCalculateDiscounts([]);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testBatchCalculateDiscountsWithMultipleInputs(): void
    {
        $inputs = [];
        for ($i = 1; $i <= 3; ++$i) {
            $items = [
                new CalculateActivityDiscountItem(
                    productId: "product_{$i}",
                    skuId: "sku_{$i}",
                    quantity: 1,
                    price: 100.0
                ),
            ];

            $inputs[] = new CalculateActivityDiscountInput(
                items: $items,
                userId: "user_{$i}"
            );
        }

        $result = $this->service->batchCalculateDiscounts($inputs);

        $this->assertIsArray($result);
        $this->assertCount(3, $result);
    }

    public function testOptimizeInputsForBatch(): void
    {
        $items = [
            new CalculateActivityDiscountItem(
                productId: 'product_1',
                skuId: 'sku_1',
                quantity: 5,
                price: 200.0
            ),
        ];

        $input = new CalculateActivityDiscountInput(
            items: $items,
            userId: 'user_456'
        );

        $result = $this->service->optimizeInputsForBatch([$input]);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertContainsOnlyInstancesOf(CalculateActivityDiscountInput::class, $result);
    }

    public function testCalculateDiscountsAsync(): void
    {
        $items = [
            new CalculateActivityDiscountItem(
                productId: 'product_1',
                skuId: 'sku_1',
                quantity: 2,
                price: 100.0
            ),
        ];

        $input = new CalculateActivityDiscountInput(
            items: $items,
            userId: 'user_async'
        );

        $jobId = $this->service->calculateDiscountsAsync([$input], 'http://callback.example.com');

        $this->assertIsString($jobId);
        $this->assertStringStartsWith('batch_discount_', $jobId);
        $this->assertNotEmpty($jobId);
    }

    public function testCalculateDiscountsAsyncWithoutCallback(): void
    {
        $items = [
            new CalculateActivityDiscountItem(
                productId: 'product_2',
                skuId: 'sku_2',
                quantity: 1,
                price: 50.0
            ),
        ];

        $input = new CalculateActivityDiscountInput(
            items: $items,
            userId: 'user_no_callback'
        );

        $jobId = $this->service->calculateDiscountsAsync([$input]);

        $this->assertIsString($jobId);
        $this->assertStringStartsWith('batch_discount_', $jobId);
    }

    public function testEstimateBatchProcessingTime(): void
    {
        $inputCount = 100;
        $estimate = $this->service->estimateBatchProcessingTime($inputCount);

        $this->assertIsArray($estimate);
        $this->assertArrayHasKey('inputCount', $estimate);
        $this->assertArrayHasKey('batchCount', $estimate);
        $this->assertArrayHasKey('estimatedTimeMs', $estimate);
        $this->assertArrayHasKey('estimatedTimeSec', $estimate);
        $this->assertArrayHasKey('avgTimePerInputMs', $estimate);

        $this->assertSame($inputCount, $estimate['inputCount']);
        $this->assertGreaterThan(0, $estimate['batchCount']);
        $this->assertGreaterThan(0, $estimate['estimatedTimeMs']);
        $this->assertGreaterThan(0, $estimate['estimatedTimeSec']);
        $this->assertSame(50, $estimate['avgTimePerInputMs']); // 基于源码中的常量
    }

    public function testEstimateBatchProcessingTimeWithZeroInput(): void
    {
        $estimate = $this->service->estimateBatchProcessingTime(0);

        $this->assertSame(0, $estimate['inputCount']);
        $this->assertSame(0.0, $estimate['batchCount']);
        $this->assertSame(0.0, $estimate['estimatedTimeMs']);
        $this->assertSame(0.0, $estimate['estimatedTimeSec']);
    }

    public function testValidateBatchSize(): void
    {
        $smallBatch = [];
        for ($i = 0; $i < 5; ++$i) {
            $smallBatch[] = new CalculateActivityDiscountInput(
                items: [new CalculateActivityDiscountItem('prod' . $i, 'sku' . $i, 1, 10.0)],
                userId: 'user' . $i
            );
        }

        $validation = $this->service->validateBatchSize($smallBatch);

        $this->assertIsArray($validation);
        $this->assertArrayHasKey('valid', $validation);
        $this->assertArrayHasKey('warnings', $validation);
        $this->assertArrayHasKey('recommendations', $validation);

        $this->assertTrue($validation['valid']);
        $this->assertIsArray($validation['warnings']);
        $this->assertIsArray($validation['recommendations']);
        $this->assertContains('批量输入数量较小，考虑使用单次计算', $validation['recommendations']);
    }

    public function testValidateBatchSizeWithLargeBatch(): void
    {
        $largeBatch = [];
        for ($i = 0; $i < 1500; ++$i) {
            $largeBatch[] = new CalculateActivityDiscountInput(
                items: [new CalculateActivityDiscountItem('prod' . $i, 'sku' . $i, 1, 10.0)],
                userId: 'user' . $i
            );
        }

        $validation = $this->service->validateBatchSize($largeBatch);

        $this->assertTrue($validation['valid']); // 虽然数量大，但还是有效的
        $this->assertIsArray($validation['warnings']);
        $this->assertContains('批量输入数量较大，建议分批处理', $validation['warnings']);
    }

    public function testOptimizeInputsForBatchWithFrequencyOrdering(): void
    {
        $inputs = [
            new CalculateActivityDiscountInput(
                items: [new CalculateActivityDiscountItem('rare_product', 'sku1', 1, 100.0)],
                userId: 'user1'
            ),
            new CalculateActivityDiscountInput(
                items: [
                    new CalculateActivityDiscountItem('common_product', 'sku2', 2, 50.0),
                    new CalculateActivityDiscountItem('common_product', 'sku3', 1, 30.0),
                ],
                userId: 'user2'
            ),
            new CalculateActivityDiscountInput(
                items: [new CalculateActivityDiscountItem('common_product', 'sku4', 1, 40.0)],
                userId: 'user3'
            ),
        ];

        $optimized = $this->service->optimizeInputsForBatch($inputs);

        $this->assertCount(3, $optimized);
        $this->assertInstanceOf(CalculateActivityDiscountInput::class, $optimized[0]);

        // 验证包含更多常见商品的输入排在前面（基于频率优化）
        $firstInputProductIds = $optimized[0]->getProductIds();
        $this->assertContains('common_product', $firstInputProductIds);
    }
}
