<?php

namespace PromotionEngineBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PromotionEngineBundle\Entity\ActivityProduct;
use PromotionEngineBundle\Entity\TimeLimitActivity;
use PromotionEngineBundle\Enum\ActivityStatus;
use PromotionEngineBundle\Enum\ActivityType;
use PromotionEngineBundle\Exception\ActivityException;
use PromotionEngineBundle\Repository\ActivityProductRepository;
use PromotionEngineBundle\Service\ActivityStockService;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(ActivityStockService::class)]
#[RunTestsInSeparateProcesses]
final class ActivityStockServiceTest extends AbstractIntegrationTestCase
{
    private ActivityStockService $stockService;

    private TimeLimitActivity $testActivity;

    private ActivityProduct $testActivityProduct;

    protected function onSetUp(): void
    {
        $this->stockService = self::getService(ActivityStockService::class);
        $this->setupTestData();
    }

    private function setupTestData(): void
    {
        // 创建测试活动
        $this->testActivity = new TimeLimitActivity();
        $this->testActivity->setName('测试秒杀活动');
        $this->testActivity->setActivityType(ActivityType::LIMITED_TIME_SECKILL);
        $this->testActivity->setStatus(ActivityStatus::ACTIVE);
        $this->testActivity->setStartTime(new \DateTimeImmutable('now'));
        $this->testActivity->setEndTime(new \DateTimeImmutable('+1 hour'));
        $this->testActivity->setPreheatStartTime(new \DateTimeImmutable('-10 minutes'));
        $this->testActivity->setPreheatEnabled(true);
        $this->testActivity->setValid(true);
        $this->persistAndFlush($this->testActivity);

        // 创建测试商品活动关联
        $this->testActivityProduct = new ActivityProduct();
        $this->testActivityProduct->setActivity($this->testActivity);
        $this->testActivityProduct->setProductId('TEST_PRODUCT_001');
        $this->testActivityProduct->setActivityPrice('99.99');
        $this->testActivityProduct->setActivityStock(100);
        $this->testActivityProduct->setSoldQuantity(0);
        $this->testActivityProduct->setLimitPerUser(5);
        $this->testActivityProduct->setValid(true);
        $this->persistAndFlush($this->testActivityProduct);
    }

    public function testServiceIsInstantiable(): void
    {
        $this->assertInstanceOf(ActivityStockService::class, $this->stockService);
    }

    // checkStockAvailability 相关测试
    public function testCheckStockAvailabilityWithSufficientStock(): void
    {
        $activityId = (string) $this->testActivity->getId();
        $productId = $this->testActivityProduct->getProductId();

        $result = $this->stockService->checkStockAvailability($activityId, $productId, 10);
        $this->assertTrue($result);
    }

    public function testCheckStockAvailabilityWithExactStock(): void
    {
        $activityId = (string) $this->testActivity->getId();
        $productId = $this->testActivityProduct->getProductId();

        $result = $this->stockService->checkStockAvailability($activityId, $productId, 100);
        $this->assertTrue($result);
    }

    public function testCheckStockAvailabilityWithInsufficientStock(): void
    {
        $activityId = (string) $this->testActivity->getId();
        $productId = $this->testActivityProduct->getProductId();

        $result = $this->stockService->checkStockAvailability($activityId, $productId, 101);
        $this->assertFalse($result);
    }

    public function testCheckStockAvailabilityWithNonExistentProduct(): void
    {
        $activityId = (string) $this->testActivity->getId();

        $result = $this->stockService->checkStockAvailability($activityId, 'NON_EXISTENT', 1);
        $this->assertFalse($result);
    }

    public function testCheckStockAvailabilityWithDefaultQuantity(): void
    {
        $activityId = (string) $this->testActivity->getId();
        $productId = $this->testActivityProduct->getProductId();

        $result = $this->stockService->checkStockAvailability($activityId, $productId);
        $this->assertTrue($result);
    }

    public function testCheckStockAvailabilityWithZeroStock(): void
    {
        // 先把库存全部扣光
        $this->testActivityProduct->setSoldQuantity(100);
        $this->persistAndFlush($this->testActivityProduct);

        $activityId = (string) $this->testActivity->getId();
        $productId = $this->testActivityProduct->getProductId();

        $result = $this->stockService->checkStockAvailability($activityId, $productId, 1);
        $this->assertFalse($result);
    }

