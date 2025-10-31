<?php

namespace PromotionEngineBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PromotionEngineBundle\Entity\Campaign;
use PromotionEngineBundle\Entity\Discount;
use PromotionEngineBundle\Entity\ProductRelation;
use PromotionEngineBundle\Enum\DiscountType;
use PromotionEngineBundle\Repository\CampaignRepository;
use PromotionEngineBundle\Repository\DiscountRepository;
use PromotionEngineBundle\Repository\ProductRelationRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(ProductRelationRepository::class)]
#[RunTestsInSeparateProcesses]
final class ProductRelationRepositoryTest extends AbstractRepositoryTestCase
{
    protected function onSetUp(): void
    {
        // 基础设置，由父类处理
    }

    public function testRepositoryIsServiceEntityRepository(): void
    {
        $repository = self::getService(ProductRelationRepository::class);
        $this->assertInstanceOf(ServiceEntityRepository::class, $repository);
    }

    public function testRepositoryConstruction(): void
    {
        $repository = self::getService(ProductRelationRepository::class);
        $this->assertInstanceOf(ProductRelationRepository::class, $repository);
    }

    public function testSave(): void
    {
        $repository = self::getService(ProductRelationRepository::class);

        $productRelation = $this->createTestProductRelation();
        $repository->save($productRelation);

        $foundRelation = $repository->find($productRelation->getId());
        $this->assertNotNull($foundRelation);
        $this->assertInstanceOf(ProductRelation::class, $foundRelation);
        $this->assertSame('1001', $foundRelation->getSpuId());
        $this->assertSame('2001', $foundRelation->getSkuId());
        $this->assertSame(1, $foundRelation->getGiftQuantity());
    }

    public function testRemove(): void
    {
        $repository = self::getService(ProductRelationRepository::class);

        $productRelation = $this->createTestProductRelation();
        $repository->save($productRelation);
        $id = $productRelation->getId();

        $repository->remove($productRelation);

        $foundRelation = $repository->find($id);
        $this->assertNull($foundRelation);
    }

    public function testFindByWithNullCriteria(): void
    {
        $repository = self::getService(ProductRelationRepository::class);

        $this->clearExistingData($repository);

        $relation1 = $this->createTestProductRelation('1001', null, 5);
        $repository->save($relation1);

        $relationsWithNullSkuId = $repository->createQueryBuilder('pr')
            ->where('pr.skuId IS NULL')
            ->getQuery()
            ->getResult()
        ;

        $this->assertIsArray($relationsWithNullSkuId);
        $this->assertGreaterThanOrEqual(1, count($relationsWithNullSkuId));

        $found = false;
        foreach ($relationsWithNullSkuId as $relation) {
            if ('1001' === $relation->getSpuId()) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
    }

    public function testFindOneByAssociationDiscountShouldReturnMatchingEntity(): void
    {
        $repository = self::getService(ProductRelationRepository::class);

        $this->clearExistingData($repository);

        $discount1 = $this->createTestDiscount();
        $discount2 = $this->createTestDiscount();

        $relation1 = new ProductRelation();
        $relation1->setDiscount($discount1);
        $relation1->setSpuId('1001');
        $relation1->setSkuId('2001');
        $relation1->setTotal(0);
        $relation1->setGiftQuantity(5);
        $repository->save($relation1);

        $relation2 = new ProductRelation();
        $relation2->setDiscount($discount2);
        $relation2->setSpuId('1002');
        $relation2->setSkuId('2002');
        $relation2->setTotal(0);
        $relation2->setGiftQuantity(10);
        $repository->save($relation2);

        $foundByDiscount = $repository->findOneBy(['discount' => $discount1]);
        $this->assertNotNull($foundByDiscount);
        $this->assertInstanceOf(ProductRelation::class, $foundByDiscount);
        $this->assertSame('1001', $foundByDiscount->getSpuId());
        $this->assertSame($relation1->getId(), $foundByDiscount->getId());
    }

    public function testCountByAssociationDiscountShouldReturnCorrectNumber(): void
    {
        $repository = self::getService(ProductRelationRepository::class);

        $this->clearExistingData($repository);

        $discount1 = $this->createTestDiscount();
        $discount2 = $this->createTestDiscount();

        for ($i = 1; $i <= 4; ++$i) {
            $relation = new ProductRelation();
            $relation->setDiscount($discount1);
            $relation->setSpuId('100' . $i);
            $relation->setSkuId('200' . $i);
            $relation->setTotal(0);
            $relation->setGiftQuantity($i * 5);
            $repository->save($relation);
        }

        for ($i = 1; $i <= 2; ++$i) {
            $relation = new ProductRelation();
            $relation->setDiscount($discount2);
            $relation->setSpuId('200' . $i);
            $relation->setSkuId('300' . $i);
            $relation->setTotal(0);
            $relation->setGiftQuantity($i * 10);
            $repository->save($relation);
        }

        $count = $repository->count(['discount' => $discount1]);
        $this->assertSame(4, $count);
    }

    public function testCountWithNullCriteria(): void
    {
        $repository = self::getService(ProductRelationRepository::class);

        $this->clearExistingData($repository);

        $relation1 = $this->createTestProductRelation('1001', null, 5);
        $repository->save($relation1);

        $nonNullSpuIdCount = (int) $repository->createQueryBuilder('pr')
            ->select('COUNT(pr.id)')
            ->where('pr.spuId IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult()
        ;

        $this->assertGreaterThanOrEqual(1, $nonNullSpuIdCount);

        $totalCount = $repository->count([]);
        $this->assertSame(1, $totalCount);
        $this->assertSame($totalCount, $nonNullSpuIdCount);
    }

    protected function createNewEntity(): object
    {
        return $this->createTestProductRelation();
    }

    protected function getRepository(): ProductRelationRepository
    {
        return self::getService(ProductRelationRepository::class);
    }

    private function createTestDiscount(): Discount
    {
        $campaign = new Campaign();
        $campaign->setTitle('测试活动');
        $campaign->setStartTime(new \DateTimeImmutable('2024-01-01'));
        $campaign->setEndTime(new \DateTimeImmutable('2024-12-31'));

        $discount = new Discount();
        $discount->setCampaign($campaign);
        $discount->setType(DiscountType::BUY_GIVE);
        $discount->setValue('0.00');
        $discount->setIsLimited(false);
        $discount->setQuota(0);
        $discount->setNumber(0);
        $discount->setRemark('测试赠品优惠');

        $campaignRepository = self::getService(CampaignRepository::class);
        $campaignRepository->save($campaign);

        $discountRepository = self::getService(DiscountRepository::class);
        $discountRepository->save($discount);

        return $discount;
    }

    private function createTestProductRelation(
        string $spuId = '1001',
        ?string $skuId = '2001',
        int $giftQuantity = 1,
    ): ProductRelation {
        $discount = $this->createTestDiscount();

        $productRelation = new ProductRelation();
        $productRelation->setDiscount($discount);
        $productRelation->setSpuId($spuId);
        $productRelation->setSkuId($skuId);
        $productRelation->setTotal(0);
        $productRelation->setGiftQuantity($giftQuantity);

        return $productRelation;
    }

    private function clearExistingData(ProductRelationRepository $repository): void
    {
        $allRelations = $repository->findAll();
        foreach ($allRelations as $relation) {
            if ($relation instanceof ProductRelation) {
                $repository->remove($relation);
            }
        }
    }
}
