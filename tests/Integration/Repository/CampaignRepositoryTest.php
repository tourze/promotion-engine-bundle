<?php

namespace PromotionEngineBundle\Tests\Integration\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use PromotionEngineBundle\Repository\CampaignRepository;

class CampaignRepositoryTest extends TestCase
{
    public function testRepositoryIsServiceEntityRepository(): void
    {
        $this->assertInstanceOf(
            ServiceEntityRepository::class,
            new CampaignRepository($this->createMock(ManagerRegistry::class))
        );
    }

    public function testRepositoryExists(): void
    {
        $this->assertTrue(class_exists(CampaignRepository::class));
    }

    public function testRepositoryConstruction(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $repository = new CampaignRepository($registry);
        
        $this->assertInstanceOf(CampaignRepository::class, $repository);
    }
}