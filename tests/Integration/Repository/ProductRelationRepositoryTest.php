<?php

namespace PromotionEngineBundle\Tests\Integration\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use PromotionEngineBundle\Repository\ProductRelationRepository;

class ProductRelationRepositoryTest extends TestCase
{
    public function testRepositoryIsServiceEntityRepository(): void
    {
        $this->assertInstanceOf(
            ServiceEntityRepository::class,
            new ProductRelationRepository($this->createMock(ManagerRegistry::class))
        );
    }

    public function testRepositoryExists(): void
    {
        $this->assertTrue(class_exists(ProductRelationRepository::class));
    }

    public function testRepositoryConstruction(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $repository = new ProductRelationRepository($registry);
        
        $this->assertInstanceOf(ProductRelationRepository::class, $repository);
    }
}