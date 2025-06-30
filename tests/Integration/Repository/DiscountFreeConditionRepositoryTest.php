<?php

namespace PromotionEngineBundle\Tests\Integration\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use PromotionEngineBundle\Repository\DiscountFreeConditionRepository;

class DiscountFreeConditionRepositoryTest extends TestCase
{
    public function testRepositoryIsServiceEntityRepository(): void
    {
        $this->assertInstanceOf(
            ServiceEntityRepository::class,
            new DiscountFreeConditionRepository($this->createMock(ManagerRegistry::class))
        );
    }

    public function testRepositoryExists(): void
    {
        $this->assertTrue(class_exists(DiscountFreeConditionRepository::class));
    }

    public function testRepositoryConstruction(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $repository = new DiscountFreeConditionRepository($registry);
        
        $this->assertInstanceOf(DiscountFreeConditionRepository::class, $repository);
    }
}