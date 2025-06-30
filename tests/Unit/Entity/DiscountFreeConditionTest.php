<?php

namespace PromotionEngineBundle\Tests\Unit\Entity;

use PHPUnit\Framework\TestCase;
use PromotionEngineBundle\Entity\Discount;
use PromotionEngineBundle\Entity\DiscountFreeCondition;

class DiscountFreeConditionTest extends TestCase
{
    public function testGetSetDiscount(): void
    {
        $discountFreeCondition = new DiscountFreeCondition();
        $discount = new Discount();
        
        $discountFreeCondition->setDiscount($discount);
        $this->assertSame($discount, $discountFreeCondition->getDiscount());
        
        $discountFreeCondition->setDiscount(null);
        $this->assertNull($discountFreeCondition->getDiscount());
    }

    public function testGetSetPurchaseQuantity(): void
    {
        $discountFreeCondition = new DiscountFreeCondition();
        $quantity = '5';
        
        $discountFreeCondition->setPurchaseQuantity($quantity);
        $this->assertEquals($quantity, $discountFreeCondition->getPurchaseQuantity());
    }

    public function testGetSetFreeQuantity(): void
    {
        $discountFreeCondition = new DiscountFreeCondition();
        $quantity = '1';
        
        $discountFreeCondition->setFreeQuantity($quantity);
        $this->assertEquals($quantity, $discountFreeCondition->getFreeQuantity());
    }

    public function testRetrieveAdminArray(): void
    {
        $discountFreeCondition = new DiscountFreeCondition();
        $discount = new Discount();
        
        $reflection = new \ReflectionClass($discount);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($discount, 123);
        
        $discountFreeCondition->setDiscount($discount);
        $discountFreeCondition->setPurchaseQuantity('5');
        $discountFreeCondition->setFreeQuantity('1');
        
        $adminArray = $discountFreeCondition->retrieveAdminArray();
        
        $this->assertArrayHasKey('purchaseQuantity', $adminArray);
        $this->assertArrayHasKey('freeQuantity', $adminArray);
        $this->assertArrayHasKey('discountId', $adminArray);
        $this->assertArrayHasKey('createTime', $adminArray);
        
        $this->assertEquals('5', $adminArray['purchaseQuantity']);
        $this->assertEquals('1', $adminArray['freeQuantity']);
        $this->assertEquals(123, $adminArray['discountId']);
    }

    public function testToString(): void
    {
        $discountFreeCondition = new DiscountFreeCondition();
        $this->assertEquals('0', (string)$discountFreeCondition);
        
        $reflection = new \ReflectionClass($discountFreeCondition);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($discountFreeCondition, 789);
        
        $this->assertEquals('789', (string)$discountFreeCondition);
    }

    public function testTimestampMethods(): void
    {
        $discountFreeCondition = new DiscountFreeCondition();
        $now = new \DateTimeImmutable();
        
        $discountFreeCondition->setCreateTime($now);
        $this->assertEquals($now, $discountFreeCondition->getCreateTime());
        
        $updateTime = new \DateTimeImmutable();
        $discountFreeCondition->setUpdateTime($updateTime);
        $this->assertEquals($updateTime, $discountFreeCondition->getUpdateTime());
    }
}