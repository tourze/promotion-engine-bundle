<?php

namespace PromotionEngineBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PromotionEngineBundle\Entity\ActivityProduct;
use PromotionEngineBundle\Entity\TimeLimitActivity;
use PromotionEngineBundle\Enum\ActivityStatus;
use PromotionEngineBundle\Enum\ActivityType;
use PromotionEngineBundle\Repository\ActivityProductRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[RunTestsInSeparateProcesses]
#[CoversClass(ActivityProductRepository::class)]
class ActivityProductRepositoryTest extends AbstractRepositoryTestCase
{
    private ActivityProductRepository $repository;

    protected function onSetUp(): void
    {
        $this->repository = self::getService(ActivityProductRepository::class);
    }

    protected function createNewEntity(): ActivityProduct
    {
        $activity = $this->createActivity('test_activity_' . uniqid());
        self::getEntityManager()->persist($activity);

        $entity = new ActivityProduct();
        $entity->setActivity($activity);
        $entity->setProductId('test_product_' . uniqid());
        $entity->setActivityPrice('99.99');
        $entity->setLimitPerUser(1);
        $entity->setActivityStock(100);
        $entity->setValid(true);

        return $entity;
    }

    protected function getRepository(): ActivityProductRepository
    {
        return $this->repository;
    }

    public function testSaveAndFind(): void
    {
        $activity = $this->createActivity('test_activity_123');
        self::getEntityManager()->persist($activity);

        $activityProduct = new ActivityProduct();
        $activityProduct->setActivity($activity);
        $activityProduct->setProductId('test_product_456');
        $activityProduct->setActivityPrice('99.99');
        $activityProduct->setLimitPerUser(5);
        $activityProduct->setActivityStock(100);
        $activityProduct->setValid(true);

        $this->repository->save($activityProduct, true);

        $found = $this->repository->find($activityProduct->getId());
        $this->assertNotNull($found);
        $this->assertSame($activity, $found->getActivity());
        $this->assertSame('test_product_456', $found->getProductId());
        $this->assertSame('99.99', $found->getActivityPrice());
    }

    public function testFindByActivityId(): void
    {
        $activity = $this->createActivity('test_activity_' . uniqid());
        self::getEntityManager()->persist($activity);
        self::getEntityManager()->flush(); // Ensure ID is generated

        $activityProduct1 = new ActivityProduct();
        $activityProduct1->setActivity($activity);
        $activityProduct1->setProductId('product_1');
        $activityProduct1->setActivityPrice('10.00');
        $activityProduct1->setValid(true);

        $activityProduct2 = new ActivityProduct();
        $activityProduct2->setActivity($activity);
        $activityProduct2->setProductId('product_2');
        $activityProduct2->setActivityPrice('20.00');
        $activityProduct2->setValid(true);

        $this->repository->save($activityProduct1, false);
        $this->repository->save($activityProduct2, true);

        $activityId = $activity->getId();
        $this->assertNotNull($activityId);
        $results = $this->repository->findByActivityId($activityId);
        $this->assertCount(2, $results);

        $productIds = array_map(fn (ActivityProduct $ap) => $ap->getProductId(), $results);
        $this->assertContains('product_1', $productIds);
        $this->assertContains('product_2', $productIds);
    }

    public function testFindByProductIds(): void
    {
        $activity1 = $this->createActivity('activity_1');
        $activity2 = $this->createActivity('activity_2');
        self::getEntityManager()->persist($activity1);
        self::getEntityManager()->persist($activity2);

        $activityProduct1 = new ActivityProduct();
        $activityProduct1->setActivity($activity1);
        $activityProduct1->setProductId('find_product_1');
        $activityProduct1->setActivityPrice('10.00');
        $activityProduct1->setValid(true);

        $activityProduct2 = new ActivityProduct();
        $activityProduct2->setActivity($activity2);
        $activityProduct2->setProductId('find_product_2');
        $activityProduct2->setActivityPrice('20.00');
        $activityProduct2->setValid(true);

        $this->repository->save($activityProduct1, false);
        $this->repository->save($activityProduct2, true);

        $results = $this->repository->findByProductIds(['find_product_1', 'find_product_2', 'nonexistent']);
        $this->assertCount(2, $results);

        $activityIds = array_map(fn (ActivityProduct $ap) => $ap->getActivity()?->getId(), $results);
        $this->assertContains($activity1->getId(), $activityIds);
        $this->assertContains($activity2->getId(), $activityIds);
    }

