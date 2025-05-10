<?php

namespace PromotionEngineBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use PromotionEngineBundle\Entity\Participation;

/**
 * @method Participation|null find($id, $lockMode = null, $lockVersion = null)
 * @method Participation|null findOneBy(array $criteria, array $orderBy = null)
 * @method Participation[]    findAll()
 * @method Participation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ParticipationRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Participation::class);
    }
}
