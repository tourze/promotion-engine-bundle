<?php

namespace PromotionEngineBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PromotionEngineBundle\Entity\Campaign;
use PromotionEngineBundle\Entity\Discount;
use PromotionEngineBundle\Entity\DiscountFreeCondition;
use PromotionEngineBundle\Enum\DiscountType;
use PromotionEngineBundle\Repository\CampaignRepository;
use PromotionEngineBundle\Repository\DiscountFreeConditionRepository;
use PromotionEngineBundle\Repository\DiscountRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(DiscountFreeConditionRepository::class)]
#[RunTestsInSeparateProcesses]
final class DiscountFreeConditionRepositoryTest extends AbstractRepositoryTestCase
{
    protected function onSetUp(): void
    {
        // 基础设置，由父类处理
    }

    public function testRepositoryIsServiceEntityRepository(): void
    {
        $repository = self::getService(DiscountFreeConditionRepository::class);
        $this->assertInstanceOf(ServiceEntityRepository::class, $repository);
    }

    public function testRepositoryConstruction(): void
    {
        $repository = self::getService(DiscountFreeConditionRepository::class);
        $this->assertInstanceOf(DiscountFreeConditionRepository::class, $repository);
    }

    public function testSave(): void
    {
        $repository = self::getService(DiscountFreeConditionRepository::class);

        $freeCondition = $this->createTestDiscountFreeCondition();
        $repository->save($freeCondition);

        $foundCondition = $repository->find($freeCondition->getId());
        $this->assertNotNull($foundCondition);
        $this->assertInstanceOf(DiscountFreeCondition::class, $foundCondition);
        $this->assertSame('5', $foundCondition->getPurchaseQuantity());
        $this->assertSame('1', $foundCondition->getFreeQuantity());
    }

    public function testRemove(): void
    {
        $repository = self::getService(DiscountFreeConditionRepository::class);

        $freeCondition = $this->createTestDiscountFreeCondition();
        $repository->save($freeCondition);
        $id = $freeCondition->getId();

        $repository->remove($freeCondition);

        $foundCondition = $repository->find($id);
        $this->assertNull($foundCondition);
    }

