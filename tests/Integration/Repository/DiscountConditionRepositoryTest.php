<?php

namespace PromotionEngineBundle\Tests\Integration\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use PromotionEngineBundle\Repository\DiscountConditionRepository;

class DiscountConditionRepositoryTest extends TestCase
{
    public function testRepositoryIsServiceEntityRepository(): void
    {
        $this->assertInstanceOf(
            ServiceEntityRepository::class,
            new DiscountConditionRepository($this->createMock(ManagerRegistry::class))
        );
    }

    public function testRepositoryExists(): void
    {
        $this->assertTrue(class_exists(DiscountConditionRepository::class));
    }

    public function testRepositoryConstruction(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $repository = new DiscountConditionRepository($registry);
        
        $this->assertInstanceOf(DiscountConditionRepository::class, $repository);
    }
}