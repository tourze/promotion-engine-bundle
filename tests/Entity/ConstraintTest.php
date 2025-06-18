<?php

namespace PromotionEngineBundle\Tests\Entity;

use DateTime;
use PHPUnit\Framework\TestCase;
use PromotionEngineBundle\Entity\Campaign;
use PromotionEngineBundle\Entity\Constraint;
use PromotionEngineBundle\Enum\CompareType;
use PromotionEngineBundle\Enum\LimitType;

class ConstraintTest extends TestCase
{
    /**
     * 测试有效标志的 getter 和 setter
     */
    public function testGetSetValid(): void
    {
        $constraint = new Constraint();
        
        $this->assertInstanceOf(Constraint::class, $constraint->setValid(true));
        $this->assertTrue($constraint->isValid());
        
        $constraint->setValid(false);
        $this->assertFalse($constraint->isValid());
        
        $constraint->setValid(null);
        $this->assertNull($constraint->isValid());
    }
    
    /**
     * 测试活动关联的 getter 和 setter
     */
    public function testGetSetCampaign(): void
    {
        $constraint = new Constraint();
        $campaign = $this->createMock(Campaign::class);
        
        $this->assertInstanceOf(Constraint::class, $constraint->setCampaign($campaign));
        $this->assertSame($campaign, $constraint->getCampaign());
        
        // 测试解除关联
        $constraint->setCampaign(null);
        $this->assertNull($constraint->getCampaign());
    }
    
    /**
     * 测试对比类型的 getter 和 setter
     */
    public function testGetSetCompareType(): void
    {
        $constraint = new Constraint();
        $compareType = CompareType::EQUAL;
        
        $this->assertInstanceOf(Constraint::class, $constraint->setCompareType($compareType));
        $this->assertSame($compareType, $constraint->getCompareType());
    }
    
    /**
     * 测试限制类型的 getter 和 setter
     */
    public function testGetSetLimitType(): void
    {
        $constraint = new Constraint();
        $limitType = LimitType::ORDER_PRICE;
        
        $this->assertInstanceOf(Constraint::class, $constraint->setLimitType($limitType));
        $this->assertSame($limitType, $constraint->getLimitType());
    }
    
    /**
     * 测试范围值的 getter 和 setter
     */
    public function testGetSetRangeValue(): void
    {
        $constraint = new Constraint();
        $rangeValue = '100-200';
        
        $this->assertInstanceOf(Constraint::class, $constraint->setRangeValue($rangeValue));
        $this->assertEquals($rangeValue, $constraint->getRangeValue());
        
        // 测试空值
        $constraint->setRangeValue(null);
        $this->assertNull($constraint->getRangeValue());
    }
    
    /**
     * 测试 __toString 方法
     */
    public function testToString(): void
    {
        $constraint = new Constraint();
        
        // ID 为空时应返回空字符串
        $this->assertEquals('', (string)$constraint);
        
        // 设置必要的属性
        $compareType = CompareType::GTE;
        $limitType = LimitType::ORDER_PRICE;
        $rangeValue = '100.00';
        
        $constraint->setCompareType($compareType);
        $constraint->setLimitType($limitType);
        $constraint->setRangeValue($rangeValue);
        
        // 使用反射设置 ID，因为 ID 是生成的
        $reflection = new \ReflectionClass($constraint);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($constraint, '123456789');
        
        $expected = "{$limitType->getLabel()} {$compareType->getLabel()} {$rangeValue}";
        $this->assertEquals($expected, (string)$constraint);
    }
    
    /**
     * 测试时间戳相关方法
     */
    public function testTimestampMethods(): void
    {
        $constraint = new Constraint();
        $now = new DateTime();
        
        $constraint->setCreateTime($now);
        $this->assertEquals($now, $constraint->getCreateTime());
        
        $updateTime = new DateTime();
        $constraint->setUpdateTime($updateTime);
        $this->assertEquals($updateTime, $constraint->getUpdateTime());
    }
    
    /**
     * 测试跟踪人员相关方法
     */
    public function testTrackingMethods(): void
    {
        $constraint = new Constraint();
        $creator = 'admin';
        $updater = 'manager';
        
        $this->assertInstanceOf(Constraint::class, $constraint->setCreatedBy($creator));
        $this->assertEquals($creator, $constraint->getCreatedBy());
        
        $this->assertInstanceOf(Constraint::class, $constraint->setUpdatedBy($updater));
        $this->assertEquals($updater, $constraint->getUpdatedBy());
    }
    
    /**
     * 测试 retrieveAdminArray 方法
     */
    public function testRetrieveAdminArray(): void
    {
        $constraint = new Constraint();
        $compareType = CompareType::EQUAL;
        $limitType = LimitType::ORDER_PRICE;
        
        $constraint->setCompareType($compareType);
        $constraint->setLimitType($limitType);
        
        $adminArray = $constraint->retrieveAdminArray();
        $this->assertArrayHasKey('compareType', $adminArray);
        $this->assertEquals($compareType->value, $adminArray['compareType']);
        $this->assertArrayHasKey('limitType', $adminArray);
        $this->assertEquals($limitType->value, $adminArray['limitType']);
    }
} 