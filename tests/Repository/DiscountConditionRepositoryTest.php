<?php

namespace PromotionEngineBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PromotionEngineBundle\Entity\Campaign;
use PromotionEngineBundle\Entity\Discount;
use PromotionEngineBundle\Entity\DiscountCondition;
use PromotionEngineBundle\Enum\DiscountType;
use PromotionEngineBundle\Repository\CampaignRepository;
use PromotionEngineBundle\Repository\DiscountConditionRepository;
use PromotionEngineBundle\Repository\DiscountRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(DiscountConditionRepository::class)]
#[RunTestsInSeparateProcesses]
final class DiscountConditionRepositoryTest extends AbstractRepositoryTestCase
{
    protected function onSetUp(): void
    {
        // 基础设置，由父类处理
    }

    public function testRepositoryIsServiceEntityRepository(): void
    {
        $repository = self::getService(DiscountConditionRepository::class);
        $this->assertInstanceOf(ServiceEntityRepository::class, $repository);
    }

    public function testRepositoryConstruction(): void
    {
        $repository = self::getService(DiscountConditionRepository::class);
        $this->assertInstanceOf(DiscountConditionRepository::class, $repository);
    }

    public function testSave(): void
    {
        $repository = self::getService(DiscountConditionRepository::class);

        $discountCondition = $this->createTestDiscountCondition();
        $repository->save($discountCondition);

        $foundCondition = $repository->find($discountCondition->getId());
        $this->assertNotNull($foundCondition);
        $this->assertInstanceOf(DiscountCondition::class, $foundCondition);
        $this->assertSame('测试条件1', $foundCondition->getCondition1());
        $this->assertSame('测试条件2', $foundCondition->getCondition2());
        $this->assertSame('测试条件3', $foundCondition->getCondition3());
    }

    public function testRemove(): void
    {
        $repository = self::getService(DiscountConditionRepository::class);

        $discountCondition = $this->createTestDiscountCondition();
        $repository->save($discountCondition);
        $id = $discountCondition->getId();

        $repository->remove($discountCondition);

        $foundCondition = $repository->find($id);
        $this->assertNull($foundCondition);
    }

