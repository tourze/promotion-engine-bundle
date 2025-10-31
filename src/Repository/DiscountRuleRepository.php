<?php

namespace PromotionEngineBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use PromotionEngineBundle\Entity\DiscountRule;
use PromotionEngineBundle\Enum\DiscountType;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * @extends ServiceEntityRepository<DiscountRule>
 */
#[AsRepository(entityClass: DiscountRule::class)]
class DiscountRuleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DiscountRule::class);
    }

    public function save(DiscountRule $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(DiscountRule $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return array<DiscountRule>
     */
    public function findByActivityId(string $activityId): array
    {
        /** @var array<DiscountRule> */
        return $this->createQueryBuilder('dr')
            ->andWhere('dr.activityId = :activityId')
            ->andWhere('dr.valid = :valid')
            ->setParameter('activityId', $activityId)
            ->setParameter('valid', true)
            ->orderBy('dr.createTime', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @param string[] $activityIds
     * @return array<DiscountRule>
     */
    public function findByActivityIds(array $activityIds): array
    {
        if ([] === $activityIds) {
            return [];
        }

        /** @var array<DiscountRule> */
        return $this->createQueryBuilder('dr')
            ->andWhere('dr.activityId IN (:activityIds)')
            ->andWhere('dr.valid = :valid')
            ->setParameter('activityIds', $activityIds)
            ->setParameter('valid', true)
            ->orderBy('dr.activityId', 'ASC')
            ->addOrderBy('dr.createTime', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findByActivityIdAndType(string $activityId, DiscountType $discountType): ?DiscountRule
    {
        /** @var DiscountRule|null */
        return $this->createQueryBuilder('dr')
            ->andWhere('dr.activityId = :activityId')
            ->andWhere('dr.discountType = :discountType')
            ->andWhere('dr.valid = :valid')
            ->setParameter('activityId', $activityId)
            ->setParameter('discountType', $discountType)
            ->setParameter('valid', true)
            ->orderBy('dr.createTime', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * @return array<DiscountRule>
     */
    public function findByDiscountType(DiscountType $discountType): array
    {
        /** @var array<DiscountRule> */
        return $this->createQueryBuilder('dr')
            ->andWhere('dr.discountType = :discountType')
            ->andWhere('dr.valid = :valid')
            ->setParameter('discountType', $discountType)
            ->setParameter('valid', true)
            ->orderBy('dr.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @param string[] $activityIds
     * @return array<string, DiscountRule[]>
     */
    public function findGroupedByActivityId(array $activityIds): array
    {
        if ([] === $activityIds) {
            return [];
        }

        $rules = $this->findByActivityIds($activityIds);
        $grouped = [];

        foreach ($rules as $rule) {
            $activityId = $rule->getActivityId();
            if (!isset($grouped[$activityId])) {
                $grouped[$activityId] = [];
            }
            $grouped[$activityId][] = $rule;
        }

        return $grouped;
    }
}
