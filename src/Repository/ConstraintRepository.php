<?php

namespace PromotionEngineBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use PromotionEngineBundle\Entity\Constraint;

/**
 * @method Constraint|null find($id, $lockMode = null, $lockVersion = null)
 * @method Constraint|null findOneBy(array $criteria, array $orderBy = null)
 * @method Constraint[]    findAll()
 * @method Constraint[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ConstraintRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Constraint::class);
    }
}