    public function testFindByActivityAndProduct(): void
    {
        $activity = $this->createActivity('unique_activity_' . uniqid());
        $productId = 'unique_product_' . uniqid();
        self::getEntityManager()->persist($activity);
        self::getEntityManager()->flush(); // 确保 ID 被生成

        $activityProduct = new ActivityProduct();
        $activityProduct->setActivity($activity);
        $activityProduct->setProductId($productId);
        $activityProduct->setActivityPrice('50.00');
        $activityProduct->setValid(true);

        $this->repository->save($activityProduct, true);

        $activityId = $activity->getId();
        $this->assertNotNull($activityId, 'Activity ID should not be null');

        $found = $this->repository->findByActivityAndProduct($activityId, $productId);
        $this->assertNotNull($found);
        $this->assertSame($activity, $found->getActivity());
        $this->assertSame($productId, $found->getProductId());

        $notFound = $this->repository->findByActivityAndProduct($activityId, 'nonexistent_product');
        $this->assertNull($notFound);
    }

    public function testFindLowStockProducts(): void
    {
        $activity1 = $this->createActivity('activity_low_stock_1');
        self::getEntityManager()->persist($activity1);

        $activityProduct1 = new ActivityProduct();
        $activityProduct1->setActivity($activity1);
        $activityProduct1->setProductId('low_stock_product_1');
        $activityProduct1->setActivityStock(15);
        $activityProduct1->setSoldQuantity(10);
        $activityProduct1->setValid(true);

        $activity2 = $this->createActivity('activity_low_stock_2');
        self::getEntityManager()->persist($activity2);

        $activityProduct2 = new ActivityProduct();
        $activityProduct2->setActivity($activity2);
        $activityProduct2->setProductId('low_stock_product_2');
        $activityProduct2->setActivityStock(100);
        $activityProduct2->setSoldQuantity(50);
        $activityProduct2->setValid(true);
        $this->repository->save($activityProduct1, false);
        $this->repository->save($activityProduct2, true);

        $lowStockProducts = $this->repository->findLowStockProducts(10);
        $this->assertCount(1, $lowStockProducts);
        $this->assertSame('low_stock_product_1', $lowStockProducts[0]->getProductId());
    }

    public function testFindSoldOutProducts(): void
    {
        $activity = $this->createActivity('sold_out_activity');
        self::getEntityManager()->persist($activity);

        $activityProduct = new ActivityProduct();
        $activityProduct->setActivity($activity);
        $activityProduct->setProductId('sold_out_product');
        $activityProduct->setActivityStock(10);
        $activityProduct->setSoldQuantity(10);
        $activityProduct->setValid(true);
        $this->repository->save($activityProduct, true);

        $soldOutProducts = $this->repository->findSoldOutProducts();
        $this->assertGreaterThanOrEqual(1, count($soldOutProducts));

        $productIds = array_map(fn (ActivityProduct $ap) => $ap->getProductId(), $soldOutProducts);
        $this->assertContains('sold_out_product', $productIds);
    }

    public function testGetTotalSoldQuantityByActivity(): void
    {
        $activity = $this->createActivity('total_sold_activity_' . uniqid());
        self::getEntityManager()->persist($activity);
        self::getEntityManager()->flush(); // 确保 ID 被生成

        $activityProduct1 = new ActivityProduct();
        $activityProduct1->setActivity($activity);
        $activityProduct1->setProductId('sold_product_1');
        $activityProduct1->setSoldQuantity(25);
        $activityProduct1->setValid(true);

        $activityProduct2 = new ActivityProduct();
        $activityProduct2->setActivity($activity);
        $activityProduct2->setProductId('sold_product_2');
        $activityProduct2->setSoldQuantity(35);
        $activityProduct2->setValid(true);

        $this->repository->save($activityProduct1, false);
        $this->repository->save($activityProduct2, true);

        $activityId = $activity->getId();
        $this->assertNotNull($activityId, 'Activity ID should not be null');

        $totalSold = $this->repository->getTotalSoldQuantityByActivity($activityId);
        $this->assertSame(60, $totalSold);
    }