    public function testFindByWithNullCriteria(): void
    {
        $repository = self::getService(DiscountFreeConditionRepository::class);

        $this->clearExistingData($repository);

        $condition1 = $this->createTestDiscountFreeCondition('5', '1');
        $repository->save($condition1);

        $conditionsWithData = $repository->createQueryBuilder('dfc')
            ->where('dfc.purchaseQuantity IS NOT NULL')
            ->getQuery()
            ->getResult()
        ;

        $this->assertIsArray($conditionsWithData);
        $this->assertGreaterThanOrEqual(1, count($conditionsWithData));

        $found = false;
        foreach ($conditionsWithData as $condition) {
            if (is_object($condition) && method_exists($condition, 'getPurchaseQuantity') && '5' === $condition->getPurchaseQuantity()) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
    }

    public function testFindOneByAssociationDiscountShouldReturnMatchingEntity(): void
    {
        $repository = self::getService(DiscountFreeConditionRepository::class);

        $this->clearExistingData($repository);

        $discount1 = $this->createTestDiscount();
        $discount2 = $this->createTestDiscount();

        $condition1 = new DiscountFreeCondition();
        $condition1->setDiscount($discount1);
        $condition1->setPurchaseQuantity('5');
        $condition1->setFreeQuantity('1');
        $repository->save($condition1);

        $condition2 = new DiscountFreeCondition();
        $condition2->setDiscount($discount2);
        $condition2->setPurchaseQuantity('10');
        $condition2->setFreeQuantity('2');
        $repository->save($condition2);

        $foundByDiscount = $repository->findOneBy(['discount' => $discount1]);
        $this->assertNotNull($foundByDiscount);
        $this->assertInstanceOf(DiscountFreeCondition::class, $foundByDiscount);
        $this->assertSame('5', $foundByDiscount->getPurchaseQuantity());
        $this->assertSame($condition1->getId(), $foundByDiscount->getId());
    }

    public function testCountByAssociationDiscountShouldReturnCorrectNumber(): void
    {
        $repository = self::getService(DiscountFreeConditionRepository::class);

        $this->clearExistingData($repository);

        // 创建4个独立的discount和对应的condition
        for ($i = 1; $i <= 4; ++$i) {
            $condition = new DiscountFreeCondition();
            $condition->setDiscount($this->createTestDiscount());
            $condition->setPurchaseQuantity((string) (5 * $i));
            $condition->setFreeQuantity((string) $i);
            $repository->save($condition);
        }

        // 再创建2个独立的discount和对应的condition
        for ($i = 1; $i <= 2; ++$i) {
            $condition = new DiscountFreeCondition();
            $condition->setDiscount($this->createTestDiscount());
            $condition->setPurchaseQuantity((string) (10 * $i));
            $condition->setFreeQuantity((string) $i);
            $repository->save($condition);
        }

        // 验证总共创建了6个discount和对应的condition
        $discountRepository = self::getService(DiscountRepository::class);
        $allDiscounts = $discountRepository->findAll();
        $this->assertCount(6, $allDiscounts);

        $allConditions = $repository->findAll();
        $this->assertCount(6, $allConditions);
    }

    public function testCountWithNullCriteria(): void
    {
        $repository = self::getService(DiscountFreeConditionRepository::class);

        $this->clearExistingData($repository);

        $condition1 = $this->createTestDiscountFreeCondition('5', '1');
        $repository->save($condition1);

        $nonNullPurchaseQuantityCount = (int) $repository->createQueryBuilder('dfc')
            ->select('COUNT(dfc.id)')
            ->where('dfc.purchaseQuantity IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult()
        ;

        $this->assertGreaterThanOrEqual(1, $nonNullPurchaseQuantityCount);

        $totalCount = $repository->count([]);
        $this->assertSame(1, $totalCount);
        $this->assertSame($totalCount, $nonNullPurchaseQuantityCount);
    }

    protected function createNewEntity(): object
    {
        return $this->createTestDiscountFreeCondition();
    }

    protected function getRepository(): DiscountFreeConditionRepository
    {
        return self::getService(DiscountFreeConditionRepository::class);
    }

    private function createTestDiscount(): Discount
    {
        $campaign = new Campaign();
        $campaign->setTitle('测试活动');
        $campaign->setStartTime(new \DateTimeImmutable('2024-01-01'));
        $campaign->setEndTime(new \DateTimeImmutable('2024-12-31'));

        $discount = new Discount();
        $discount->setCampaign($campaign);
        $discount->setType(DiscountType::BUY_N_GET_M);
        $discount->setValue('0.00');
        $discount->setIsLimited(false);
        $discount->setQuota(0);
        $discount->setNumber(0);
        $discount->setRemark('测试买N送M优惠');

        $campaignRepository = self::getService(CampaignRepository::class);
        $campaignRepository->save($campaign);

        $discountRepository = self::getService(DiscountRepository::class);
        $discountRepository->save($discount);

        return $discount;
    }

    private function createTestDiscountFreeCondition(
        string $purchaseQuantity = '5',
        string $freeQuantity = '1',
    ): DiscountFreeCondition {
        $discount = $this->createTestDiscount();

        $freeCondition = new DiscountFreeCondition();
        $freeCondition->setDiscount($discount);
        $freeCondition->setPurchaseQuantity($purchaseQuantity);
        $freeCondition->setFreeQuantity($freeQuantity);

        return $freeCondition;
    }

    private function clearExistingData(DiscountFreeConditionRepository $repository): void
    {
        $allConditions = $repository->findAll();
        foreach ($allConditions as $condition) {
            if ($condition instanceof DiscountFreeCondition) {
                $repository->remove($condition);
            }
        }
    }
}
