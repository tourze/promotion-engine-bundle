<?php

namespace PromotionEngineBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use PromotionEngineBundle\Entity\DiscountFreeCondition;

/**
 * @method DiscountFreeCondition|null find($id, $lockMode = null, $lockVersion = null)
 * @method DiscountFreeCondition|null findOneBy(array $criteria, array $orderBy = null)
 * @method DiscountFreeCondition[]    findAll()
 * @method DiscountFreeCondition[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DiscountFreeConditionRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DiscountFreeCondition::class);
    }
}
