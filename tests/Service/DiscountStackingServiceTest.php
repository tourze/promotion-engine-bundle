<?php

namespace PromotionEngineBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PromotionEngineBundle\DTO\CalculateActivityDiscountInput;
use PromotionEngineBundle\DTO\CalculateActivityDiscountItem;
use PromotionEngineBundle\Entity\TimeLimitActivity;
use PromotionEngineBundle\Enum\ActivityStatus;
use PromotionEngineBundle\Enum\ActivityType;
use PromotionEngineBundle\Service\DiscountStackingService;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[RunTestsInSeparateProcesses]
#[CoversClass(DiscountStackingService::class)]
final class DiscountStackingServiceTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
        // 集成测试设置
    }

    public function testFilterStackableActivitiesWithEmptyArray(): void
    {
        $stackingService = self::getService(DiscountStackingService::class);

        $items = [new CalculateActivityDiscountItem('product1', 'SKU1', 1, 100.0)];
        $input = new CalculateActivityDiscountInput($items, 'user123');

        $result = $stackingService->filterStackableActivities([], $input);

        $this->assertEmpty($result);
    }

    public function testFilterStackableActivitiesWithSingleActivity(): void
    {
        $stackingService = self::getService(DiscountStackingService::class);

        $activity = new TimeLimitActivity();
        $activity->setName('测试活动1');
        $activity->setActivityType(ActivityType::LIMITED_TIME_SECKILL);
        $activity->setStartTime(new \DateTimeImmutable('2023-11-01 00:00:00'));
        $activity->setEndTime(new \DateTimeImmutable('2023-11-11 23:59:59'));
        $activity->setStatus(ActivityStatus::ACTIVE);
        $activity->setPriority(100);
        $activity->setExclusive(false);

        $items = [new CalculateActivityDiscountItem('product1', 'SKU1', 1, 100.0)];
        $input = new CalculateActivityDiscountInput($items, 'user123');

        $result = $stackingService->filterStackableActivities([$activity], $input);

        $this->assertCount(1, $result);
        $this->assertEquals('测试活动1', $result[0]->getName());
    }

    public function testFilterStackableActivitiesWithExclusiveActivity(): void
    {
        $stackingService = self::getService(DiscountStackingService::class);

        $exclusiveActivity = new TimeLimitActivity();
        $exclusiveActivity->setName('独占活动');
        $exclusiveActivity->setActivityType(ActivityType::LIMITED_TIME_SECKILL);
        $exclusiveActivity->setStartTime(new \DateTimeImmutable('2023-11-01 00:00:00'));
        $exclusiveActivity->setEndTime(new \DateTimeImmutable('2023-11-11 23:59:59'));
        $exclusiveActivity->setStatus(ActivityStatus::ACTIVE);
        $exclusiveActivity->setPriority(150); // 高优先级
        $exclusiveActivity->setExclusive(true); // 独占

        $normalActivity = new TimeLimitActivity();
        $normalActivity->setName('普通活动');
        $normalActivity->setActivityType(ActivityType::LIMITED_TIME_DISCOUNT);
        $normalActivity->setStartTime(new \DateTimeImmutable('2023-11-01 00:00:00'));
        $normalActivity->setEndTime(new \DateTimeImmutable('2023-11-11 23:59:59'));
        $normalActivity->setStatus(ActivityStatus::ACTIVE);
        $normalActivity->setPriority(100);
        $normalActivity->setExclusive(false);

        $items = [new CalculateActivityDiscountItem('product1', 'SKU1', 1, 100.0)];
        $input = new CalculateActivityDiscountInput($items, 'user123');

        $result = $stackingService->filterStackableActivities([$exclusiveActivity, $normalActivity], $input);

        // 应该只返回独占活动
        $this->assertCount(1, $result);
        $this->assertEquals('独占活动', $result[0]->getName());
        $this->assertTrue($result[0]->isExclusive());
    }

    public function testFilterStackableActivitiesWithMultipleNormalActivities(): void
    {
        $stackingService = self::getService(DiscountStackingService::class);

        $activity1 = new TimeLimitActivity();
        $activity1->setName('活动1');
        $activity1->setActivityType(ActivityType::LIMITED_TIME_DISCOUNT);
        $activity1->setStartTime(new \DateTimeImmutable('2023-11-01 00:00:00'));
        $activity1->setEndTime(new \DateTimeImmutable('2023-11-11 23:59:59'));
        $activity1->setStatus(ActivityStatus::ACTIVE);
        $activity1->setPriority(100);
        $activity1->setExclusive(false);

        $activity2 = new TimeLimitActivity();
        $activity2->setName('活动2');
        $activity2->setActivityType(ActivityType::LIMITED_TIME_DISCOUNT);
        $activity2->setStartTime(new \DateTimeImmutable('2023-11-01 00:00:00'));
        $activity2->setEndTime(new \DateTimeImmutable('2023-11-11 23:59:59'));
        $activity2->setStatus(ActivityStatus::ACTIVE);
        $activity2->setPriority(80);
        $activity2->setExclusive(false);

        $activity3 = new TimeLimitActivity();
        $activity3->setName('活动3');
        $activity3->setActivityType(ActivityType::LIMITED_TIME_DISCOUNT);
        $activity3->setStartTime(new \DateTimeImmutable('2023-11-01 00:00:00'));
        $activity3->setEndTime(new \DateTimeImmutable('2023-11-11 23:59:59'));
        $activity3->setStatus(ActivityStatus::ACTIVE);
        $activity3->setPriority(120);
        $activity3->setExclusive(false);

        $items = [new CalculateActivityDiscountItem('product1', 'SKU1', 1, 100.0)];
        $input = new CalculateActivityDiscountInput($items, 'user123');

        $result = $stackingService->filterStackableActivities([$activity1, $activity2, $activity3], $input);

        // 应该按优先级排序，且可以叠加多个
        $this->assertGreaterThan(0, count($result));
        $this->assertLessThanOrEqual(3, count($result));

        // 检查第一个是最高优先级的
        if (count($result) > 0) {
            $this->assertEquals('活动3', $result[0]->getName());
        }
    }

    public function testOptimizeActivityCombinationWithSingleActivity(): void
    {
        $stackingService = self::getService(DiscountStackingService::class);

        $activity = new TimeLimitActivity();
        $activity->setName('单个活动');
        $activity->setActivityType(ActivityType::LIMITED_TIME_SECKILL);
        $activity->setStartTime(new \DateTimeImmutable('2023-11-01 00:00:00'));
        $activity->setEndTime(new \DateTimeImmutable('2023-11-11 23:59:59'));
        $activity->setStatus(ActivityStatus::ACTIVE);

        $items = [new CalculateActivityDiscountItem('product1', 'SKU1', 1, 100.0)];
        $input = new CalculateActivityDiscountInput($items, 'user123');

        $result = $stackingService->optimizeActivityCombination([$activity], $input);

        $this->assertCount(1, $result);
        $this->assertEquals('单个活动', $result[0]->getName());
    }

    public function testOptimizeActivityCombinationWithMultipleActivities(): void
    {
        $stackingService = self::getService(DiscountStackingService::class);

        $activity1 = new TimeLimitActivity();
        $activity1->setName('活动1');
        $activity1->setActivityType(ActivityType::LIMITED_TIME_DISCOUNT);
        $activity1->setStartTime(new \DateTimeImmutable('2023-11-01 00:00:00'));
        $activity1->setEndTime(new \DateTimeImmutable('2023-11-11 23:59:59'));
        $activity1->setStatus(ActivityStatus::ACTIVE);

        $activity2 = new TimeLimitActivity();
        $activity2->setName('活动2');
        $activity2->setActivityType(ActivityType::LIMITED_TIME_SECKILL);
        $activity2->setStartTime(new \DateTimeImmutable('2023-11-01 00:00:00'));
        $activity2->setEndTime(new \DateTimeImmutable('2023-11-11 23:59:59'));
        $activity2->setStatus(ActivityStatus::ACTIVE);

        $items = [new CalculateActivityDiscountItem('product1', 'SKU1', 1, 100.0)];
        $input = new CalculateActivityDiscountInput($items, 'user123');

        $result = $stackingService->optimizeActivityCombination([$activity1, $activity2], $input);

        $this->assertIsArray($result);
        $this->assertGreaterThan(0, count($result));
        $this->assertLessThanOrEqual(2, count($result));
    }

    public function testGetStackingLimits(): void
    {
        $stackingService = self::getService(DiscountStackingService::class);

        $limits = $stackingService->getStackingLimits();

        $this->assertIsArray($limits);
        $this->assertArrayHasKey('maxStackableActivities', $limits);
        $this->assertArrayHasKey('exclusivePriorityThreshold', $limits);
        $this->assertArrayHasKey('maxDiscountRate', $limits);
        $this->assertEquals(5, $limits['maxStackableActivities']);
        $this->assertEquals(100, $limits['exclusivePriorityThreshold']);
        $this->assertEquals(95.0, $limits['maxDiscountRate']);
    }
}
