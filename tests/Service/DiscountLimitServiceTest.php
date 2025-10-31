<?php

namespace PromotionEngineBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PromotionEngineBundle\DTO\CalculateActivityDiscountInput;
use PromotionEngineBundle\DTO\CalculateActivityDiscountItem;
use PromotionEngineBundle\Entity\ActivityProduct;
use PromotionEngineBundle\Entity\TimeLimitActivity;
use PromotionEngineBundle\Enum\ActivityStatus;
use PromotionEngineBundle\Enum\ActivityType;
use PromotionEngineBundle\Service\DiscountLimitService;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[RunTestsInSeparateProcesses]
#[CoversClass(DiscountLimitService::class)]
final class DiscountLimitServiceTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
        // 集成测试设置，如果需要可以在这里添加特定配置
    }

    public function testValidateDiscountLimitsWithoutUser(): void
    {
        $discountLimitService = self::getService(DiscountLimitService::class);

        $activity = new TimeLimitActivity();
        $activity->setName('测试活动');
        $activity->setActivityType(ActivityType::LIMITED_TIME_SECKILL);
        $activity->setStartTime(new \DateTimeImmutable('2023-11-01 00:00:00'));
        $activity->setEndTime(new \DateTimeImmutable('2023-11-11 23:59:59'));
        $activity->setStatus(ActivityStatus::ACTIVE);

        $activityProduct = new ActivityProduct();
        $activityProduct->setActivity($activity);
        $activityProduct->setProductId('product1');
        $activityProduct->setActivityPrice('80.0');
        $activityProduct->setActivityStock(100);

        $item = new CalculateActivityDiscountItem('product1', 'SKU1', 2, 100.0);
        $calculatedDiscount = 40.0;

        $result = $discountLimitService->validateDiscountLimits(
            $activity,
            $activityProduct,
            $item,
            $calculatedDiscount
        );

        $this->assertTrue($result['valid']);
        $this->assertEquals(40.0, $result['adjustedDiscount']);
        $this->assertEquals(40.0, $result['originalDiscount']);
        $this->assertEmpty($result['limitReasons']);
    }

    public function testValidateDiscountLimitsWithUserLimit(): void
    {
        $discountLimitService = self::getService(DiscountLimitService::class);

        $activity = new TimeLimitActivity();
        $activity->setName('测试活动');
        $activity->setActivityType(ActivityType::LIMITED_TIME_SECKILL);
        $activity->setStartTime(new \DateTimeImmutable('2023-11-01 00:00:00'));
        $activity->setEndTime(new \DateTimeImmutable('2023-11-11 23:59:59'));
        $activity->setStatus(ActivityStatus::ACTIVE);

        $activityProduct = new ActivityProduct();
        $activityProduct->setActivity($activity);
        $activityProduct->setProductId('product1');
        $activityProduct->setActivityPrice('80.0');
        $activityProduct->setActivityStock(100);
        $activityProduct->setLimitPerUser(1); // 限制每人1件

        $item = new CalculateActivityDiscountItem('product1', 'SKU1', 3, 100.0); // 请求3件
        $calculatedDiscount = 60.0; // 3件的优惠
        $userId = 'user123';

        $result = $discountLimitService->validateDiscountLimits(
            $activity,
            $activityProduct,
            $item,
            $calculatedDiscount,
            $userId
        );

        $this->assertTrue($result['valid']);
        $this->assertEquals(20.0, $result['adjustedDiscount']); // 只给1件的优惠
        $this->assertEquals(60.0, $result['originalDiscount']);
        $this->assertCount(1, $result['limitReasons']);
        $this->assertEquals('per_user_quantity_limit', $result['limitReasons'][0]['type']);
    }

    public function testValidateDiscountLimitsWithStockLimit(): void
    {
        $discountLimitService = self::getService(DiscountLimitService::class);

        $activity = new TimeLimitActivity();
        $activity->setName('测试活动');
        $activity->setActivityType(ActivityType::LIMITED_TIME_SECKILL);
        $activity->setStartTime(new \DateTimeImmutable('2023-11-01 00:00:00'));
        $activity->setEndTime(new \DateTimeImmutable('2023-11-11 23:59:59'));
        $activity->setStatus(ActivityStatus::ACTIVE);

        $activityProduct = new ActivityProduct();
        $activityProduct->setActivity($activity);
        $activityProduct->setProductId('product1');
        $activityProduct->setActivityPrice('80.0');
        $activityProduct->setActivityStock(2); // 只有2件库存

        $item = new CalculateActivityDiscountItem('product1', 'SKU1', 5, 100.0); // 请求5件
        $calculatedDiscount = 100.0; // 5件的优惠

        $result = $discountLimitService->validateDiscountLimits(
            $activity,
            $activityProduct,
            $item,
            $calculatedDiscount
        );

        $this->assertTrue($result['valid']);
        $this->assertEquals(40.0, $result['adjustedDiscount']); // 只给2件的优惠
        $this->assertEquals(100.0, $result['originalDiscount']);
        $this->assertCount(1, $result['limitReasons']);
        $this->assertEquals('stock_limit', $result['limitReasons'][0]['type']);
    }

    public function testValidateDiscountLimitsWithSoldOut(): void
    {
        $discountLimitService = self::getService(DiscountLimitService::class);

        $activity = new TimeLimitActivity();
        $activity->setName('测试活动');
        $activity->setActivityType(ActivityType::LIMITED_TIME_SECKILL);
        $activity->setStartTime(new \DateTimeImmutable('2023-11-01 00:00:00'));
        $activity->setEndTime(new \DateTimeImmutable('2023-11-11 23:59:59'));
        $activity->setStatus(ActivityStatus::ACTIVE);

        $activityProduct = new ActivityProduct();
        $activityProduct->setActivity($activity);
        $activityProduct->setProductId('product1');
        $activityProduct->setActivityPrice('80.0');
        $activityProduct->setActivityStock(0); // 售罄

        $item = new CalculateActivityDiscountItem('product1', 'SKU1', 1, 100.0);
        $calculatedDiscount = 20.0;

        $result = $discountLimitService->validateDiscountLimits(
            $activity,
            $activityProduct,
            $item,
            $calculatedDiscount
        );

        $this->assertFalse($result['valid']);
        $this->assertEquals(0.0, $result['adjustedDiscount']);
        $this->assertEquals(20.0, $result['originalDiscount']);
        $this->assertCount(1, $result['limitReasons']);
        $this->assertEquals('sold_out', $result['limitReasons'][0]['type']);
    }

    public function testValidateOrderLimits(): void
    {
        $discountLimitService = self::getService(DiscountLimitService::class);

        $items = [
            new CalculateActivityDiscountItem('product1', 'SKU1', 1, 100.0),
            new CalculateActivityDiscountItem('product2', 'SKU2', 2, 200.0),
        ];

        $input = new CalculateActivityDiscountInput($items, 'user123');
        $totalDiscount = 150.0;

        $result = $discountLimitService->validateOrderLimits($input, $totalDiscount);

        $this->assertTrue($result['valid']);
        $this->assertEquals(150.0, $result['adjustedTotalDiscount']);
        $this->assertIsArray($result['limitReasons']);
    }

    public function testGetDiscountLimits(): void
    {
        $discountLimitService = self::getService(DiscountLimitService::class);

        $limits = $discountLimitService->getDiscountLimits();

        $this->assertIsArray($limits);
        $this->assertArrayHasKey('maxDailyDiscountAmount', $limits);
        $this->assertArrayHasKey('maxSingleOrderDiscountRate', $limits);
        $this->assertArrayHasKey('maxUserActivityUsagePerDay', $limits);
        $this->assertEquals(10000.0, $limits['maxDailyDiscountAmount']);
        $this->assertEquals(80.0, $limits['maxSingleOrderDiscountRate']);
        $this->assertEquals(10, $limits['maxUserActivityUsagePerDay']);
    }

    public function testGetUserDiscountSummaryWithoutUser(): void
    {
        $discountLimitService = self::getService(DiscountLimitService::class);

        $summary = $discountLimitService->getUserDiscountSummary();

        $this->assertIsArray($summary);
        $this->assertArrayHasKey('dailyUsedAmount', $summary);
        $this->assertArrayHasKey('dailyRemainingQuota', $summary);
        $this->assertEquals(0.0, $summary['dailyUsedAmount']);
        $this->assertEquals(10000.0, $summary['dailyRemainingQuota']);
    }

    public function testGetUserDiscountSummaryWithUser(): void
    {
        $discountLimitService = self::getService(DiscountLimitService::class);

        $summary = $discountLimitService->getUserDiscountSummary('user123');

        $this->assertIsArray($summary);
        $this->assertArrayHasKey('dailyUsedAmount', $summary);
        $this->assertArrayHasKey('dailyRemainingQuota', $summary);
        $this->assertArrayHasKey('dailyUsageCount', $summary);
        $this->assertArrayHasKey('dailyRemainingUsage', $summary);
    }

    public function testRecordDiscountUsage(): void
    {
        $discountLimitService = self::getService(DiscountLimitService::class);

        // 验证方法调用不会抛出异常
        try {
            $discountLimitService->recordDiscountUsage(
                'activity123',
                'product1',
                50.0,
                'user123'
            );
            $success = true;
        } catch (\Exception $e) {
            $success = false;
        }

        $this->assertTrue($success, '记录优惠使用应该成功执行而不抛出异常');
    }
}
