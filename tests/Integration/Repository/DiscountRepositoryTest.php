<?php

namespace PromotionEngineBundle\Tests\Integration\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use PromotionEngineBundle\Repository\DiscountRepository;

class DiscountRepositoryTest extends TestCase
{
    public function testRepositoryIsServiceEntityRepository(): void
    {
        $this->assertInstanceOf(
            ServiceEntityRepository::class,
            new DiscountRepository($this->createMock(ManagerRegistry::class))
        );
    }

    public function testRepositoryExists(): void
    {
        $this->assertTrue(class_exists(DiscountRepository::class));
    }

    public function testRepositoryConstruction(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $repository = new DiscountRepository($registry);
        
        $this->assertInstanceOf(DiscountRepository::class, $repository);
    }
}