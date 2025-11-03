<?php

namespace PromotionEngineBundle\Tests\Repository;

use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PromotionEngineBundle\Entity\TimeLimitActivity;
use PromotionEngineBundle\Enum\ActivityStatus;
use PromotionEngineBundle\Enum\ActivityType;
use PromotionEngineBundle\Repository\TimeLimitActivityRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[RunTestsInSeparateProcesses]
#[CoversClass(TimeLimitActivityRepository::class)]
final class TimeLimitActivityRepositoryTest extends AbstractRepositoryTestCase
{
    protected function onSetUp(): void
    {
        // Repository测试设置
    }

    protected function getRepositoryClass(): string
    {
        return TimeLimitActivityRepository::class;
    }

    protected function getEntityClass(): string
    {
        return TimeLimitActivity::class;
    }

    protected function createNewEntity(): object
    {
        $activity = new TimeLimitActivity();
        $activity->setName('测试活动');
        $activity->setActivityType(ActivityType::LIMITED_TIME_SECKILL);
        $activity->setStartTime(new \DateTimeImmutable('2023-11-01 00:00:00'));
        $activity->setEndTime(new \DateTimeImmutable('2023-11-11 23:59:59'));
        $activity->setStatus(ActivityStatus::ACTIVE);
        $activity->setValid(true);

        return $activity;
    }

    protected function getRepository(): TimeLimitActivityRepository
    {
        return self::getService(TimeLimitActivityRepository::class);
    }

    public function testFindActiveActivitiesCustom(): void
    {
        $now = new \DateTimeImmutable('2023-11-05 12:00:00');

        // 活跃的活动
        $activeActivity = new TimeLimitActivity();
        $activeActivity->setName('活跃活动');
        $activeActivity->setActivityType(ActivityType::LIMITED_TIME_SECKILL);
        $activeActivity->setStartTime(new \DateTimeImmutable('2023-11-01 00:00:00'));
        $activeActivity->setEndTime(new \DateTimeImmutable('2023-11-10 23:59:59'));
        $activeActivity->setStatus(ActivityStatus::ACTIVE);
        $activeActivity->setValid(true);

        // 未开始的活动
        $pendingActivity = new TimeLimitActivity();
        $pendingActivity->setName('未开始活动');
        $pendingActivity->setActivityType(ActivityType::LIMITED_TIME_SECKILL);
        $pendingActivity->setStartTime(new \DateTimeImmutable('2023-11-15 00:00:00'));
        $pendingActivity->setEndTime(new \DateTimeImmutable('2023-11-25 23:59:59'));
        $pendingActivity->setStatus(ActivityStatus::PENDING);
        $pendingActivity->setValid(true);

        $em = self::getEntityManager();
        $em->persist($activeActivity);
        $em->persist($pendingActivity);
        $em->flush();

        $repository = $this->getRepository();
        $results = $repository->findActiveActivities($now);

        $this->assertGreaterThanOrEqual(1, $results);

        // 验证至少有一个是我们创建的活跃活动
        $foundOurActivity = false;
        foreach ($results as $result) {
            if ('活跃活动' === $result->getName()) {
                $foundOurActivity = true;
                break;
            }
        }
        $this->assertTrue($foundOurActivity, '应该包含我们创建的活跃活动');
    }

    public function testFindByIds(): void
    {
        $activity1 = new TimeLimitActivity();
        $activity1->setName('活动1');
        $activity1->setActivityType(ActivityType::LIMITED_TIME_SECKILL);
        $activity1->setStartTime(new \DateTimeImmutable('2023-11-01 00:00:00'));
        $activity1->setEndTime(new \DateTimeImmutable('2023-11-11 23:59:59'));
        $activity1->setStatus(ActivityStatus::ACTIVE);
        $activity1->setValid(true);

        $activity2 = new TimeLimitActivity();
        $activity2->setName('活动2');
        $activity2->setActivityType(ActivityType::LIMITED_TIME_DISCOUNT);
        $activity2->setStartTime(new \DateTimeImmutable('2023-11-01 00:00:00'));
        $activity2->setEndTime(new \DateTimeImmutable('2023-11-11 23:59:59'));
        $activity2->setStatus(ActivityStatus::ACTIVE);
        $activity2->setValid(true);

        $em = self::getEntityManager();
        $em->persist($activity1);
        $em->persist($activity2);
        $em->flush();

        $id1 = $activity1->getId();
        $id2 = $activity2->getId();
        $this->assertNotNull($id1, 'Activity 1 ID should not be null');
        $this->assertNotNull($id2, 'Activity 2 ID should not be null');

        $ids = [$id1, $id2];
        $repository = $this->getRepository();
        $results = $repository->findByIds($ids);

        $this->assertCount(2, $results);
        $resultNames = array_map(fn ($activity) => $activity->getName(), $results);
        $this->assertContains('活动1', $resultNames);
        $this->assertContains('活动2', $resultNames);
    }