    public function testFindByWithNullCriteria(): void
    {
        $repository = self::getService(DiscountConditionRepository::class);

        $this->clearExistingData($repository);

        $condition1 = $this->createTestDiscountCondition('空值测试条件', '空值测试条件2', null);
        $repository->save($condition1);

        $conditionsWithNullCondition3 = $repository->createQueryBuilder('dc')
            ->where('dc.condition3 IS NULL')
            ->getQuery()
            ->getResult()
        ;

        $this->assertIsArray($conditionsWithNullCondition3);
        $this->assertGreaterThanOrEqual(1, count($conditionsWithNullCondition3));

        $found = false;
        foreach ($conditionsWithNullCondition3 as $condition) {
            if (is_object($condition) && method_exists($condition, 'getCondition1') && '空值测试条件' === $condition->getCondition1()) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
    }

    public function testCountWithNullCriteria(): void
    {
        $repository = self::getService(DiscountConditionRepository::class);

        $this->clearExistingData($repository);

        $condition1 = $this->createTestDiscountCondition('空值计数测试', '空值计数测试2', null);
        $repository->save($condition1);

        $nonNullCondition1Count = (int) $repository->createQueryBuilder('dc')
            ->select('COUNT(dc.id)')
            ->where('dc.condition1 IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult()
        ;

        $this->assertGreaterThanOrEqual(1, $nonNullCondition1Count);

        $totalCount = $repository->count([]);
        $this->assertSame(1, $totalCount);
        $this->assertSame($totalCount, $nonNullCondition1Count);
    }

    public function testFindOneByOrderedByCondition1ShouldReturnCorrectEntity(): void
    {
        $repository = self::getService(DiscountConditionRepository::class);

        $this->clearExistingData($repository);

        $condition1 = $this->createTestDiscountCondition('Zebra条件', '条件2A', '条件3A');
        $repository->save($condition1);

        $condition2 = $this->createTestDiscountCondition('Apple条件', '条件2B', '条件3B');
        $repository->save($condition2);

        $condition3 = $this->createTestDiscountCondition('Middle条件', '条件2C', '条件3C');
        $repository->save($condition3);

        $firstConditionAsc = $repository->findOneBy([], ['condition1' => 'ASC']);
        $this->assertNotNull($firstConditionAsc);
        $this->assertInstanceOf(DiscountCondition::class, $firstConditionAsc);
        $this->assertSame('Apple条件', $firstConditionAsc->getCondition1());

        $firstConditionDesc = $repository->findOneBy([], ['condition1' => 'DESC']);
        $this->assertNotNull($firstConditionDesc);
        $this->assertInstanceOf(DiscountCondition::class, $firstConditionDesc);
        $this->assertSame('Zebra条件', $firstConditionDesc->getCondition1());
    }

    public function testFindOneByAssociationDiscountShouldReturnMatchingEntity(): void
    {
        $repository = self::getService(DiscountConditionRepository::class);

        $this->clearExistingData($repository);

        $discount1 = $this->createTestDiscount();
        $discount2 = $this->createTestDiscount();

        $condition1 = new DiscountCondition();
        $condition1->setDiscount($discount1);
        $condition1->setCondition1('条件A');
        $condition1->setCondition2('条件B');
        $repository->save($condition1);

        $condition2 = new DiscountCondition();
        $condition2->setDiscount($discount2);
        $condition2->setCondition1('条件C');
        $condition2->setCondition2('条件D');
        $repository->save($condition2);

        $foundByDiscount = $repository->findOneBy(['discount' => $discount1]);
        $this->assertNotNull($foundByDiscount);
        $this->assertInstanceOf(DiscountCondition::class, $foundByDiscount);
        $this->assertSame('条件A', $foundByDiscount->getCondition1());
        $this->assertSame($condition1->getId(), $foundByDiscount->getId());
    }

    public function testCountByAssociationDiscountShouldReturnCorrectNumber(): void
    {
        $repository = self::getService(DiscountConditionRepository::class);

        $this->clearExistingData($repository);

        $discount1 = $this->createTestDiscount();
        $discount2 = $this->createTestDiscount();

        for ($i = 1; $i <= 4; ++$i) {
            $condition = new DiscountCondition();
            $condition->setDiscount($discount1);
            $condition->setCondition1("条件{$i}");
            $condition->setCondition2("条件B{$i}");
            $repository->save($condition);
        }

        for ($i = 1; $i <= 2; ++$i) {
            $condition = new DiscountCondition();
            $condition->setDiscount($discount2);
            $condition->setCondition1("其他条件{$i}");
            $condition->setCondition2("其他条件B{$i}");
            $repository->save($condition);
        }

        $count = $repository->count(['discount' => $discount1]);
        $this->assertSame(4, $count);
    }

    protected function createNewEntity(): object
    {
        return $this->createTestDiscountCondition();
    }

    protected function getRepository(): DiscountConditionRepository
    {
        return self::getService(DiscountConditionRepository::class);
    }

    private function createTestDiscount(): Discount
    {
        $campaign = new Campaign();
        $campaign->setTitle('测试活动');
        $campaign->setStartTime(new \DateTimeImmutable('2024-01-01'));
        $campaign->setEndTime(new \DateTimeImmutable('2024-12-31'));

        $discount = new Discount();
        $discount->setCampaign($campaign);
        $discount->setType(DiscountType::REDUCTION);
        $discount->setValue('100.00');
        $discount->setIsLimited(false);
        $discount->setQuota(0);
        $discount->setNumber(0);
        $discount->setRemark('测试优惠');

        $campaignRepository = self::getService(CampaignRepository::class);
        $campaignRepository->save($campaign);

        $discountRepository = self::getService(DiscountRepository::class);
        $discountRepository->save($discount);

        return $discount;
    }

    private function createTestDiscountCondition(
        string $condition1 = '测试条件1',
        string $condition2 = '测试条件2',
        ?string $condition3 = '测试条件3',
    ): DiscountCondition {
        $discount = $this->createTestDiscount();

        $discountCondition = new DiscountCondition();
        $discountCondition->setDiscount($discount);
        $discountCondition->setCondition1($condition1);
        $discountCondition->setCondition2($condition2);
        $discountCondition->setCondition3($condition3);

        return $discountCondition;
    }

    private function clearExistingData(DiscountConditionRepository $repository): void
    {
        $allConditions = $repository->findAll();
        foreach ($allConditions as $condition) {
            if ($condition instanceof DiscountCondition) {
                $repository->remove($condition);
            }
        }
    }
}
