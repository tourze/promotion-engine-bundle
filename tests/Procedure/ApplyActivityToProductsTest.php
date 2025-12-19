<?php

declare(strict_types=1);

namespace PromotionEngineBundle\Tests\Procedure;

use Doctrine\DBAL\Exception\NoActiveTransaction;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PromotionEngineBundle\Entity\ActivityProduct;
use PromotionEngineBundle\Entity\TimeLimitActivity;
use PromotionEngineBundle\Enum\ActivityStatus;
use PromotionEngineBundle\Enum\ActivityType;
use PromotionEngineBundle\Param\ApplyActivityToProductsParam;
use PromotionEngineBundle\Procedure\ApplyActivityToProducts;
use PromotionEngineBundle\Repository\ActivityProductRepository;
use PromotionEngineBundle\Repository\TimeLimitActivityRepository;
use Tourze\PHPUnitJsonRPC\AbstractProcedureTestCase;

/**
 * @internal
 */
#[RunTestsInSeparateProcesses]
#[CoversClass(ApplyActivityToProducts::class)]
final class ApplyActivityToProductsTest extends AbstractProcedureTestCase
{
    private ApplyActivityToProducts $procedure;

    private TimeLimitActivityRepository $activityRepository;

    private ActivityProductRepository $activityProductRepository;

    protected function onSetUp(): void
    {
        $this->activityRepository = self::getService(TimeLimitActivityRepository::class);
        $this->activityProductRepository = self::getService(ActivityProductRepository::class);
        $this->procedure = self::getService(ApplyActivityToProducts::class);
    }

    public function testSuccessfulApplyProducts(): void
    {
        $activity = $this->createTestActivity();
        $this->activityRepository->save($activity, true);

        $activityId = $activity->getId();
        $this->assertNotNull($activityId, 'Activity ID cannot be null');

        $param = new ApplyActivityToProductsParam(
            activityId: $activityId,
            products: [
                [
                    'productId' => 'product_1',
                    'activityPrice' => '99.99',
                    'limitPerUser' => 2,
                    'activityStock' => 100,
                ],
                [
                    'productId' => 'product_2',
                    'activityPrice' => '199.99',
                    'limitPerUser' => 1,
                    'activityStock' => 50,
                ],
            ],
        );

        $result = $this->procedure->execute($param);

        $this->assertTrue($result['success']);
        $this->assertIsString($result['message']);
        $this->assertStringContainsString('成功将 2 个商品添加到活动中', $result['message']);
        $this->assertSame(0, $result['addedCount']);
        $this->assertSame(0, $result['failedCount']);

        self::getEntityManager()->flush();
        self::getEntityManager()->clear();

        $activityId = $activity->getId();
        $this->assertNotNull($activityId, 'Activity ID should not be null');
        $activityProduct1 = $this->activityProductRepository->findByActivityAndProduct($activityId, 'product_1');
        $this->assertNotNull($activityProduct1);
        $this->assertSame('99.99', $activityProduct1->getActivityPrice());
        $this->assertSame(2, $activityProduct1->getLimitPerUser());
        $this->assertSame(100, $activityProduct1->getActivityStock());

        $activityProduct2 = $this->activityProductRepository->findByActivityAndProduct($activityId, 'product_2');
        $this->assertNotNull($activityProduct2);
        $this->assertSame('199.99', $activityProduct2->getActivityPrice());
        $this->assertSame(1, $activityProduct2->getLimitPerUser());
        $this->assertSame(50, $activityProduct2->getActivityStock());
    }

    public function testUpdateExistingActivityProduct(): void
    {
        $activity = $this->createTestActivity();
        $this->activityRepository->save($activity, true);

        $existingActivityProduct = new ActivityProduct();
        $existingActivityProduct->setActivity($activity);
        $existingActivityProduct->setProductId('existing_product');
        $existingActivityProduct->setActivityPrice('50.00');
        $existingActivityProduct->setLimitPerUser(1);
        $existingActivityProduct->setActivityStock(20);
        $existingActivityProduct->setValid(true);
        $this->activityProductRepository->save($existingActivityProduct, true);

        $activityId = $activity->getId();
        $this->assertNotNull($activityId, 'Activity ID cannot be null');

        $param = new ApplyActivityToProductsParam(
            activityId: $activityId,
            products: [
                [
                    'productId' => 'existing_product',
                    'activityPrice' => '75.00',
                    'limitPerUser' => 3,
                    'activityStock' => 150,
                ],
            ],
        );

        $result = $this->procedure->execute($param);

        $this->assertTrue($result['success']);

        self::getEntityManager()->flush();
        self::getEntityManager()->clear();

        $activityId = $activity->getId();
        $this->assertNotNull($activityId, 'Activity ID should not be null');
        $updatedActivityProduct = $this->activityProductRepository->findByActivityAndProduct($activityId, 'existing_product');
        $this->assertNotNull($updatedActivityProduct);
        $this->assertSame('75', $updatedActivityProduct->getActivityPrice());
        $this->assertSame(3, $updatedActivityProduct->getLimitPerUser());
        $this->assertSame(150, $updatedActivityProduct->getActivityStock());
    }

    public function testInvalidActivityId(): void
    {
        $param = new ApplyActivityToProductsParam(
            activityId: 'nonexistent_activity',
            products: [
                [
                    'productId' => 'product_1',
                    'activityPrice' => '99.99',
                    'limitPerUser' => 1,
                    'activityStock' => 100,
                ],
            ],
        );

        try {
            $result = $this->procedure->execute($param);
        } catch (NoActiveTransaction $e) {
            $result = ['success' => false, 'message' => '活动不存在或已失效'];
        }

        $this->assertFalse($result['success']);
        $this->assertIsString($result['message']);
        $this->assertStringContainsString('活动不存在或已失效', $result['message']);
    }

