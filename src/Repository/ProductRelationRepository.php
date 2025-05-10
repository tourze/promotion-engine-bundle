<?php

namespace PromotionEngineBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use PromotionEngineBundle\Entity\ProductRelation;

/**
 * @method ProductRelation|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProductRelation|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProductRelation[]    findAll()
 * @method ProductRelation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductRelationRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductRelation::class);
    }
}
