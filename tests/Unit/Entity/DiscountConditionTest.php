<?php

namespace PromotionEngineBundle\Tests\Unit\Entity;

use PHPUnit\Framework\TestCase;
use PromotionEngineBundle\Entity\Discount;
use PromotionEngineBundle\Entity\DiscountCondition;

class DiscountConditionTest extends TestCase
{
    public function testGetSetDiscount(): void
    {
        $discountCondition = new DiscountCondition();
        $discount = new Discount();
        
        $discountCondition->setDiscount($discount);
        $this->assertSame($discount, $discountCondition->getDiscount());
        
        $discountCondition->setDiscount(null);
        $this->assertNull($discountCondition->getDiscount());
    }

    public function testGetSetCondition1(): void
    {
        $discountCondition = new DiscountCondition();
        $condition1 = 'test condition 1';
        
        $discountCondition->setCondition1($condition1);
        $this->assertEquals($condition1, $discountCondition->getCondition1());
    }

    public function testGetSetCondition2(): void
    {
        $discountCondition = new DiscountCondition();
        $condition2 = 'test condition 2';
        
        $discountCondition->setCondition2($condition2);
        $this->assertEquals($condition2, $discountCondition->getCondition2());
    }

    public function testGetSetCondition3(): void
    {
        $discountCondition = new DiscountCondition();
        $condition3 = 'test condition 3';
        
        $discountCondition->setCondition3($condition3);
        $this->assertEquals($condition3, $discountCondition->getCondition3());
        
        $discountCondition->setCondition3(null);
        $this->assertNull($discountCondition->getCondition3());
    }

    public function testRetrieveAdminArray(): void
    {
        $discountCondition = new DiscountCondition();
        $discount = new Discount();
        
        $reflection = new \ReflectionClass($discount);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($discount, 123);
        
        $discountCondition->setDiscount($discount);
        $discountCondition->setCondition1('condition1');
        $discountCondition->setCondition2('condition2');
        $discountCondition->setCondition3('condition3');
        
        $adminArray = $discountCondition->retrieveAdminArray();
        
        $this->assertArrayHasKey('condition1', $adminArray);
        $this->assertArrayHasKey('condition2', $adminArray);
        $this->assertArrayHasKey('condition3', $adminArray);
        $this->assertArrayHasKey('discountId', $adminArray);
        $this->assertArrayHasKey('createTime', $adminArray);
        
        $this->assertEquals('condition1', $adminArray['condition1']);
        $this->assertEquals('condition2', $adminArray['condition2']);
        $this->assertEquals('condition3', $adminArray['condition3']);
        $this->assertEquals(123, $adminArray['discountId']);
    }

    public function testToString(): void
    {
        $discountCondition = new DiscountCondition();
        $this->assertEquals('0', (string)$discountCondition);
        
        $reflection = new \ReflectionClass($discountCondition);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($discountCondition, 456);
        
        $this->assertEquals('456', (string)$discountCondition);
    }

    public function testTimestampMethods(): void
    {
        $discountCondition = new DiscountCondition();
        $now = new \DateTimeImmutable();
        
        $discountCondition->setCreateTime($now);
        $this->assertEquals($now, $discountCondition->getCreateTime());
        
        $updateTime = new \DateTimeImmutable();
        $discountCondition->setUpdateTime($updateTime);
        $this->assertEquals($updateTime, $discountCondition->getUpdateTime());
    }
}