    public function testFindByIdsWithEmptyArray(): void
    {
        $repository = $this->getRepository();
        $results = $repository->findByIds([]);

        $this->assertEmpty($results);
    }

    public function testCountActiveActivities(): void
    {
        $now = new \DateTimeImmutable('2023-11-05 12:00:00');

        $activeActivity = new TimeLimitActivity();
        $activeActivity->setName('活跃活动');
        $activeActivity->setActivityType(ActivityType::LIMITED_TIME_SECKILL);
        $activeActivity->setStartTime(new \DateTimeImmutable('2023-11-01 00:00:00'));
        $activeActivity->setEndTime(new \DateTimeImmutable('2023-11-10 23:59:59'));
        $activeActivity->setStatus(ActivityStatus::ACTIVE);
        $activeActivity->setValid(true);

        $em = self::getEntityManager();
        $em->persist($activeActivity);
        $em->flush();

        $repository = $this->getRepository();
        $count = $repository->countActiveActivities($now);

        $this->assertGreaterThanOrEqual(1, $count);
    }

    public function testFindActivitiesByProductIds(): void
    {
        $now = new \DateTimeImmutable('2023-11-05 12:00:00');
        $productIds = ['product_123', 'product_456'];

        $activity = new TimeLimitActivity();
        $activity->setName('测试产品活动');
        $activity->setActivityType(ActivityType::LIMITED_TIME_DISCOUNT);
        $activity->setStartTime(new \DateTimeImmutable('2023-11-01 00:00:00'));
        $activity->setEndTime(new \DateTimeImmutable('2023-11-10 23:59:59'));
        $activity->setStatus(ActivityStatus::ACTIVE);
        $activity->setValid(true);
        $activity->setProductIds($productIds);
        $activity->setPriority(5);

        $em = self::getEntityManager();
        $em->persist($activity);
        $em->flush();

        $repository = $this->getRepository();

        // 测试正常情况
        $results = $repository->findActivitiesByProductIds($productIds, $now);
        $this->assertNotEmpty($results);

        // 测试单个产品ID
        $results = $repository->findActivitiesByProductIds(['product_123'], $now);
        $this->assertNotEmpty($results);

        // 测试空数组
        $results = $repository->findActivitiesByProductIds([], $now);
        $this->assertEmpty($results);

        // 测试不存在的产品ID
        $results = $repository->findActivitiesByProductIds(['nonexistent'], $now);
        $this->assertEmpty($results);
    }

    public function testFindActivitiesNeedingStatusUpdate(): void
    {
        $now = new \DateTimeImmutable('2023-11-05 12:00:00');

        // 应该从PENDING变为ACTIVE的活动
        $pendingActivity = new TimeLimitActivity();
        $pendingActivity->setName('应该变为活跃的活动');
        $pendingActivity->setActivityType(ActivityType::LIMITED_TIME_SECKILL);
        $pendingActivity->setStartTime(new \DateTimeImmutable('2023-11-01 00:00:00')); // 开始时间已过
        $pendingActivity->setEndTime(new \DateTimeImmutable('2023-11-10 23:59:59'));
        $pendingActivity->setStatus(ActivityStatus::PENDING); // 但状态还是PENDING
        $pendingActivity->setValid(true);

        // 应该从ACTIVE变为FINISHED的活动
        $activeActivity = new TimeLimitActivity();
        $activeActivity->setName('应该变为结束的活动');
        $activeActivity->setActivityType(ActivityType::LIMITED_TIME_DISCOUNT);
        $activeActivity->setStartTime(new \DateTimeImmutable('2023-10-01 00:00:00'));
        $activeActivity->setEndTime(new \DateTimeImmutable('2023-11-01 23:59:59')); // 结束时间已过
        $activeActivity->setStatus(ActivityStatus::ACTIVE); // 但状态还是ACTIVE
        $activeActivity->setValid(true);

        $em = self::getEntityManager();
        $em->persist($pendingActivity);
        $em->persist($activeActivity);
        $em->flush();

        $repository = $this->getRepository();
        $results = $repository->findActivitiesNeedingStatusUpdate($now);

        $this->assertGreaterThanOrEqual(2, count($results));

        // 验证包含我们创建的活动
        $foundPending = false;
        $foundActive = false;
        foreach ($results as $result) {
            if ('应该变为活跃的活动' === $result->getName()) {
                $foundPending = true;
            }
            if ('应该变为结束的活动' === $result->getName()) {
                $foundActive = true;
            }
        }
        $this->assertTrue($foundPending || $foundActive, '应该包含需要状态更新的活动');
    }