    // batchCheckStockAvailability 相关测试
    public function testBatchCheckStockAvailabilityWithSufficientStock(): void
    {
        $activityId = (string) $this->testActivity->getId();
        $items = [
            $this->testActivityProduct->getProductId() => 50,
        ];

        $result = $this->stockService->batchCheckStockAvailability($activityId, $items);
        $this->assertTrue($result);
    }

    public function testBatchCheckStockAvailabilityWithInsufficientStock(): void
    {
        $activityId = (string) $this->testActivity->getId();
        $items = [
            $this->testActivityProduct->getProductId() => 150,
        ];

        $result = $this->stockService->batchCheckStockAvailability($activityId, $items);
        $this->assertFalse($result);
    }

    public function testBatchCheckStockAvailabilityWithNonExistentProduct(): void
    {
        $activityId = (string) $this->testActivity->getId();
        $items = [
            'NON_EXISTENT' => 1,
        ];

        $result = $this->stockService->batchCheckStockAvailability($activityId, $items);
        $this->assertFalse($result);
    }

    public function testBatchCheckStockAvailabilityWithEmptyItems(): void
    {
        $activityId = (string) $this->testActivity->getId();
        $items = [];

        $result = $this->stockService->batchCheckStockAvailability($activityId, $items);
        $this->assertTrue($result);
    }

    // decreaseStock 相关测试
    public function testDecreaseStockSuccess(): void
    {
        $activityId = (string) $this->testActivity->getId();
        $productId = $this->testActivityProduct->getProductId();

        $this->stockService->decreaseStock($activityId, $productId, 10);

        // 验证库存变化
        self::getEntityManager()->clear();
        $updatedProduct = self::getService(ActivityProductRepository::class)->findByActivityAndProduct($activityId, $productId);
        $this->assertNotNull($updatedProduct);
        $this->assertEquals(10, $updatedProduct->getSoldQuantity());
        $this->assertEquals(90, $updatedProduct->getRemainingStock());
    }

    public function testDecreaseStockWithDefaultQuantity(): void
    {
        $activityId = (string) $this->testActivity->getId();
        $productId = $this->testActivityProduct->getProductId();

        $this->stockService->decreaseStock($activityId, $productId);

        self::getEntityManager()->clear();
        $updatedProduct = self::getService(ActivityProductRepository::class)->findByActivityAndProduct($activityId, $productId);
        $this->assertNotNull($updatedProduct);
        $this->assertEquals(1, $updatedProduct->getSoldQuantity());
    }

    public function testDecreaseStockWithInvalidQuantity(): void
    {
        $activityId = (string) $this->testActivity->getId();
        $productId = $this->testActivityProduct->getProductId();

        $this->expectException(ActivityException::class);
        $this->expectExceptionMessage('库存扣减数量必须大于0');
        $this->stockService->decreaseStock($activityId, $productId, 0);
    }

    public function testDecreaseStockWithNegativeQuantity(): void
    {
        $activityId = (string) $this->testActivity->getId();
        $productId = $this->testActivityProduct->getProductId();

        $this->expectException(ActivityException::class);
        $this->expectExceptionMessage('库存扣减数量必须大于0');
        $this->stockService->decreaseStock($activityId, $productId, -5);
    }

    public function testDecreaseStockWithNonExistentProduct(): void
    {
        $activityId = (string) $this->testActivity->getId();

        $this->expectException(ActivityException::class);
        $this->expectExceptionMessage('活动商品不存在');
        $this->stockService->decreaseStock($activityId, 'NON_EXISTENT', 1);
    }

    public function testDecreaseStockWithInsufficientStock(): void
    {
        $activityId = (string) $this->testActivity->getId();
        $productId = $this->testActivityProduct->getProductId();

        $this->expectException(ActivityException::class);
        $this->expectExceptionMessage('活动库存不足');
        $this->stockService->decreaseStock($activityId, $productId, 101);
    }

