<?php

namespace PromotionEngineBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use PromotionEngineBundle\Entity\ActivityProduct;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * @extends ServiceEntityRepository<ActivityProduct>
 */
#[AsRepository(entityClass: ActivityProduct::class)]
class ActivityProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ActivityProduct::class);
    }

    public function save(ActivityProduct $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ActivityProduct $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return array<ActivityProduct>
     */
    public function findByActivityId(string $activityId): array
    {
        /** @var array<ActivityProduct> */
        return $this->createQueryBuilder('ap')
            ->andWhere('ap.activity = :activityId')
            ->andWhere('ap.valid = :valid')
            ->setParameter('activityId', $activityId)
            ->setParameter('valid', true)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @param string[] $productIds
     * @return array<ActivityProduct>
     */
    public function findByProductIds(array $productIds): array
    {
        if ([] === $productIds) {
            return [];
        }

        /** @var array<ActivityProduct> */
        return $this->createQueryBuilder('ap')
            ->andWhere('ap.productId IN (:productIds)')
            ->andWhere('ap.valid = :valid')
            ->setParameter('productIds', $productIds)
            ->setParameter('valid', true)
            ->getQuery()
            ->getResult()
        ;
    }

    public function findByActivityAndProduct(string $activityId, string $productId): ?ActivityProduct
    {
        /** @var ActivityProduct|null */
        return $this->createQueryBuilder('ap')
            ->andWhere('ap.activity = :activityId')
            ->andWhere('ap.productId = :productId')
            ->andWhere('ap.valid = :valid')
            ->setParameter('activityId', $activityId)
            ->setParameter('productId', $productId)
            ->setParameter('valid', true)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * @param string[] $productIds
     * @return array<ActivityProduct>
     */
    public function findActiveByProductIds(array $productIds): array
    {
        if ([] === $productIds) {
            return [];
        }

        $now = new \DateTimeImmutable();

        /** @var array<ActivityProduct> */
        return $this->createQueryBuilder('ap')
            ->join('ap.activity', 'a')
            ->andWhere('ap.productId IN (:productIds)')
            ->andWhere('ap.valid = :valid')
            ->andWhere('a.valid = :valid')
            ->andWhere('a.startTime <= :now')
            ->andWhere('a.endTime >= :now')
            ->setParameter('productIds', $productIds)
            ->setParameter('valid', true)
            ->setParameter('now', $now)
            ->orderBy('a.priority', 'DESC')
            ->addOrderBy('a.createTime', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findActiveByProductId(string $productId): ?ActivityProduct
    {
        $results = $this->findActiveByProductIds([$productId]);

        return [] !== $results ? $results[0] : null;
    }

    /**
     * @return array<ActivityProduct>
     */
    public function findLowStockProducts(int $threshold = 10): array
    {
        /** @var array<ActivityProduct> */
        return $this->createQueryBuilder('ap')
            ->join('ap.activity', 'a')
            ->andWhere('ap.valid = :valid')
            ->andWhere('a.valid = :valid')
            ->andWhere('(ap.activityStock - ap.soldQuantity) <= :threshold')
            ->andWhere('(ap.activityStock - ap.soldQuantity) > 0')
            ->setParameter('valid', true)
            ->setParameter('threshold', $threshold)
            ->orderBy('ap.activityStock - ap.soldQuantity', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return array<ActivityProduct>
     */
    public function findSoldOutProducts(): array
    {
        /** @var array<ActivityProduct> */
        return $this->createQueryBuilder('ap')
            ->join('ap.activity', 'a')
            ->andWhere('ap.valid = :valid')
            ->andWhere('a.valid = :valid')
            ->andWhere('ap.soldQuantity >= ap.activityStock')
            ->setParameter('valid', true)
            ->getQuery()
            ->getResult()
        ;
    }

    public function getTotalSoldQuantityByActivity(string $activityId): int
    {
        $result = $this->createQueryBuilder('ap')
            ->select('SUM(ap.soldQuantity) as total')
            ->andWhere('ap.activity = :activityId')
            ->andWhere('ap.valid = :valid')
            ->setParameter('activityId', $activityId)
            ->setParameter('valid', true)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return (int) ($result ?? 0);
    }

    /**
     * @param string[] $activityIds
     */
    public function deleteByActivityIds(array $activityIds): void
    {
        if ([] === $activityIds) {
            return;
        }

        $this->createQueryBuilder('ap')
            ->update()
            ->set('ap.valid', ':valid')
            ->andWhere('ap.activity IN (:activityIds)')
            ->setParameter('valid', false)
            ->setParameter('activityIds', $activityIds)
            ->getQuery()
            ->execute()
        ;
    }

    /**
     * @param string[] $productIds
     */
    public function deleteByActivityAndProducts(string $activityId, array $productIds): void
    {
        if ([] === $productIds) {
            return;
        }

        $this->createQueryBuilder('ap')
            ->update()
            ->set('ap.valid', ':valid')
            ->andWhere('ap.activity = :activityId')
            ->andWhere('ap.productId IN (:productIds)')
            ->setParameter('valid', false)
            ->setParameter('activityId', $activityId)
            ->setParameter('productIds', $productIds)
            ->getQuery()
            ->execute()
        ;
    }
}