    public function testFindByActivityType(): void
    {
        $seckillActivity = new TimeLimitActivity();
        $seckillActivity->setName('秒杀活动');
        $seckillActivity->setActivityType(ActivityType::LIMITED_TIME_SECKILL);
        $seckillActivity->setStartTime(new \DateTimeImmutable('2023-11-01 00:00:00'));
        $seckillActivity->setEndTime(new \DateTimeImmutable('2023-11-11 23:59:59'));
        $seckillActivity->setStatus(ActivityStatus::ACTIVE);
        $seckillActivity->setValid(true);
        $seckillActivity->setPriority(10);

        $discountActivity = new TimeLimitActivity();
        $discountActivity->setName('折扣活动');
        $discountActivity->setActivityType(ActivityType::LIMITED_TIME_DISCOUNT);
        $discountActivity->setStartTime(new \DateTimeImmutable('2023-11-01 00:00:00'));
        $discountActivity->setEndTime(new \DateTimeImmutable('2023-11-11 23:59:59'));
        $discountActivity->setStatus(ActivityStatus::ACTIVE);
        $discountActivity->setValid(true);
        $discountActivity->setPriority(5);

        $em = self::getEntityManager();
        $em->persist($seckillActivity);
        $em->persist($discountActivity);
        $em->flush();

        $repository = $this->getRepository();
        $queryBuilder = $repository->findByActivityType(ActivityType::LIMITED_TIME_SECKILL);

        $this->assertInstanceOf(QueryBuilder::class, $queryBuilder);

        $results = $queryBuilder->getQuery()->getResult();
        $this->assertNotEmpty($results);

        // 验证所有结果都是秒杀类型
        $this->assertIsArray($results);
        foreach ($results as $result) {
            $this->assertInstanceOf(TimeLimitActivity::class, $result);
            $this->assertEquals(ActivityType::LIMITED_TIME_SECKILL, $result->getActivityType());
            $this->assertTrue($result->isValid());
        }
    }

    public function testFindConflictingActivities(): void
    {
        $productIds = ['product_conflict_1', 'product_conflict_2'];
        $startTime = new \DateTimeImmutable('2023-11-05 00:00:00');
        $endTime = new \DateTimeImmutable('2023-11-15 23:59:59');

        // 创建一个独占活动
        $exclusiveActivity = new TimeLimitActivity();
        $exclusiveActivity->setName('独占活动');
        $exclusiveActivity->setActivityType(ActivityType::LIMITED_TIME_SECKILL);
        $exclusiveActivity->setStartTime(new \DateTimeImmutable('2023-11-10 00:00:00'));
        $exclusiveActivity->setEndTime(new \DateTimeImmutable('2023-11-20 23:59:59'));
        $exclusiveActivity->setStatus(ActivityStatus::ACTIVE);
        $exclusiveActivity->setValid(true);
        $exclusiveActivity->setExclusive(true);
        $exclusiveActivity->setProductIds($productIds);

        // 创建一个非独占活动（不应该在冲突结果中）
        $nonExclusiveActivity = new TimeLimitActivity();
        $nonExclusiveActivity->setName('非独占活动');
        $nonExclusiveActivity->setActivityType(ActivityType::LIMITED_TIME_DISCOUNT);
        $nonExclusiveActivity->setStartTime(new \DateTimeImmutable('2023-11-10 00:00:00'));
        $nonExclusiveActivity->setEndTime(new \DateTimeImmutable('2023-11-20 23:59:59'));
        $nonExclusiveActivity->setStatus(ActivityStatus::ACTIVE);
        $nonExclusiveActivity->setValid(true);
        $nonExclusiveActivity->setExclusive(false);
        $nonExclusiveActivity->setProductIds($productIds);

        $em = self::getEntityManager();
        $em->persist($exclusiveActivity);
        $em->persist($nonExclusiveActivity);
        $em->flush();

        $repository = $this->getRepository();

        // 测试正常冲突检测
        $conflicts = $repository->findConflictingActivities($productIds, $startTime, $endTime);
        $this->assertNotEmpty($conflicts);

        // 验证只返回独占活动
        foreach ($conflicts as $conflict) {
            $this->assertTrue($conflict->isExclusive());
        }

        // 测试空产品ID数组
        $conflicts = $repository->findConflictingActivities([], $startTime, $endTime);
        $this->assertEmpty($conflicts);

        // 测试排除特定活动ID
        $exclusiveId = $exclusiveActivity->getId();
        $conflicts = $repository->findConflictingActivities($productIds, $startTime, $endTime, $exclusiveId);

        // 应该不包含被排除的活动
        foreach ($conflicts as $conflict) {
            $this->assertNotEquals($exclusiveId, $conflict->getId());
        }
    }