    public function testDecreaseStockTransactionRollback(): void
    {
        $activityId = (string) $this->testActivity->getId();
        $productId = $this->testActivityProduct->getProductId();

        // 故意构造一个会导致事务失败的场景（库存不足）
        try {
            $this->stockService->decreaseStock($activityId, $productId, 101);
        } catch (ActivityException $e) {
            // 预期的异常
        }

        // 验证原始数据未被修改
        self::getEntityManager()->clear();
        $product = self::getService(ActivityProductRepository::class)->findByActivityAndProduct($activityId, $productId);
        $this->assertNotNull($product);
        $this->assertEquals(0, $product->getSoldQuantity()); // 原始值未变
    }

    // increaseStock 相关测试
    public function testIncreaseStockSuccess(): void
    {
        // 先扣减一些库存
        $this->testActivityProduct->setSoldQuantity(20);
        $this->persistAndFlush($this->testActivityProduct);

        $activityId = (string) $this->testActivity->getId();
        $productId = $this->testActivityProduct->getProductId();

        $this->stockService->increaseStock($activityId, $productId, 10);

        self::getEntityManager()->clear();
        $updatedProduct = self::getService(ActivityProductRepository::class)->findByActivityAndProduct($activityId, $productId);
        $this->assertNotNull($updatedProduct);
        $this->assertEquals(10, $updatedProduct->getSoldQuantity());
        $this->assertEquals(90, $updatedProduct->getRemainingStock());
    }

    public function testIncreaseStockWithDefaultQuantity(): void
    {
        $this->testActivityProduct->setSoldQuantity(10);
        $this->persistAndFlush($this->testActivityProduct);

        $activityId = (string) $this->testActivity->getId();
        $productId = $this->testActivityProduct->getProductId();

        $this->stockService->increaseStock($activityId, $productId);

        self::getEntityManager()->clear();
        $updatedProduct = self::getService(ActivityProductRepository::class)->findByActivityAndProduct($activityId, $productId);
        $this->assertNotNull($updatedProduct);
        $this->assertEquals(9, $updatedProduct->getSoldQuantity());
    }

    public function testIncreaseStockToZero(): void
    {
        $this->testActivityProduct->setSoldQuantity(10);
        $this->persistAndFlush($this->testActivityProduct);

        $activityId = (string) $this->testActivity->getId();
        $productId = $this->testActivityProduct->getProductId();

        $this->stockService->increaseStock($activityId, $productId, 10);

        self::getEntityManager()->clear();
        $updatedProduct = self::getService(ActivityProductRepository::class)->findByActivityAndProduct($activityId, $productId);
        $this->assertNotNull($updatedProduct);
        $this->assertEquals(0, $updatedProduct->getSoldQuantity());
    }

    public function testIncreaseStockBeyondSoldQuantity(): void
    {
        $this->testActivityProduct->setSoldQuantity(5);
        $this->persistAndFlush($this->testActivityProduct);

        $activityId = (string) $this->testActivity->getId();
        $productId = $this->testActivityProduct->getProductId();

        // 恢复超过已售数量应该被限制为0
        $this->stockService->increaseStock($activityId, $productId, 10);

        self::getEntityManager()->clear();
        $updatedProduct = self::getService(ActivityProductRepository::class)->findByActivityAndProduct($activityId, $productId);
        $this->assertNotNull($updatedProduct);
        $this->assertEquals(0, $updatedProduct->getSoldQuantity());
    }

    public function testIncreaseStockWithInvalidQuantity(): void
    {
        $activityId = (string) $this->testActivity->getId();
        $productId = $this->testActivityProduct->getProductId();

        $this->expectException(ActivityException::class);
        $this->expectExceptionMessage('库存增加数量必须大于0');
        $this->stockService->increaseStock($activityId, $productId, 0);
    }

    public function testIncreaseStockWithNonExistentProduct(): void
    {
        $activityId = (string) $this->testActivity->getId();

        $this->expectException(ActivityException::class);
        $this->expectExceptionMessage('活动商品不存在');
        $this->stockService->increaseStock($activityId, 'NON_EXISTENT', 1);
    }