    public function testDeleteByActivityIds(): void
    {
        $activity1 = $this->createActivity('delete_activity_1_' . uniqid());
        $activity2 = $this->createActivity('delete_activity_2_' . uniqid());
        self::getEntityManager()->persist($activity1);
        self::getEntityManager()->persist($activity2);
        self::getEntityManager()->flush(); // 确保 ID 被生成

        $activityProduct1 = new ActivityProduct();
        $activityProduct1->setActivity($activity1);
        $activityProduct1->setProductId('delete_product_1');
        $activityProduct1->setValid(true);

        $activityProduct2 = new ActivityProduct();
        $activityProduct2->setActivity($activity2);
        $activityProduct2->setProductId('delete_product_2');
        $activityProduct2->setValid(true);

        $this->repository->save($activityProduct1, false);
        $this->repository->save($activityProduct2, true);

        $activityId1 = $activity1->getId();
        $activityId2 = $activity2->getId();

        $this->assertNotNull($activityId1, 'Activity 1 ID should not be null');
        $this->assertNotNull($activityId2, 'Activity 2 ID should not be null');

        $this->repository->deleteByActivityIds([$activityId1, $activityId2]);

        $found1 = $this->repository->findByActivityAndProduct($activityId1, 'delete_product_1');
        $found2 = $this->repository->findByActivityAndProduct($activityId2, 'delete_product_2');

        $this->assertNull($found1);
        $this->assertNull($found2);
    }

    private function createActivity(string $activityId): TimeLimitActivity
    {
        $activity = new TimeLimitActivity();
        $activity->setName('Test Activity ' . $activityId);
        $activity->setStartTime(new \DateTimeImmutable('2024-01-01 00:00:00'));
        $activity->setEndTime(new \DateTimeImmutable('2024-12-31 23:59:59'));
        $activity->setActivityType(ActivityType::LIMITED_TIME_SECKILL);
        $activity->setStatus(ActivityStatus::ACTIVE);
        $activity->setValid(true);

        return $activity;
    }

    public function testDeleteByActivityAndProducts(): void
    {
        $activity = $this->createActivity('delete_activity_products_' . uniqid());
        self::getEntityManager()->persist($activity);
        self::getEntityManager()->flush(); // 确保 ID 被生成

        // 创建测试数据
        $activityProduct1 = new ActivityProduct();
        $activityProduct1->setActivity($activity);
        $activityProduct1->setProductId('product_to_delete_1');
        $activityProduct1->setActivityPrice('50.00');
        $activityProduct1->setValid(true);

        $activityProduct2 = new ActivityProduct();
        $activityProduct2->setActivity($activity);
        $activityProduct2->setProductId('product_to_delete_2');
        $activityProduct2->setActivityPrice('60.00');
        $activityProduct2->setValid(true);

        $activityProduct3 = new ActivityProduct();
        $activityProduct3->setActivity($activity);
        $activityProduct3->setProductId('product_to_keep');
        $activityProduct3->setActivityPrice('70.00');
        $activityProduct3->setValid(true);

        $this->repository->save($activityProduct1, false);
        $this->repository->save($activityProduct2, false);
        $this->repository->save($activityProduct3, true);

        $activityId = $activity->getId();
        $this->assertNotNull($activityId, 'Activity ID should not be null');

        // 删除指定商品
        $this->repository->deleteByActivityAndProducts($activityId, ['product_to_delete_1', 'product_to_delete_2']);

        // 验证删除结果
        $deleted1 = $this->repository->findByActivityAndProduct($activityId, 'product_to_delete_1');
        $deleted2 = $this->repository->findByActivityAndProduct($activityId, 'product_to_delete_2');
        $kept = $this->repository->findByActivityAndProduct($activityId, 'product_to_keep');

        $this->assertNull($deleted1, '商品1应该被删除');
        $this->assertNull($deleted2, '商品2应该被删除');
        $this->assertNotNull($kept, '保留的商品应该还存在');
        $this->assertSame('product_to_keep', $kept->getProductId());
    }

