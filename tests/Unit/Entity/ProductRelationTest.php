<?php

namespace PromotionEngineBundle\Tests\Unit\Entity;

use PHPUnit\Framework\TestCase;
use PromotionEngineBundle\Entity\Discount;
use PromotionEngineBundle\Entity\ProductRelation;

class ProductRelationTest extends TestCase
{
    public function testGetSetDiscount(): void
    {
        $productRelation = new ProductRelation();
        $discount = new Discount();
        
        $productRelation->setDiscount($discount);
        $this->assertSame($discount, $productRelation->getDiscount());
        
        $productRelation->setDiscount(null);
        $this->assertNull($productRelation->getDiscount());
    }

    public function testGetSetSpuId(): void
    {
        $productRelation = new ProductRelation();
        $spuId = '123456789';
        
        $productRelation->setSpuId($spuId);
        $this->assertEquals($spuId, $productRelation->getSpuId());
    }

    public function testGetSetSkuId(): void
    {
        $productRelation = new ProductRelation();
        $skuId = '987654321';
        
        $productRelation->setSkuId($skuId);
        $this->assertEquals($skuId, $productRelation->getSkuId());
        
        $productRelation->setSkuId(null);
        $this->assertNull($productRelation->getSkuId());
    }

    public function testGetSetTotal(): void
    {
        $productRelation = new ProductRelation();
        $total = 100;
        
        $productRelation->setTotal($total);
        $this->assertEquals($total, $productRelation->getTotal());
    }

    public function testGetSetGiftQuantity(): void
    {
        $productRelation = new ProductRelation();
        $quantity = 2;
        
        $productRelation->setGiftQuantity($quantity);
        $this->assertEquals($quantity, $productRelation->getGiftQuantity());
    }

    public function testRetrieveAdminArray(): void
    {
        $productRelation = new ProductRelation();
        $discount = new Discount();
        
        $reflection = new \ReflectionClass($discount);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($discount, 123);
        
        $productRelation->setDiscount($discount);
        $productRelation->setSpuId('123456');
        $productRelation->setSkuId('789012');
        
        $adminArray = $productRelation->retrieveAdminArray();
        
        $this->assertArrayHasKey('spuId', $adminArray);
        $this->assertArrayHasKey('skuId', $adminArray);
        $this->assertArrayHasKey('discountId', $adminArray);
        $this->assertArrayHasKey('createTime', $adminArray);
        
        $this->assertEquals('123456', $adminArray['spuId']);
        $this->assertEquals('789012', $adminArray['skuId']);
        $this->assertEquals(123, $adminArray['discountId']);
    }

    public function testToString(): void
    {
        $productRelation = new ProductRelation();
        $this->assertEquals('0', (string)$productRelation);
        
        $reflection = new \ReflectionClass($productRelation);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($productRelation, 999);
        
        $this->assertEquals('999', (string)$productRelation);
    }

    public function testTimestampMethods(): void
    {
        $productRelation = new ProductRelation();
        $now = new \DateTimeImmutable();
        
        $productRelation->setCreateTime($now);
        $this->assertEquals($now, $productRelation->getCreateTime());
        
        $updateTime = new \DateTimeImmutable();
        $productRelation->setUpdateTime($updateTime);
        $this->assertEquals($updateTime, $productRelation->getUpdateTime());
    }
}