    // batchDecreaseStock 相关测试
    public function testBatchDecreaseStockSuccess(): void
    {
        $activityId = (string) $this->testActivity->getId();
        $items = [
            $this->testActivityProduct->getProductId() => 50,
        ];

        $this->stockService->batchDecreaseStock($activityId, $items);

        self::getEntityManager()->clear();
        $updatedProduct = self::getService(ActivityProductRepository::class)->findByActivityAndProduct(
            $activityId,
            $this->testActivityProduct->getProductId()
        );
        $this->assertNotNull($updatedProduct);
        $this->assertEquals(50, $updatedProduct->getSoldQuantity());
    }

    public function testBatchDecreaseStockWithEmptyItems(): void
    {
        $activityId = (string) $this->testActivity->getId();
        $items = [];

        // 空数组不应该抛出异常
        $this->stockService->batchDecreaseStock($activityId, $items);

        // 验证数据未变化
        self::getEntityManager()->clear();
        $product = self::getService(ActivityProductRepository::class)->findByActivityAndProduct(
            $activityId,
            $this->testActivityProduct->getProductId()
        );
        $this->assertNotNull($product);
        $this->assertEquals(0, $product->getSoldQuantity());
    }

    public function testBatchDecreaseStockWithNonExistentProduct(): void
    {
        $activityId = (string) $this->testActivity->getId();
        $items = [
            'NON_EXISTENT' => 1,
        ];

        $this->expectException(ActivityException::class);
        $this->expectExceptionMessage('活动商品不存在: NON_EXISTENT');
        $this->stockService->batchDecreaseStock($activityId, $items);
    }

    public function testBatchDecreaseStockWithInsufficientStock(): void
    {
        $activityId = (string) $this->testActivity->getId();
        $items = [
            $this->testActivityProduct->getProductId() => 150,
        ];

        $this->expectException(ActivityException::class);
        $this->expectExceptionMessage('活动库存不足');
        $this->stockService->batchDecreaseStock($activityId, $items);
    }

    public function testBatchDecreaseStockTransactionRollback(): void
    {
        $activityId = (string) $this->testActivity->getId();
        $items = [
            $this->testActivityProduct->getProductId() => 150, // 超过库存
        ];

        try {
            $this->stockService->batchDecreaseStock($activityId, $items);
        } catch (ActivityException $e) {
            // 预期异常
        }

        // 验证事务回滚，数据未被修改
        self::getEntityManager()->clear();
        $product = self::getService(ActivityProductRepository::class)->findByActivityAndProduct(
            $activityId,
            $this->testActivityProduct->getProductId()
        );
        $this->assertNotNull($product);
        $this->assertEquals(0, $product->getSoldQuantity());
    }

    // batchIncreaseStock 相关测试
    public function testBatchIncreaseStockSuccess(): void
    {
        // 先扣减库存
        $this->testActivityProduct->setSoldQuantity(60);
        $this->persistAndFlush($this->testActivityProduct);

        $activityId = (string) $this->testActivity->getId();
        $items = [
            $this->testActivityProduct->getProductId() => 30,
        ];

        $this->stockService->batchIncreaseStock($activityId, $items);

        self::getEntityManager()->clear();
        $updatedProduct = self::getService(ActivityProductRepository::class)->findByActivityAndProduct(
            $activityId,
            $this->testActivityProduct->getProductId()
        );
        $this->assertNotNull($updatedProduct);
        $this->assertEquals(30, $updatedProduct->getSoldQuantity());
    }

    public function testBatchIncreaseStockWithEmptyItems(): void
    {
        $activityId = (string) $this->testActivity->getId();
        $items = [];

        $this->stockService->batchIncreaseStock($activityId, $items);

        // 验证数据未变化
        self::getEntityManager()->clear();
        $product = self::getService(ActivityProductRepository::class)->findByActivityAndProduct(
            $activityId,
            $this->testActivityProduct->getProductId()
        );
        $this->assertNotNull($product);
        $this->assertEquals(0, $product->getSoldQuantity());
    }

    public function testBatchIncreaseStockWithNonExistentProduct(): void
    {
        $activityId = (string) $this->testActivity->getId();
        $items = [
            'NON_EXISTENT' => 1,
        ];

        $this->expectException(ActivityException::class);
        $this->expectExceptionMessage('活动商品不存在: NON_EXISTENT');
        $this->stockService->batchIncreaseStock($activityId, $items);
    }

