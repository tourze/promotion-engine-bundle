<?php

namespace PromotionEngineBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PromotionEngineBundle\Entity\Campaign;
use PromotionEngineBundle\Entity\Discount;
use PromotionEngineBundle\Enum\DiscountType;
use PromotionEngineBundle\Repository\CampaignRepository;
use PromotionEngineBundle\Repository\DiscountRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(DiscountRepository::class)]
#[RunTestsInSeparateProcesses]
final class DiscountRepositoryTest extends AbstractRepositoryTestCase
{
    protected function onSetUp(): void
    {
        // 基础设置，由父类处理
    }

    public function testRepositoryIsServiceEntityRepository(): void
    {
        $repository = self::getService(DiscountRepository::class);
        $this->assertInstanceOf(ServiceEntityRepository::class, $repository);
    }

    public function testRepositoryConstruction(): void
    {
        $repository = self::getService(DiscountRepository::class);
        $this->assertInstanceOf(DiscountRepository::class, $repository);
    }

    public function testSave(): void
    {
        $repository = self::getService(DiscountRepository::class);

        $discount = $this->createTestDiscount();
        $repository->save($discount);

        $foundDiscount = $repository->find($discount->getId());
        $this->assertNotNull($foundDiscount);
        $this->assertInstanceOf(Discount::class, $foundDiscount);
        $this->assertSame(DiscountType::REDUCTION, $foundDiscount->getType());
        $this->assertSame('100.00', $foundDiscount->getValue());
        $this->assertSame('测试优惠', $foundDiscount->getRemark());
    }

    public function testRemove(): void
    {
        $repository = self::getService(DiscountRepository::class);

        $discount = $this->createTestDiscount();
        $repository->save($discount);
        $id = $discount->getId();

        $repository->remove($discount);

        $foundDiscount = $repository->find($id);
        $this->assertNull($foundDiscount);
    }

    public function testFindByWithNullCriteria(): void
    {
        $repository = self::getService(DiscountRepository::class);

        $this->clearExistingData($repository);

        $discount1 = $this->createTestDiscount(DiscountType::REDUCTION, '100.00', null);
        $repository->save($discount1);

        $discountsWithNullRemark = $repository->createQueryBuilder('d')
            ->where('d.remark IS NULL')
            ->getQuery()
            ->getResult()
        ;

        $this->assertIsArray($discountsWithNullRemark);
        $this->assertGreaterThanOrEqual(1, count($discountsWithNullRemark));

        $found = false;
        foreach ($discountsWithNullRemark as $discount) {
            if (is_object($discount) && method_exists($discount, 'getType') && method_exists($discount, 'getValue')
                && DiscountType::REDUCTION === $discount->getType() && '100.00' === $discount->getValue()) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
    }

    public function testFindOneByAssociationCampaignShouldReturnMatchingEntity(): void
    {
        $repository = self::getService(DiscountRepository::class);

        $this->clearExistingData($repository);

        $campaign1 = $this->createTestCampaign();
        $campaign2 = $this->createTestCampaign();

        $discount1 = new Discount();
        $discount1->setCampaign($campaign1);
        $discount1->setType(DiscountType::REDUCTION);
        $discount1->setValue('100.00');
        $discount1->setIsLimited(false);
        $discount1->setQuota(0);
        $discount1->setNumber(0);
        $discount1->setRemark('测试优惠1');
        $repository->save($discount1);

        $discount2 = new Discount();
        $discount2->setCampaign($campaign2);
        $discount2->setType(DiscountType::DISCOUNT);
        $discount2->setValue('0.85');
        $discount2->setIsLimited(false);
        $discount2->setQuota(0);
        $discount2->setNumber(0);
        $discount2->setRemark('测试优惠2');
        $repository->save($discount2);

        $foundByCampaign = $repository->findOneBy(['campaign' => $campaign1]);
        $this->assertNotNull($foundByCampaign);
        $this->assertInstanceOf(Discount::class, $foundByCampaign);
        $this->assertSame('100.00', $foundByCampaign->getValue());
        $this->assertSame($discount1->getId(), $foundByCampaign->getId());
    }

    public function testCountByAssociationCampaignShouldReturnCorrectNumber(): void
    {
        $repository = self::getService(DiscountRepository::class);

        $this->clearExistingData($repository);

        $campaign1 = $this->createTestCampaign();
        $campaign2 = $this->createTestCampaign();

        for ($i = 1; $i <= 4; ++$i) {
            $discount = new Discount();
            $discount->setCampaign($campaign1);
            $discount->setType(DiscountType::REDUCTION);
            $discount->setValue((string) (100 * $i));
            $discount->setIsLimited(false);
            $discount->setQuota(0);
            $discount->setNumber(0);
            $discount->setRemark("测试优惠{$i}");
            $repository->save($discount);
        }

        for ($i = 1; $i <= 2; ++$i) {
            $discount = new Discount();
            $discount->setCampaign($campaign2);
            $discount->setType(DiscountType::DISCOUNT);
            $discount->setValue('0.' . (80 + $i));
            $discount->setIsLimited(false);
            $discount->setQuota(0);
            $discount->setNumber(0);
            $discount->setRemark("其他优惠{$i}");
            $repository->save($discount);
        }

        $count = $repository->count(['campaign' => $campaign1]);
        $this->assertSame(4, $count);
    }

    public function testCountWithNullCriteria(): void
    {
        $repository = self::getService(DiscountRepository::class);

        $this->clearExistingData($repository);

        $discount1 = $this->createTestDiscount(DiscountType::REDUCTION, '100.00', null);
        $repository->save($discount1);

        $nonNullTypeCount = (int) $repository->createQueryBuilder('d')
            ->select('COUNT(d.id)')
            ->where('d.type IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult()
        ;

        $this->assertGreaterThanOrEqual(1, $nonNullTypeCount);

        $totalCount = $repository->count([]);
        $this->assertSame(1, $totalCount);
        $this->assertSame($totalCount, $nonNullTypeCount);
    }

    protected function createNewEntity(): object
    {
        return $this->createTestDiscount();
    }

    protected function getRepository(): DiscountRepository
    {
        return self::getService(DiscountRepository::class);
    }

    private function createTestCampaign(): Campaign
    {
        $campaign = new Campaign();
        $campaign->setTitle('测试活动');
        $campaign->setStartTime(new \DateTimeImmutable('2024-01-01'));
        $campaign->setEndTime(new \DateTimeImmutable('2024-12-31'));

        $campaignRepository = self::getService(CampaignRepository::class);
        $campaignRepository->save($campaign);

        return $campaign;
    }

    private function createTestDiscount(
        DiscountType $type = DiscountType::REDUCTION,
        string $value = '100.00',
        ?string $remark = '测试优惠',
    ): Discount {
        $campaign = $this->createTestCampaign();

        $discount = new Discount();
        $discount->setCampaign($campaign);
        $discount->setType($type);
        $discount->setValue($value);
        $discount->setIsLimited(false);
        $discount->setQuota(0);
        $discount->setNumber(0);
        $discount->setRemark($remark);

        return $discount;
    }

    private function clearExistingData(DiscountRepository $repository): void
    {
        $allDiscounts = $repository->findAll();
        foreach ($allDiscounts as $discount) {
            if ($discount instanceof Discount) {
                $repository->remove($discount);
            }
        }
    }
}
