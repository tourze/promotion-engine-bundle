<?php

namespace PromotionEngineBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use PromotionEngineBundle\Entity\DiscountCondition;

/**
 * @method DiscountCondition|null find($id, $lockMode = null, $lockVersion = null)
 * @method DiscountCondition|null findOneBy(array $criteria, array $orderBy = null)
 * @method DiscountCondition[]    findAll()
 * @method DiscountCondition[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DiscountConditionRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DiscountCondition::class);
    }
}
