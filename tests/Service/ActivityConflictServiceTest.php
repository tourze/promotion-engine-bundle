<?php

namespace PromotionEngineBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use PromotionEngineBundle\Entity\TimeLimitActivity;
use PromotionEngineBundle\Enum\ActivityStatus;
use PromotionEngineBundle\Enum\ActivityType;
use PromotionEngineBundle\Exception\ActivityException;
use PromotionEngineBundle\Repository\TimeLimitActivityRepository;
use PromotionEngineBundle\Service\ActivityConflictService;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[RunTestsInSeparateProcesses]
#[CoversClass(ActivityConflictService::class)]
final class ActivityConflictServiceTest extends AbstractIntegrationTestCase
{
    private ActivityConflictService $service;

    private MockObject $repository;

    protected function onSetUp(): void
    {
        $this->repository = $this->createMock(TimeLimitActivityRepository::class);

        // 将Mock Repository注入到容器中
        self::getContainer()->set(TimeLimitActivityRepository::class, $this->repository);

        // 从容器获取ActivityConflictService实例，而不是直接new
        $this->service = self::getService(ActivityConflictService::class);
    }

    public function testCheckConflicts(): void
    {
        $conflictActivity = $this->createMockActivity();
        $conflictActivity->method('getId')->willReturn('activity123');
        $conflictActivity->method('getName')->willReturn('冲突活动');
        $conflictActivity->method('getActivityType')->willReturn(ActivityType::LIMITED_TIME_SECKILL);
        $conflictActivity->method('getStartTime')->willReturn(new \DateTimeImmutable('2023-11-01 00:00:00'));
        $conflictActivity->method('getEndTime')->willReturn(new \DateTimeImmutable('2023-11-11 23:59:59'));
        $conflictActivity->method('getPriority')->willReturn(100);
        $conflictActivity->method('getProductIds')->willReturn(['product1', 'product2']);

        $this->repository
            ->expects($this->once())
            ->method('findConflictingActivities')
            ->with(['product1'], new \DateTimeImmutable('2023-11-01'), new \DateTimeImmutable('2023-11-11'), null)
            ->willReturn([$conflictActivity])
        ;

        $conflicts = $this->service->checkConflicts(
            ['product1'],
            new \DateTimeImmutable('2023-11-01'),
            new \DateTimeImmutable('2023-11-11')
        );

        $this->assertCount(1, $conflicts);
        $this->assertArrayHasKey('activity123', $conflicts);

        $conflict = $conflicts['activity123'];
        $this->assertEquals('activity123', $conflict['activityId']);
        $this->assertEquals('冲突活动', $conflict['activityName']);
        $this->assertEquals('限时秒杀', $conflict['activityType']);
        $this->assertEquals('2023-11-01 00:00:00', $conflict['startTime']);
        $this->assertEquals('2023-11-11 23:59:59', $conflict['endTime']);
        $this->assertEquals(100, $conflict['priority']);
        $this->assertEquals(['product1'], $conflict['conflictProducts']);
    }

    public function testValidateNoConflictsWithNonExclusiveActivity(): void
    {
        $this->repository
            ->expects($this->never())
            ->method('findConflictingActivities')
        ;

        $this->service->validateNoConflicts(
            ['product1'],
            new \DateTimeImmutable('2023-11-01'),
            new \DateTimeImmutable('2023-11-11'),
            false
        );

        // 验证方法成功执行，无冲突时不应该抛出异常
        $this->assertTrue(true, '非独占活动验证应该成功通过');
    }

    public function testValidateNoConflictsWithExclusiveActivityAndNoConflicts(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('findConflictingActivities')
            ->willReturn([])
        ;

        $this->service->validateNoConflicts(
            ['product1'],
            new \DateTimeImmutable('2023-11-01'),
            new \DateTimeImmutable('2023-11-11'),
            true
        );

        // 验证方法成功执行，无冲突时不应该抛出异常
        $this->assertTrue(true, '独占活动无冲突验证应该成功通过');
    }

    public function testValidateNoConflictsWithExclusiveActivityAndConflicts(): void
    {
        $conflictActivity = $this->createMockActivity();
        $conflictActivity->method('getId')->willReturn('activity123');
        $conflictActivity->method('getName')->willReturn('冲突活动');
        $conflictActivity->method('getActivityType')->willReturn(ActivityType::LIMITED_TIME_SECKILL);
        $conflictActivity->method('getStartTime')->willReturn(new \DateTimeImmutable('2023-11-01 00:00:00'));
        $conflictActivity->method('getEndTime')->willReturn(new \DateTimeImmutable('2023-11-11 23:59:59'));
        $conflictActivity->method('getPriority')->willReturn(100);
        $conflictActivity->method('getProductIds')->willReturn(['product1']);

        $this->repository
            ->expects($this->once())
            ->method('findConflictingActivities')
            ->willReturn([$conflictActivity])
        ;

        $this->expectException(ActivityException::class);
        $this->expectExceptionMessage('商品已参与其他独占活动: 冲突活动(2023-11-01 00:00:00 - 2023-11-11 23:59:59)');

        $this->service->validateNoConflicts(
            ['product1'],
            new \DateTimeImmutable('2023-11-01'),
            new \DateTimeImmutable('2023-11-11'),
            true
        );
    }

    public function testGetActiveActivitiesForProducts(): void
    {
        $activity = $this->createMockActivity();
        $now = new \DateTimeImmutable('2023-11-05 12:00:00');

        $this->repository
            ->expects($this->once())
            ->method('findActivitiesByProductIds')
            ->with(['product1'], $now)
            ->willReturn([$activity])
        ;

        $activities = $this->service->getActiveActivitiesForProducts(['product1'], $now);

        $this->assertCount(1, $activities);
        $this->assertSame($activity, $activities[0]);
    }

