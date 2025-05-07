<?php

namespace PromotionEngineBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use DoctrineEnhanceBundle\Repository\CommonRepositoryAware;
use PromotionEngineBundle\Entity\DiscountFreeCondition;

/**
 * @method DiscountFreeCondition|null find($id, $lockMode = null, $lockVersion = null)
 * @method DiscountFreeCondition|null findOneBy(array $criteria, array $orderBy = null)
 * @method DiscountFreeCondition[]    findAll()
 * @method DiscountFreeCondition[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DiscountFreeConditionRepository extends ServiceEntityRepository
{
    use CommonRepositoryAware;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DiscountFreeCondition::class);
    }

    public function add(DiscountFreeCondition $entity, bool $flush = true): void
    {
        $this->_em->persist($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    public function update(bool $flush = true): void
    {
        $this->_em->flush();
    }
}