    public function testFindHighestPriorityActivityForProduct(): void
    {
        $productId = 'priority_test_product';
        $now = new \DateTimeImmutable('2023-11-05 12:00:00');

        // 创建低优先级活动
        $lowPriorityActivity = new TimeLimitActivity();
        $lowPriorityActivity->setName('低优先级活动');
        $lowPriorityActivity->setActivityType(ActivityType::LIMITED_TIME_DISCOUNT);
        $lowPriorityActivity->setStartTime(new \DateTimeImmutable('2023-11-01 00:00:00'));
        $lowPriorityActivity->setEndTime(new \DateTimeImmutable('2023-11-10 23:59:59'));
        $lowPriorityActivity->setStatus(ActivityStatus::ACTIVE);
        $lowPriorityActivity->setValid(true);
        $lowPriorityActivity->setPriority(1);
        $lowPriorityActivity->setProductIds([$productId]);

        // 创建高优先级活动
        $highPriorityActivity = new TimeLimitActivity();
        $highPriorityActivity->setName('高优先级活动');
        $highPriorityActivity->setActivityType(ActivityType::LIMITED_TIME_SECKILL);
        $highPriorityActivity->setStartTime(new \DateTimeImmutable('2023-11-01 00:00:00'));
        $highPriorityActivity->setEndTime(new \DateTimeImmutable('2023-11-10 23:59:59'));
        $highPriorityActivity->setStatus(ActivityStatus::ACTIVE);
        $highPriorityActivity->setValid(true);
        $highPriorityActivity->setPriority(10);
        $highPriorityActivity->setProductIds([$productId]);

        $em = self::getEntityManager();
        $em->persist($lowPriorityActivity);
        $em->persist($highPriorityActivity);
        $em->flush();

        $repository = $this->getRepository();
        $result = $repository->findHighestPriorityActivityForProduct($productId, $now);

        $this->assertNotNull($result);
        $this->assertEquals('高优先级活动', $result->getName());
        $this->assertEquals(10, $result->getPriority());

        // 测试不存在的产品
        $result = $repository->findHighestPriorityActivityForProduct('nonexistent_product', $now);
        $this->assertNull($result);
    }

    public function testFindSeckillActivities(): void
    {
        // 创建秒杀活动
        $seckillActivity = new TimeLimitActivity();
        $seckillActivity->setName('秒杀活动');
        $seckillActivity->setActivityType(ActivityType::LIMITED_TIME_SECKILL);
        $seckillActivity->setStartTime(new \DateTimeImmutable('2023-11-01 00:00:00'));
        $seckillActivity->setEndTime(new \DateTimeImmutable('2023-11-11 23:59:59'));
        $seckillActivity->setStatus(ActivityStatus::ACTIVE);
        $seckillActivity->setValid(true);

        // 创建非秒杀活动
        $discountActivity = new TimeLimitActivity();
        $discountActivity->setName('折扣活动');
        $discountActivity->setActivityType(ActivityType::LIMITED_TIME_DISCOUNT);
        $discountActivity->setStartTime(new \DateTimeImmutable('2023-11-01 00:00:00'));
        $discountActivity->setEndTime(new \DateTimeImmutable('2023-11-11 23:59:59'));
        $discountActivity->setStatus(ActivityStatus::ACTIVE);
        $discountActivity->setValid(true);

        $em = self::getEntityManager();
        $em->persist($seckillActivity);
        $em->persist($discountActivity);
        $em->flush();

        $repository = $this->getRepository();
        $results = $repository->findSeckillActivities();

        $this->assertNotEmpty($results);

        // 验证所有结果都是秒杀类型
        $this->assertIsArray($results);
        foreach ($results as $result) {
            $this->assertInstanceOf(TimeLimitActivity::class, $result);
            $this->assertEquals(ActivityType::LIMITED_TIME_SECKILL, $result->getActivityType());
            $this->assertTrue($result->isValid());
        }

        // 验证包含我们创建的秒杀活动
        $foundSeckill = false;
        foreach ($results as $result) {
            if ('秒杀活动' === $result->getName()) {
                $foundSeckill = true;
                break;
            }
        }
        $this->assertTrue($foundSeckill, '应该包含我们创建的秒杀活动');
    }
}
