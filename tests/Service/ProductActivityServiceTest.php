<?php

namespace PromotionEngineBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PromotionEngineBundle\Entity\ActivityProduct;
use PromotionEngineBundle\Entity\TimeLimitActivity;
use PromotionEngineBundle\Enum\ActivityStatus;
use PromotionEngineBundle\Enum\ActivityType;
use PromotionEngineBundle\Repository\ActivityProductRepository;
use PromotionEngineBundle\Service\ProductActivityService;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[RunTestsInSeparateProcesses]
#[CoversClass(ProductActivityService::class)]
final class ProductActivityServiceTest extends AbstractIntegrationTestCase
{
    private ProductActivityService $productActivityService;

    private ActivityProductRepository $activityProductRepository;

    protected function onSetUp(): void
    {
        $this->activityProductRepository = self::getService(ActivityProductRepository::class);
        $this->productActivityService = self::getService(ProductActivityService::class);
    }

    public function testGetProductActivityInfo(): void
    {
        $activityProduct = $this->createTestActivityProduct('activity1', 'product1');

        // Clear entity manager to ensure fresh data from database
        self::getEntityManager()->clear();

        $activityInfo = $this->productActivityService->getProductActivityInfo('product1');

        $this->assertNotNull($activityInfo);
        $this->assertIsArray($activityInfo);
        $this->assertSame('Test Activity activity1', $activityInfo['activityName']);
        $this->assertSame(ActivityType::LIMITED_TIME_SECKILL->value, $activityInfo['activityType']);
        $this->assertSame('99.99', $activityInfo['activityPrice']);
        $this->assertSame(5, $activityInfo['limitPerUser']);
        $this->assertSame(100, $activityInfo['activityStock']);
        $this->assertSame(30, $activityInfo['soldQuantity']);
        $this->assertSame(70, $activityInfo['remainingStock']);
        $this->assertFalse($activityInfo['isSoldOut']);
    }

    public function testGetProductActivityInfoNotFound(): void
    {
        $activityInfo = $this->productActivityService->getProductActivityInfo('nonexistent_product');

        $this->assertNull($activityInfo);
    }

    public function testGetBatchProductActivityInfo(): void
    {
        $this->createTestActivityProduct('activity2', 'product2');
        $this->createTestActivityProduct('activity3', 'product3');

        $batchInfo = $this->productActivityService->getBatchProductActivityInfo(['product2', 'product3', 'nonexistent']);

        $this->assertIsArray($batchInfo);
        $this->assertCount(2, $batchInfo);
        $this->assertArrayHasKey('product2', $batchInfo);
        $this->assertArrayHasKey('product3', $batchInfo);
        $this->assertArrayNotHasKey('nonexistent', $batchInfo);

        $this->assertSame('Test Activity activity2', $batchInfo['product2']['activityName']);
        $this->assertSame('Test Activity activity3', $batchInfo['product3']['activityName']);
    }

    public function testGetBatchProductActivityInfoEmpty(): void
    {
        $batchInfo = $this->productActivityService->getBatchProductActivityInfo([]);

        $this->assertIsArray($batchInfo);
        $this->assertEmpty($batchInfo);
    }

    public function testHasActiveActivity(): void
    {
        $this->createTestActivityProduct('activity4', 'product4');

        $this->assertTrue($this->productActivityService->hasActiveActivity('product4'));
        $this->assertFalse($this->productActivityService->hasActiveActivity('nonexistent_product'));
    }

    public function testFilterProductsWithActiveActivity(): void
    {
        $this->createTestActivityProduct('activity5', 'product5a');
        $this->createTestActivityProduct('activity6', 'product5b');

        $productsWithActivity = $this->productActivityService->filterProductsWithActiveActivity([
            'product5a',
            'product5b',
            'nonexistent',
        ]);

        $this->assertIsArray($productsWithActivity);
        $this->assertCount(2, $productsWithActivity);
        $this->assertContains('product5a', $productsWithActivity);
        $this->assertContains('product5b', $productsWithActivity);
        $this->assertNotContains('nonexistent', $productsWithActivity);
    }

    public function testFilterProductsWithoutActiveActivity(): void
    {
        $this->createTestActivityProduct('activity7', 'product7a');

        $productsWithoutActivity = $this->productActivityService->filterProductsWithoutActiveActivity([
            'product7a',
            'product7b',
            'product7c',
        ]);

        $this->assertIsArray($productsWithoutActivity);
        $this->assertCount(2, $productsWithoutActivity);
        $this->assertNotContains('product7a', $productsWithoutActivity);
        $this->assertContains('product7b', $productsWithoutActivity);
        $this->assertContains('product7c', $productsWithoutActivity);
    }

    public function testGetActivityPrice(): void
    {
        $this->createTestActivityProduct('activity8', 'product8');

        $price = $this->productActivityService->getActivityPrice('product8');
        $this->assertSame('99.99', $price);

        $priceNotFound = $this->productActivityService->getActivityPrice('nonexistent');
        $this->assertNull($priceNotFound);
    }

    public function testGetLimitPerUser(): void
    {
        $this->createTestActivityProduct('activity9', 'product9');

        $limit = $this->productActivityService->getLimitPerUser('product9');
        $this->assertSame(5, $limit);

        $limitNotFound = $this->productActivityService->getLimitPerUser('nonexistent');
        $this->assertNull($limitNotFound);
    }

    public function testGetRemainingStock(): void
    {
        $this->createTestActivityProduct('activity10', 'product10');

        $stock = $this->productActivityService->getRemainingStock('product10');
        $this->assertSame(70, $stock);

        $stockNotFound = $this->productActivityService->getRemainingStock('nonexistent');
        $this->assertNull($stockNotFound);
    }

    public function testIsProductStockAvailable(): void
    {
        $this->createTestActivityProduct('activity11', 'product11');

        $this->assertTrue($this->productActivityService->isProductStockAvailable('product11', 50));
        $this->assertTrue($this->productActivityService->isProductStockAvailable('product11', 70));
        $this->assertFalse($this->productActivityService->isProductStockAvailable('product11', 71));
        $this->assertFalse($this->productActivityService->isProductStockAvailable('nonexistent', 1));
    }

    private function createTestActivityProduct(string $activityId, string $productId): ActivityProduct
    {
        $activity = new TimeLimitActivity();
        $activity->setName('Test Activity ' . $activityId);
        $activity->setStartTime(new \DateTimeImmutable('-1 day'));
        $activity->setEndTime(new \DateTimeImmutable('+1 year'));
        $activity->setActivityType(ActivityType::LIMITED_TIME_SECKILL);
        $activity->setStatus(ActivityStatus::ACTIVE);
        $activity->setValid(true);

        self::getEntityManager()->persist($activity);
        self::getEntityManager()->flush();

        $activityProduct = new ActivityProduct();
        $activityProduct->setActivity($activity);
        $activityProduct->setProductId($productId);
        $activityProduct->setActivityPrice('99.99');
        $activityProduct->setLimitPerUser(5);
        $activityProduct->setActivityStock(100);
        $activityProduct->setSoldQuantity(30);
        $activityProduct->setValid(true);

        $this->activityProductRepository->save($activityProduct, true);

        return $activityProduct;
    }
}