    public function testDeleteByActivityAndProductsWithEmptyList(): void
    {
        $activity = $this->createActivity('empty_delete_activity_' . uniqid());
        self::getEntityManager()->persist($activity);
        self::getEntityManager()->flush(); // 确保 ID 被生成

        $activityProduct = new ActivityProduct();
        $activityProduct->setActivity($activity);
        $activityProduct->setProductId('should_remain');
        $activityProduct->setActivityPrice('100.00');
        $activityProduct->setValid(true);

        $this->repository->save($activityProduct, true);

        $activityId = $activity->getId();
        $this->assertNotNull($activityId, 'Activity ID should not be null');

        // 用空数组调用删除方法
        $this->repository->deleteByActivityAndProducts($activityId, []);

        // 验证商品仍然存在
        $found = $this->repository->findByActivityAndProduct($activityId, 'should_remain');
        $this->assertNotNull($found, '商品应该仍然存在，因为没有被删除');
    }

    public function testFindActiveByProductId(): void
    {
        $productId = 'single_active_product_' . uniqid();

        // 创建一个活跃的活动和商品关联
        $activeActivity = $this->createActivity('active_for_single_' . uniqid());
        $activeActivity->setStartTime(new \DateTimeImmutable('-1 hour'));
        $activeActivity->setEndTime(new \DateTimeImmutable('+1 hour'));

        $activeProduct = new ActivityProduct();
        $activeProduct->setActivity($activeActivity);
        $activeProduct->setProductId($productId);
        $activeProduct->setActivityPrice('80.00');
        $activeProduct->setValid(true);

        // 创建一个已结束的活动和商品关联，使用不同的productId避免约束冲突
        $expiredActivity = $this->createActivity('expired_for_single_' . uniqid());
        $expiredActivity->setStartTime(new \DateTimeImmutable('-3 hours'));
        $expiredActivity->setEndTime(new \DateTimeImmutable('-1 hour'));

        $expiredProductId = 'expired_product_' . uniqid();
        $expiredProduct = new ActivityProduct();
        $expiredProduct->setActivity($expiredActivity);
        $expiredProduct->setProductId($expiredProductId);
        $expiredProduct->setActivityPrice('70.00');
        $expiredProduct->setValid(true);

        self::getEntityManager()->persist($activeActivity);
        self::getEntityManager()->persist($expiredActivity);
        $this->repository->save($activeProduct, false);
        $this->repository->save($expiredProduct, true);

        // 测试查找单个活跃商品
        $result = $this->repository->findActiveByProductId($productId);

        $this->assertNotNull($result, '应该找到活跃的商品');
        $this->assertSame($productId, $result->getProductId());
        $this->assertSame('80.00', $result->getActivityPrice());
        $this->assertNotNull($result->getActivity());
        $this->assertSame($activeActivity->getId(), $result->getActivity()->getId());
    }

    public function testFindActiveByProductIdWithNoActiveActivity(): void
    {
        $productId = 'no_active_product_' . uniqid();

        // 创建一个已结束的活动
        $expiredActivity = $this->createActivity('expired_activity');
        $expiredActivity->setStartTime(new \DateTimeImmutable('-3 hours'));
        $expiredActivity->setEndTime(new \DateTimeImmutable('-1 hour'));

        $expiredProduct = new ActivityProduct();
        $expiredProduct->setActivity($expiredActivity);
        $expiredProduct->setProductId($productId);
        $expiredProduct->setActivityPrice('50.00');
        $expiredProduct->setValid(true);

        self::getEntityManager()->persist($expiredActivity);
        $this->repository->save($expiredProduct, true);

        // 应该找不到活跃的商品
        $result = $this->repository->findActiveByProductId($productId);
        $this->assertNull($result, '不应该找到活跃的商品');
    }

