<?php

namespace PromotionEngineBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PromotionEngineBundle\Entity\TimeLimitActivity;
use PromotionEngineBundle\Enum\ActivityStatus;
use PromotionEngineBundle\Enum\ActivityType;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * @extends ServiceEntityRepository<TimeLimitActivity>
 */
#[AsRepository(entityClass: TimeLimitActivity::class)]
class TimeLimitActivityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TimeLimitActivity::class);
    }

    public function save(TimeLimitActivity $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(TimeLimitActivity $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return array<TimeLimitActivity>
     */
    public function findActiveActivities(?\DateTimeInterface $now = null): array
    {
        $now ??= new \DateTimeImmutable();

        /** @var array<TimeLimitActivity> */
        return $this->createQueryBuilder('a')
            ->where('a.valid = :valid')
            ->andWhere('a.startTime <= :now')
            ->andWhere('a.endTime >= :now')
            ->setParameter('valid', true)
            ->setParameter('now', $now)
            ->orderBy('a.priority', 'DESC')
            ->addOrderBy('a.createTime', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return array<TimeLimitActivity>
     */
    public function findActivitiesNeedingStatusUpdate(?\DateTimeInterface $now = null): array
    {
        $now ??= new \DateTimeImmutable();

        $qb = $this->createQueryBuilder('a')
            ->where('a.valid = :valid')
            ->setParameter('valid', true)
        ;

        $qb->andWhere(
            $qb->expr()->orX(
                // PENDING状态且开始时间已到 -> 应该变ACTIVE
                $qb->expr()->andX(
                    $qb->expr()->eq('a.status', ':pendingStatus'),
                    $qb->expr()->lte('a.startTime', ':now')
                ),
                // ACTIVE状态且结束时间已过 -> 应该变FINISHED
                $qb->expr()->andX(
                    $qb->expr()->eq('a.status', ':activeStatus'),
                    $qb->expr()->lt('a.endTime', ':now')
                ),
                // ACTIVE状态但开始时间还没到 -> 应该变PENDING
                $qb->expr()->andX(
                    $qb->expr()->eq('a.status', ':activeStatus'),
                    $qb->expr()->gt('a.startTime', ':now')
                )
            )
        );

        $qb->setParameter('pendingStatus', ActivityStatus::PENDING->value)
            ->setParameter('activeStatus', ActivityStatus::ACTIVE->value)
            ->setParameter('now', $now)
        ;

        /** @var array<TimeLimitActivity> */
        return $qb->getQuery()->getResult();
    }

    /**
     * @param string[] $productIds
     * @return array<TimeLimitActivity>
     */
    public function findConflictingActivities(
        array $productIds,
        \DateTimeInterface $startTime,
        \DateTimeInterface $endTime,
        ?string $excludeActivityId = null,
    ): array {
        if ([] === $productIds) {
            return [];
        }

        $qb = $this->createQueryBuilder('a')
            ->where('a.valid = :valid')
            ->andWhere('a.exclusive = :exclusive')
            ->andWhere('a.startTime < :endTime')
            ->andWhere('a.endTime > :startTime')
        ;

        $productIdsOrWhere = [];
        foreach ($productIds as $index => $productId) {
            $productIdsOrWhere[] = "a.productIds LIKE :productId{$index}";
            $qb->setParameter("productId{$index}", '%"' . $productId . '"%');
        }
        $qb->andWhere($qb->expr()->orX(...$productIdsOrWhere));

        $qb->setParameter('valid', true)
            ->setParameter('exclusive', true)
            ->setParameter('startTime', $startTime)
            ->setParameter('endTime', $endTime)
        ;

        if (null !== $excludeActivityId) {
            $qb->andWhere('a.id != :excludeId')
                ->setParameter('excludeId', $excludeActivityId)
            ;
        }

        /** @var array<TimeLimitActivity> */
        return $qb->getQuery()->getResult();
    }

    /**
     * @param string[] $productIds
     * @return array<TimeLimitActivity>
     */
    public function findActivitiesByProductIds(array $productIds, ?\DateTimeInterface $now = null): array
    {
        if ([] === $productIds) {
            return [];
        }

        $now ??= new \DateTimeImmutable();

        $qb = $this->createQueryBuilder('a')
            ->where('a.valid = :valid')
            ->andWhere('a.startTime <= :now')
            ->andWhere('a.endTime >= :now')
        ;

        $productIdsOrWhere = [];
        foreach ($productIds as $index => $productId) {
            $productIdsOrWhere[] = "a.productIds LIKE :productId{$index}";
            $qb->setParameter("productId{$index}", '%"' . $productId . '"%');
        }
        $qb->andWhere($qb->expr()->orX(...$productIdsOrWhere));

        $qb->setParameter('valid', true)
            ->setParameter('now', $now)
            ->orderBy('a.priority', 'DESC')
            ->addOrderBy('a.createTime', 'ASC')
        ;

        /** @var array<TimeLimitActivity> */
        return $qb->getQuery()->getResult();
    }

    public function findByActivityType(ActivityType $activityType): QueryBuilder
    {
        return $this->createQueryBuilder('a')
            ->where('a.activityType = :type')
            ->andWhere('a.valid = :valid')
            ->setParameter('type', $activityType)
            ->setParameter('valid', true)
            ->orderBy('a.priority', 'DESC')
            ->addOrderBy('a.createTime', 'DESC')
        ;
    }

    /**
     * @return array<TimeLimitActivity>
     */
    public function findSeckillActivities(?\DateTimeInterface $now = null): array
    {
        /** @var array<TimeLimitActivity> */
        return $this->findByActivityType(ActivityType::LIMITED_TIME_SECKILL)
            ->getQuery()
            ->getResult()
        ;
    }

    public function findHighestPriorityActivityForProduct(string $productId, ?\DateTimeInterface $now = null): ?TimeLimitActivity
    {
        $now ??= new \DateTimeImmutable();

        try {
            /** @var TimeLimitActivity */
            return $this->createQueryBuilder('a')
                ->where('a.valid = :valid')
                ->andWhere('a.startTime <= :now')
                ->andWhere('a.endTime >= :now')
                ->andWhere('a.productIds LIKE :productId')
                ->setParameter('valid', true)
                ->setParameter('now', $now)
                ->setParameter('productId', '%"' . $productId . '"%')
                ->orderBy('a.priority', 'DESC')
                ->addOrderBy('a.createTime', 'ASC')
                ->setMaxResults(1)
                ->getQuery()
                ->getSingleResult()
            ;
        } catch (NoResultException) {
            return null;
        }
    }

    public function countActiveActivities(?\DateTimeInterface $now = null): int
    {
        $now ??= new \DateTimeImmutable();

        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.valid = :valid')
            ->andWhere('a.startTime <= :now')
            ->andWhere('a.endTime >= :now')
            ->setParameter('valid', true)
            ->setParameter('now', $now)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /**
     * @param string[] $ids
     * @return array<TimeLimitActivity>
     */
    public function findByIds(array $ids): array
    {
        if ([] === $ids) {
            return [];
        }

        /** @var array<TimeLimitActivity> */
        return $this->createQueryBuilder('a')
            ->where('a.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult()
        ;
    }
}
