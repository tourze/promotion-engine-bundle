<?php

namespace PromotionEngineBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PromotionEngineBundle\Entity\Campaign;
use PromotionEngineBundle\Entity\Constraint;
use PromotionEngineBundle\Enum\CompareType;
use PromotionEngineBundle\Enum\LimitType;
use PromotionEngineBundle\Repository\CampaignRepository;
use PromotionEngineBundle\Repository\ConstraintRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(ConstraintRepository::class)]
#[RunTestsInSeparateProcesses]
final class ConstraintRepositoryTest extends AbstractRepositoryTestCase
{
    protected function onSetUp(): void
    {
        // 基础设置，由父类处理
    }

    public function testRepositoryIsServiceEntityRepository(): void
    {
        $repository = self::getService(ConstraintRepository::class);
        $this->assertInstanceOf(ServiceEntityRepository::class, $repository);
    }

    public function testRepositoryConstruction(): void
    {
        $repository = self::getService(ConstraintRepository::class);
        $this->assertInstanceOf(ConstraintRepository::class, $repository);
    }

    // findBy() 方法测试

    // findOneBy() 方法测试

    // findAll() 方法测试

    // find() 方法测试

    // save() 和 remove() 方法测试
    public function testSaveShouldPersistEntity(): void
    {
        $repository = self::getService(ConstraintRepository::class);
        $constraint = $this->createTestConstraint();

        $repository->save($constraint);

        $this->assertNotNull($constraint->getId());

        $foundConstraint = $repository->find($constraint->getId());
        $this->assertInstanceOf(Constraint::class, $foundConstraint);
        $this->assertSame($constraint->getRangeValue(), $foundConstraint->getRangeValue());
    }

    public function testRemoveShouldDeleteEntity(): void
    {
        $repository = self::getService(ConstraintRepository::class);
        $constraint = $this->createTestConstraint();
        $repository->save($constraint);
        $constraintId = $constraint->getId();

        $repository->remove($constraint);

        $foundConstraint = $repository->find($constraintId);
        $this->assertNull($foundConstraint);
    }

    // 关联查询测试
    public function testFindOneByAssociationCampaignShouldReturnMatchingEntity(): void
    {
        $repository = self::getService(ConstraintRepository::class);
        $campaignRepository = self::getService(CampaignRepository::class);

        // 创建活动
        $campaign = $this->createTestCampaign();
        $campaignRepository->save($campaign);

        // 创建约束并关联活动
        $constraint = $this->createTestConstraint();
        $constraint->setCampaign($campaign);
        $repository->save($constraint);

        $foundConstraint = $repository->findOneBy(['campaign' => $campaign]);
        $this->assertInstanceOf(Constraint::class, $foundConstraint);
        $this->assertSame($campaign->getId(), $foundConstraint->getCampaign()?->getId());
    }

    public function testCountByAssociationCampaignShouldReturnCorrectNumber(): void
    {
        $repository = self::getService(ConstraintRepository::class);
        $campaignRepository = self::getService(CampaignRepository::class);

        // 创建活动
        $campaign = $this->createTestCampaign();
        $campaignRepository->save($campaign);

        // 清理可能存在的数据
        foreach ($repository->findBy(['campaign' => $campaign]) as $entity) {
            if ($entity instanceof Constraint) {
                $repository->remove($entity);
            }
        }

        // 创建4个属于该活动的约束
        for ($i = 0; $i < 4; ++$i) {
            $constraint = $this->createTestConstraint();
            $constraint->setCampaign($campaign);
            $constraint->setRangeValue("value_for_campaign_count_{$i}");
            $repository->save($constraint);
        }

        // 创建2个属于其他活动的约束
        for ($i = 0; $i < 2; ++$i) {
            $otherCampaign = $this->createTestCampaign();
            $otherCampaign->setTitle("other_campaign_{$i}");
            $campaignRepository->save($otherCampaign);

            $constraint = $this->createTestConstraint();
            $constraint->setCampaign($otherCampaign);
            $constraint->setRangeValue("value_for_other_campaign_{$i}");
            $repository->save($constraint);
        }

        $count = $repository->count(['campaign' => $campaign]);
        $this->assertSame(4, $count);
    }

    // findOneBy 排序逻辑测试

    // IS NULL 查询相关测试

    protected function createNewEntity(): object
    {
        return $this->createTestConstraint();
    }

    protected function getRepository(): ConstraintRepository
    {
        return self::getService(ConstraintRepository::class);
    }

    private function createTestCampaign(): Campaign
    {
        $campaign = new Campaign();
        $campaign->setTitle('test_campaign_' . uniqid());
        $campaign->setDescription('Test Campaign Description');
        $campaign->setStartTime(new \DateTimeImmutable('2024-01-01 00:00:00'));
        $campaign->setEndTime(new \DateTimeImmutable('2024-12-31 23:59:59'));
        $campaign->setExclusive(false);
        $campaign->setWeight(0);
        $campaign->setValid(true);

        return $campaign;
    }

    private function createTestConstraint(): Constraint
    {
        $campaignRepository = self::getService(CampaignRepository::class);
        $campaign = $this->createTestCampaign();
        $campaignRepository->save($campaign);

        $constraint = new Constraint();
        $constraint->setCampaign($campaign);
        $constraint->setCompareType(CompareType::EQUAL);
        $constraint->setLimitType(LimitType::ORDER_PRICE);
        $constraint->setRangeValue('100.00');
        $constraint->setValid(true);

        return $constraint;
    }
}