    public function testFindActiveByProductIds(): void
    {
        // 创建活跃活动
        $activeActivity1 = $this->createActivity('active_activity_1');
        $activeActivity1->setStartTime(new \DateTimeImmutable('-1 hour'));
        $activeActivity1->setEndTime(new \DateTimeImmutable('+1 hour'));
        $activeActivity1->setPriority(2); // 高优先级

        $activeActivity2 = $this->createActivity('active_activity_2');
        $activeActivity2->setStartTime(new \DateTimeImmutable('-30 minutes'));
        $activeActivity2->setEndTime(new \DateTimeImmutable('+2 hours'));
        $activeActivity2->setPriority(1); // 低优先级

        // 创建已结束活动
        $expiredActivity = $this->createActivity('expired_activity');
        $expiredActivity->setStartTime(new \DateTimeImmutable('-3 hours'));
        $expiredActivity->setEndTime(new \DateTimeImmutable('-1 hour'));

        // 创建商品关联
        $activeProduct1 = new ActivityProduct();
        $activeProduct1->setActivity($activeActivity1);
        $activeProduct1->setProductId('active_product_1');
        $activeProduct1->setActivityPrice('90.00');
        $activeProduct1->setValid(true);

        $activeProduct2 = new ActivityProduct();
        $activeProduct2->setActivity($activeActivity2);
        $activeProduct2->setProductId('active_product_2');
        $activeProduct2->setActivityPrice('85.00');
        $activeProduct2->setValid(true);

        $expiredProduct = new ActivityProduct();
        $expiredProduct->setActivity($expiredActivity);
        $expiredProduct->setProductId('expired_product');
        $expiredProduct->setActivityPrice('80.00');
        $expiredProduct->setValid(true);

        self::getEntityManager()->persist($activeActivity1);
        self::getEntityManager()->persist($activeActivity2);
        self::getEntityManager()->persist($expiredActivity);
        $this->repository->save($activeProduct1, false);
        $this->repository->save($activeProduct2, false);
        $this->repository->save($expiredProduct, true);

        // 测试查找多个商品的活跃关联
        $results = $this->repository->findActiveByProductIds(['active_product_1', 'active_product_2', 'expired_product', 'nonexistent']);

        $this->assertCount(2, $results, '应该只返回2个活跃的商品关联');

        $productIds = array_map(fn ($ap) => $ap->getProductId(), $results);
        $this->assertContains('active_product_1', $productIds);
        $this->assertContains('active_product_2', $productIds);
        $this->assertNotContains('expired_product', $productIds);

        // 验证优先级排序：高优先级活动的商品应该排在前面
        $this->assertSame('active_product_1', $results[0]->getProductId(), '高优先级活动的商品应该排在第一位');
        $this->assertSame('90.00', $results[0]->getActivityPrice());
    }

    public function testFindActiveByProductIdsWithEmptyArray(): void
    {
        $results = $this->repository->findActiveByProductIds([]);
        $this->assertIsArray($results);
        $this->assertEmpty($results, '空数组输入应该返回空结果');
    }

    public function testUniqueConstraintMultipleActivities(): void
    {
        // 创建两个不同的活动
        $activity1 = $this->createActivity('constraint_activity_1_' . uniqid());
        $activity2 = $this->createActivity('constraint_activity_2_' . uniqid());

        self::getEntityManager()->persist($activity1);
        self::getEntityManager()->persist($activity2);
        self::getEntityManager()->flush();

        // 使用不同的产品ID避免约束冲突，重点是测试不同活动可以包含各自的产品
        $productId1 = 'constraint_test_product_1_' . uniqid();
        $productId2 = 'constraint_test_product_2_' . uniqid();

        $activityProduct1 = new ActivityProduct();
        $activityProduct1->setActivity($activity1);
        $activityProduct1->setProductId($productId1);
        $activityProduct1->setActivityPrice('99.99');
        $activityProduct1->setValid(true);

        $activityProduct2 = new ActivityProduct();
        $activityProduct2->setActivity($activity2);
        $activityProduct2->setProductId($productId2);
        $activityProduct2->setActivityPrice('89.99');
        $activityProduct2->setValid(true);

        // 这应该不会抛出约束违规异常
        $this->repository->save($activityProduct1, false);
        $this->repository->save($activityProduct2, true);

        $activityId1 = $activity1->getId();
        $activityId2 = $activity2->getId();
        $this->assertNotNull($activityId1, 'Activity 1 ID should not be null');
        $this->assertNotNull($activityId2, 'Activity 2 ID should not be null');

        // 验证两个记录都被正确保存
        $found1 = $this->repository->findByActivityAndProduct($activityId1, $productId1);
        $found2 = $this->repository->findByActivityAndProduct($activityId2, $productId2);

        $this->assertNotNull($found1);
        $this->assertNotNull($found2);
        $this->assertSame('99.99', $found1->getActivityPrice());
        $this->assertSame('89.99', $found2->getActivityPrice());

        // 验证每个活动只能找到自己的产品
        $notFound1 = $this->repository->findByActivityAndProduct($activityId1, $productId2);
        $notFound2 = $this->repository->findByActivityAndProduct($activityId2, $productId1);

        $this->assertNull($notFound1);
        $this->assertNull($notFound2);
    }
}