    public function testBatchIncreaseStockBeyondSoldQuantity(): void
    {
        $this->testActivityProduct->setSoldQuantity(10);
        $this->persistAndFlush($this->testActivityProduct);

        $activityId = (string) $this->testActivity->getId();
        $items = [
            $this->testActivityProduct->getProductId() => 20, // 超过已售数量
        ];

        $this->stockService->batchIncreaseStock($activityId, $items);

        // 应该被限制为0
        self::getEntityManager()->clear();
        $updatedProduct = self::getService(ActivityProductRepository::class)->findByActivityAndProduct(
            $activityId,
            $this->testActivityProduct->getProductId()
        );
        $this->assertNotNull($updatedProduct);
        $this->assertEquals(0, $updatedProduct->getSoldQuantity());
    }

    public function testBatchIncreaseStockTransactionRollback(): void
    {
        $this->testActivityProduct->setSoldQuantity(50);
        $this->persistAndFlush($this->testActivityProduct);

        $activityId = (string) $this->testActivity->getId();
        $items = [
            'NON_EXISTENT' => 1, // 不存在的商品会导致异常
        ];

        try {
            $this->stockService->batchIncreaseStock($activityId, $items);
        } catch (ActivityException $e) {
            // 预期异常
        }

        // 验证事务回滚
        self::getEntityManager()->clear();
        $product = self::getService(ActivityProductRepository::class)->findByActivityAndProduct(
            $activityId,
            $this->testActivityProduct->getProductId()
        );
        $this->assertNotNull($product);
        $this->assertEquals(50, $product->getSoldQuantity()); // 原始值未变
    }

    // 综合测试
    public function testMixedStockOperationsConsistency(): void
    {
        $activityId = (string) $this->testActivity->getId();
        $productId = $this->testActivityProduct->getProductId();

        // 1. 扣减库存
        $this->stockService->decreaseStock($activityId, $productId, 30);
        $this->assertTrue($this->stockService->checkStockAvailability($activityId, $productId, 70));
        $this->assertFalse($this->stockService->checkStockAvailability($activityId, $productId, 71));

        // 2. 恢复部分库存
        $this->stockService->increaseStock($activityId, $productId, 10);
        $this->assertTrue($this->stockService->checkStockAvailability($activityId, $productId, 80));

        // 3. 批量操作
        $this->stockService->batchDecreaseStock($activityId, [$productId => 20]);
        $this->assertTrue($this->stockService->checkStockAvailability($activityId, $productId, 60));

        // 验证最终状态
        self::getEntityManager()->clear();
        $finalProduct = self::getService(ActivityProductRepository::class)->findByActivityAndProduct($activityId, $productId);
        $this->assertNotNull($finalProduct);
        $this->assertEquals(40, $finalProduct->getSoldQuantity());
        $this->assertEquals(60, $finalProduct->getRemainingStock());
    }

    public function testBatchOperationsConsistency(): void
    {
        $activityId = (string) $this->testActivity->getId();
        $productId = $this->testActivityProduct->getProductId();

        $items = [$productId => 25];

        // 批量检查
        $this->assertTrue($this->stockService->batchCheckStockAvailability($activityId, $items));

        // 批量扣减
        $this->stockService->batchDecreaseStock($activityId, $items);
        $this->assertFalse($this->stockService->batchCheckStockAvailability($activityId, [$productId => 76]));
        $this->assertTrue($this->stockService->batchCheckStockAvailability($activityId, [$productId => 75]));

        // 批量恢复
        $this->stockService->batchIncreaseStock($activityId, [$productId => 10]);
        $this->assertTrue($this->stockService->batchCheckStockAvailability($activityId, [$productId => 85]));

        // 验证最终状态
        self::getEntityManager()->clear();
        $finalProduct = self::getService(ActivityProductRepository::class)->findByActivityAndProduct($activityId, $productId);
        $this->assertNotNull($finalProduct);
        $this->assertEquals(15, $finalProduct->getSoldQuantity());
        $this->assertEquals(85, $finalProduct->getRemainingStock());
    }
}