    public function testResolveActivityPriorities(): void
    {
        $highPriorityActivity = $this->createMockActivity();
        $highPriorityActivity->method('getPriority')->willReturn(100);
        $highPriorityActivity->method('getCreateTime')->willReturn(new \DateTimeImmutable('2023-10-01'));
        $highPriorityActivity->method('getProductIds')->willReturn(['product1']);

        $lowPriorityActivity = $this->createMockActivity();
        $lowPriorityActivity->method('getPriority')->willReturn(50);
        $lowPriorityActivity->method('getCreateTime')->willReturn(new \DateTimeImmutable('2023-10-02'));
        $lowPriorityActivity->method('getProductIds')->willReturn(['product1']);

        $this->repository
            ->expects($this->once())
            ->method('findActivitiesByProductIds')
            ->willReturn([$highPriorityActivity, $lowPriorityActivity])
        ;

        $priorities = $this->service->resolveActivityPriorities(['product1']);

        $this->assertCount(1, $priorities);
        $this->assertArrayHasKey('product1', $priorities);
        $this->assertSame($highPriorityActivity, $priorities['product1']);
    }

    public function testResolveActivityPrioritiesWithSamePriority(): void
    {
        $earlierActivity = $this->createMockActivity();
        $earlierActivity->method('getPriority')->willReturn(100);
        $earlierActivity->method('getCreateTime')->willReturn(new \DateTimeImmutable('2023-10-01'));
        $earlierActivity->method('getProductIds')->willReturn(['product1']);

        $laterActivity = $this->createMockActivity();
        $laterActivity->method('getPriority')->willReturn(100);
        $laterActivity->method('getCreateTime')->willReturn(new \DateTimeImmutable('2023-10-02'));
        $laterActivity->method('getProductIds')->willReturn(['product1']);

        $this->repository
            ->expects($this->once())
            ->method('findActivitiesByProductIds')
            ->willReturn([$earlierActivity, $laterActivity])
        ;

        $priorities = $this->service->resolveActivityPriorities(['product1']);

        $this->assertCount(1, $priorities);
        $this->assertArrayHasKey('product1', $priorities);
        $this->assertSame($earlierActivity, $priorities['product1']);
    }

    public function testGetHighestPriorityActivity(): void
    {
        $activity = $this->createMockActivity();
        $now = new \DateTimeImmutable('2023-11-05 12:00:00');

        $this->repository
            ->expects($this->once())
            ->method('findHighestPriorityActivityForProduct')
            ->with('product1', $now)
            ->willReturn($activity)
        ;

        $result = $this->service->getHighestPriorityActivity('product1', $now);

        $this->assertSame($activity, $result);
    }

    public function testGetActivitySummaryForProducts(): void
    {
        $activity = $this->createMockActivity();
        $activity->method('getId')->willReturn('activity123');
        $activity->method('getName')->willReturn('测试活动');
        $activity->method('getActivityType')->willReturn(ActivityType::LIMITED_TIME_SECKILL);
        $activity->method('getStatus')->willReturn(ActivityStatus::ACTIVE);
        $activity->method('getPriority')->willReturn(100);
        $activity->method('getStartTime')->willReturn(new \DateTimeImmutable('2023-11-01 00:00:00'));
        $activity->method('getEndTime')->willReturn(new \DateTimeImmutable('2023-11-11 23:59:59'));
        $activity->method('isExclusive')->willReturn(true);
        $activity->method('getTotalLimit')->willReturn(1000);
        $activity->method('getSoldQuantity')->willReturn(300);
        $activity->method('getRemainingQuantity')->willReturn(700);
        $activity->method('isSoldOut')->willReturn(false);
        $activity->method('isInPreheatPeriod')->willReturn(false);
        $activity->method('getPreheatStartTime')->willReturn(null);
        $activity->method('getProductIds')->willReturn(['product1']);
        $activity->method('getCreateTime')->willReturn(new \DateTimeImmutable('2023-10-01'));

        $this->repository
            ->expects($this->once())
            ->method('findActivitiesByProductIds')
            ->willReturn([$activity])
        ;

        $summary = $this->service->getActivitySummaryForProducts(['product1']);

        $this->assertCount(1, $summary);
        $this->assertArrayHasKey('product1', $summary);

        $productSummary = $summary['product1'];
        $this->assertEquals('activity123', $productSummary['activityId']);
        $this->assertEquals('测试活动', $productSummary['activityName']);
        $this->assertEquals('限时秒杀', $productSummary['activityType']);
        $this->assertEquals('limited_time_seckill', $productSummary['activityTypeValue']);
        $this->assertEquals('active', $productSummary['status']);
        $this->assertEquals('进行中', $productSummary['statusLabel']);
        $this->assertEquals(100, $productSummary['priority']);
        $this->assertEquals('2023-11-01 00:00:00', $productSummary['startTime']);
        $this->assertEquals('2023-11-11 23:59:59', $productSummary['endTime']);
        $this->assertTrue($productSummary['isExclusive']);
        $this->assertEquals(1000, $productSummary['totalLimit']);
        $this->assertEquals(300, $productSummary['soldQuantity']);
        $this->assertEquals(700, $productSummary['remainingQuantity']);
        $this->assertFalse($productSummary['isSoldOut']);
        $this->assertFalse($productSummary['isInPreheatPeriod']);
        $this->assertNull($productSummary['preheatStartTime']);
    }

    private function createMockActivity(): MockObject
    {
        return $this->createMock(TimeLimitActivity::class);
    }
}
