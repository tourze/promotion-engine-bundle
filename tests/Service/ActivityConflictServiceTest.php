<?php

namespace PromotionEngineBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PromotionEngineBundle\Entity\TimeLimitActivity;
use PromotionEngineBundle\Enum\ActivityStatus;
use PromotionEngineBundle\Enum\ActivityType;
use PromotionEngineBundle\Service\ActivityConflictService;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(ActivityConflictService::class)]
#[RunTestsInSeparateProcesses]
final class ActivityConflictServiceTest extends AbstractIntegrationTestCase
{
    private ActivityConflictService $service;

    protected function onSetUp(): void
    {
        $this->service = self::getService(ActivityConflictService::class);
    }

    public function testServiceExists(): void
    {
        $this->assertInstanceOf(ActivityConflictService::class, $this->service);
    }

    public function testCheckConflictsWithNoConflicts(): void
    {
        $conflicts = $this->service->checkConflicts(
            ['non_existent_product'],
            new \DateTimeImmutable('2023-11-01'),
            new \DateTimeImmutable('2023-11-11')
        );

        $this->assertIsArray($conflicts);
        $this->assertCount(0, $conflicts);
    }

    public function testCheckConflictsWithRealActivity(): void
    {
        $productId = 'test_product_' . uniqid();
        $activity = $this->createAndPersistActivity($productId);

        $conflicts = $this->service->checkConflicts(
            [$productId],
            $activity->getStartTime(),
            $activity->getEndTime()
        );

        $this->assertIsArray($conflicts);
    }

    public function testValidateNoConflictsWithNonExclusiveActivity(): void
    {
        $this->service->validateNoConflicts(
            ['product1'],
            new \DateTimeImmutable('2023-11-01'),
            new \DateTimeImmutable('2023-11-11'),
            false
        );

        $this->assertTrue(true, '非独占活动验证应该成功通过');
    }

    public function testValidateNoConflictsWithExclusiveActivityAndNoConflicts(): void
    {
        $this->service->validateNoConflicts(
            ['unique_product_' . uniqid()],
            new \DateTimeImmutable('2099-01-01'),
            new \DateTimeImmutable('2099-12-31'),
            true
        );

        $this->assertTrue(true, '独占活动无冲突验证应该成功通过');
    }

    public function testGetActiveActivitiesForProducts(): void
    {
        $activities = $this->service->getActiveActivitiesForProducts(
            ['non_existent_product'],
            new \DateTimeImmutable()
        );

        $this->assertIsArray($activities);
    }

    public function testResolveActivityPriorities(): void
    {
        $priorities = $this->service->resolveActivityPriorities(['non_existent_product']);

        $this->assertIsArray($priorities);
    }

    public function testGetHighestPriorityActivity(): void
    {
        $result = $this->service->getHighestPriorityActivity(
            'non_existent_product',
            new \DateTimeImmutable()
        );

        $this->assertNull($result);
    }

    public function testGetActivitySummaryForProducts(): void
    {
        $summary = $this->service->getActivitySummaryForProducts(['non_existent_product']);

        $this->assertIsArray($summary);
    }

    private function createAndPersistActivity(string $productId): TimeLimitActivity
    {
        $activity = new TimeLimitActivity();
        $activity->setName('测试活动_' . uniqid());
        $activity->setActivityType(ActivityType::LIMITED_TIME_SECKILL);
        $activity->setStartTime(new \DateTimeImmutable('-1 day'));
        $activity->setEndTime(new \DateTimeImmutable('+7 days'));
        $activity->setStatus(ActivityStatus::ACTIVE);
        $activity->setValid(true);
        $activity->setProductIds([$productId]);
        $activity->setPriority(100);
        $activity->setExclusive(false);
        $activity->setPreheatEnabled(false);

        self::getEntityManager()->persist($activity);
        self::getEntityManager()->flush();

        return $activity;
    }
}
