<?php

namespace PromotionEngineBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PromotionEngineBundle\Entity\DiscountRule;
use PromotionEngineBundle\Enum\DiscountType;
use PromotionEngineBundle\Repository\DiscountRuleRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[RunTestsInSeparateProcesses]
#[CoversClass(DiscountRuleRepository::class)]
final class DiscountRuleRepositoryTest extends AbstractRepositoryTestCase
{
    private DiscountRuleRepository $repository;

    protected function onSetUp(): void
    {
        $this->repository = self::getService(DiscountRuleRepository::class);
    }

    protected function createNewEntity(): DiscountRule
    {
        $entity = new DiscountRule();
        $entity->setActivityId('activity_' . uniqid());
        $entity->setDiscountType(DiscountType::DISCOUNT);
        $entity->setDiscountValue('10.00');
        $entity->setMinAmount('100.00');
        $entity->setMaxDiscountAmount('50.00');
        $entity->setValid(true);

        return $entity;
    }

    protected function getRepository(): DiscountRuleRepository
    {
        return $this->repository;
    }

    public function testFindByActivityId(): void
    {
        $activityId = 'test_activity_' . uniqid();

        // 创建有效的规则
        $validRule = new DiscountRule();
        $validRule->setActivityId($activityId);
        $validRule->setDiscountType(DiscountType::DISCOUNT);
        $validRule->setDiscountValue('10.00');
        $validRule->setValid(true);
        $this->repository->save($validRule, true);

        // 创建无效的规则
        $invalidRule = new DiscountRule();
        $invalidRule->setActivityId($activityId);
        $invalidRule->setDiscountType(DiscountType::REDUCTION);
        $invalidRule->setDiscountValue('20.00');
        $invalidRule->setValid(false);
        $this->repository->save($invalidRule, true);

        // 测试查找有效规则
        $rules = $this->repository->findByActivityId($activityId);
        $this->assertCount(1, $rules);
        $this->assertEquals($activityId, $rules[0]->getActivityId());
        $this->assertTrue($rules[0]->isValid());
    }

    public function testFindByActivityIdAndType(): void
    {
        $activityId = 'test_activity_' . uniqid();
        $discountType = DiscountType::DISCOUNT;

        $rule = new DiscountRule();
        $rule->setActivityId($activityId);
        $rule->setDiscountType($discountType);
        $rule->setDiscountValue('15.00');
        $rule->setValid(true);
        $this->repository->save($rule, true);

        $foundRule = $this->repository->findByActivityIdAndType($activityId, $discountType);
        $this->assertNotNull($foundRule);
        $this->assertEquals($activityId, $foundRule->getActivityId());
        $this->assertEquals($discountType, $foundRule->getDiscountType());

        // 测试找不到的情况
        $notFound = $this->repository->findByActivityIdAndType($activityId, DiscountType::REDUCTION);
        $this->assertNull($notFound);
    }

    public function testFindByActivityIds(): void
    {
        $activityId1 = 'test_activity_1_' . uniqid();
        $activityId2 = 'test_activity_2_' . uniqid();

        // 创建第一个活动的规则
        $rule1 = new DiscountRule();
        $rule1->setActivityId($activityId1);
        $rule1->setDiscountType(DiscountType::DISCOUNT);
        $rule1->setDiscountValue('10.00');
        $rule1->setValid(true);
        $this->repository->save($rule1, true);

        // 创建第二个活动的规则
        $rule2 = new DiscountRule();
        $rule2->setActivityId($activityId2);
        $rule2->setDiscountType(DiscountType::REDUCTION);
        $rule2->setDiscountValue('20.00');
        $rule2->setValid(true);
        $this->repository->save($rule2, true);

        $rules = $this->repository->findByActivityIds([$activityId1, $activityId2]);
        $this->assertCount(2, $rules);

        // 测试空数组
        $emptyRules = $this->repository->findByActivityIds([]);
        $this->assertEmpty($emptyRules);
    }

    public function testFindByDiscountType(): void
    {
        $discountType = DiscountType::BUY_GIVE;

        $rule = new DiscountRule();
        $rule->setActivityId('activity_' . uniqid());
        $rule->setDiscountType($discountType);
        $rule->setDiscountValue('1');
        $rule->setGiftQuantity(1);
        $rule->setValid(true);
        $this->repository->save($rule, true);

        $rules = $this->repository->findByDiscountType($discountType);
        $this->assertNotEmpty($rules);

        foreach ($rules as $foundRule) {
            $this->assertEquals($discountType, $foundRule->getDiscountType());
            $this->assertTrue($foundRule->isValid());
        }
    }

    public function testFindGroupedByActivityId(): void
    {
        $activityId1 = 'test_activity_1_' . uniqid();
        $activityId2 = 'test_activity_2_' . uniqid();

        // 为第一个活动创建两个规则
        $rule1a = new DiscountRule();
        $rule1a->setActivityId($activityId1);
        $rule1a->setDiscountType(DiscountType::DISCOUNT);
        $rule1a->setDiscountValue('10.00');
        $rule1a->setValid(true);
        $this->repository->save($rule1a, true);

        $rule1b = new DiscountRule();
        $rule1b->setActivityId($activityId1);
        $rule1b->setDiscountType(DiscountType::REDUCTION);
        $rule1b->setDiscountValue('5.00');
        $rule1b->setValid(true);
        $this->repository->save($rule1b, true);

        // 为第二个活动创建一个规则
        $rule2 = new DiscountRule();
        $rule2->setActivityId($activityId2);
        $rule2->setDiscountType(DiscountType::FREE_FREIGHT);
        $rule2->setDiscountValue('0.00');
        $rule2->setValid(true);
        $this->repository->save($rule2, true);

        $grouped = $this->repository->findGroupedByActivityId([$activityId1, $activityId2]);

        $this->assertArrayHasKey($activityId1, $grouped);
        $this->assertArrayHasKey($activityId2, $grouped);
        $this->assertCount(2, $grouped[$activityId1]);
        $this->assertCount(1, $grouped[$activityId2]);

        // 测试空数组
        $emptyGrouped = $this->repository->findGroupedByActivityId([]);
        $this->assertEmpty($emptyGrouped);
    }

    public function testCustomRepositoryMethods(): void
    {
        $entity = new DiscountRule();
        $entity->setActivityId('test_activity_' . uniqid());
        $entity->setDiscountType(DiscountType::DISCOUNT);
        $entity->setDiscountValue('25.00');
        $entity->setMinAmount('200.00');
        $entity->setValid(true);

        $this->repository->save($entity, true);

        $this->assertTrue(self::getEntityManager()->contains($entity));
        $this->assertNotNull($entity->getId());

        // 测试查找
        $found = $this->repository->find($entity->getId());
        $this->assertInstanceOf(DiscountRule::class, $found);
        $this->assertEquals($entity->getActivityId(), $found->getActivityId());
        $this->assertEquals($entity->getDiscountType(), $found->getDiscountType());

        // 测试删除
        $entityId = $entity->getId();
        $this->assertNotNull($entityId, 'Entity should have ID before removal');

        $this->repository->remove($entity, true);
        $this->assertNull($this->repository->find($entityId));
    }
}