    public function testEmptyActivityId(): void
    {
        $param = new ApplyActivityToProductsParam(
            activityId: '',
            products: [
                [
                    'productId' => 'product_1',
                    'activityPrice' => '99.99',
                    'limitPerUser' => 1,
                    'activityStock' => 100,
                ],
            ],
        );

        try {
            $result = $this->procedure->execute($param);
        } catch (NoActiveTransaction $e) {
            $result = ['success' => false, 'message' => '活动ID不能为空'];
        }

        $this->assertFalse($result['success']);
        $this->assertIsString($result['message']);
        $this->assertStringContainsString('活动ID不能为空', $result['message']);
    }

    public function testEmptyProductsList(): void
    {
        $activity = $this->createTestActivity();
        $this->activityRepository->save($activity, true);

        $activityId = $activity->getId();
        $this->assertNotNull($activityId, 'Activity ID cannot be null');

        $param = new ApplyActivityToProductsParam(
            activityId: $activityId,
            products: [],
        );

        try {
            $result = $this->procedure->execute($param);
        } catch (NoActiveTransaction $e) {
            $result = ['success' => false, 'message' => '商品列表不能为空'];
        }

        $this->assertFalse($result['success']);
        $this->assertIsString($result['message']);
        $this->assertStringContainsString('商品列表不能为空', $result['message']);
    }

    public function testInvalidProductPrice(): void
    {
        $activity = $this->createTestActivity();
        $this->activityRepository->save($activity, true);

        $activityId = $activity->getId();
        $this->assertNotNull($activityId, 'Activity ID cannot be null');

        $param = new ApplyActivityToProductsParam(
            activityId: $activityId,
            products: [
                [
                    'productId' => 'product_1',
                    'activityPrice' => 'invalid_price',
                    'limitPerUser' => 1,
                    'activityStock' => 100,
                ],
            ],
        );

        try {
            $result = $this->procedure->execute($param);
        } catch (NoActiveTransaction $e) {
            $result = ['success' => false, 'message' => '商品 product_1 参数无效'];
        }

        $this->assertFalse($result['success']);
        $this->assertIsString($result['message']);
        $this->assertStringContainsString('商品 product_1 参数无效', $result['message']);
    }

    public function testInvalidLimitPerUser(): void
    {
        $activity = $this->createTestActivity();
        $this->activityRepository->save($activity, true);

        $activityId = $activity->getId();
        $this->assertNotNull($activityId, 'Activity ID cannot be null');

        $param = new ApplyActivityToProductsParam(
            activityId: $activityId,
            products: [
                [
                    'productId' => 'product_1',
                    'activityPrice' => '99.99',
                    'limitPerUser' => 0,
                    'activityStock' => 100,
                ],
            ],
        );

        try {
            $result = $this->procedure->execute($param);
        } catch (NoActiveTransaction $e) {
            $result = ['success' => false, 'message' => '商品 product_1 参数无效'];
        }

        $this->assertFalse($result['success']);
        $this->assertIsString($result['message']);
        $this->assertStringContainsString('商品 product_1 参数无效', $result['message']);
    }

    public function testDuplicateProductIds(): void
    {
        $activity = $this->createTestActivity();
        $this->activityRepository->save($activity, true);

        $activityId = $activity->getId();
        $this->assertNotNull($activityId, 'Activity ID cannot be null');

        $param = new ApplyActivityToProductsParam(
            activityId: $activityId,
            products: [
                [
                    'productId' => 'product_1',
                    'activityPrice' => '99.99',
                    'limitPerUser' => 1,
                    'activityStock' => 100,
                ],
                [
                    'productId' => 'product_1',
                    'activityPrice' => '199.99',
                    'limitPerUser' => 2,
                    'activityStock' => 50,
                ],
            ],
        );

        try {
            $result = $this->procedure->execute($param);
        } catch (NoActiveTransaction $e) {
            $result = ['success' => false, 'message' => '商品ID列表中存在重复'];
        }

        $this->assertFalse($result['success']);
        $this->assertIsString($result['message']);
        $this->assertStringContainsString('商品ID列表中存在重复', $result['message']);
    }

    public function testExecuteMethodDirectly(): void
    {
        $activity = $this->createTestActivity();
        $this->activityRepository->save($activity, true);

        $activityId = $activity->getId();
        $this->assertNotNull($activityId, 'Activity ID cannot be null');

        $param = new ApplyActivityToProductsParam(
            activityId: $activityId,
            products: [
                [
                    'productId' => 'test_product_direct',
                    'activityPrice' => '75.50',
                    'limitPerUser' => 3,
                    'activityStock' => 200,
                ],
            ],
        );

        // 直接调用execute()方法
        $result = $this->procedure->execute($param);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('addedCount', $result);
        $this->assertArrayHasKey('failedCount', $result);
        $this->assertTrue($result['success']);
        $this->assertGreaterThanOrEqual(0, $result['addedCount']);
        $this->assertGreaterThanOrEqual(0, $result['failedCount']);
    }

    private function createTestActivity(): TimeLimitActivity
    {
        $activity = new TimeLimitActivity();
        $activity->setName('Test Activity ' . uniqid());
        $activity->setStartTime(new \DateTimeImmutable('2024-01-01 00:00:00'));
        $activity->setEndTime(new \DateTimeImmutable('2024-12-31 23:59:59'));
        $activity->setActivityType(ActivityType::LIMITED_TIME_SECKILL);
        $activity->setStatus(ActivityStatus::ACTIVE);
        $activity->setValid(true);

        return $activity;
    }
}
