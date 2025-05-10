<?php

namespace PromotionEngineBundle\Tests\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use PromotionEngineBundle\Entity\Campaign;
use PromotionEngineBundle\Entity\Discount;
use PromotionEngineBundle\Entity\ProductRelation;
use PromotionEngineBundle\Enum\DiscountType;

class DiscountTest extends TestCase
{
    /**
     * 测试默认构造函数
     */
    public function testConstruct_default(): void
    {
        $discount = new Discount();
        
        $this->assertInstanceOf(ArrayCollection::class, $discount->getProductRelations());
        $this->assertCount(0, $discount->getProductRelations());
    }

    /**
     * 测试类型属性的 getter 和 setter
     */
    public function testGetSetType(): void
    {
        $discount = new Discount();
        $type = DiscountType::REDUCTION;
        
        $this->assertInstanceOf(Discount::class, $discount->setType($type));
        $this->assertEquals($type, $discount->getType());
    }

    /**
     * 测试值属性的 getter 和 setter
     */
    public function testGetSetValue(): void
    {
        $discount = new Discount();
        $value = '100.00';
        
        $this->assertInstanceOf(Discount::class, $discount->setValue($value));
        $this->assertEquals($value, $discount->getValue());
        
        // 测试空值
        $discount->setValue(null);
        $this->assertNull($discount->getValue());
    }

    /**
     * 测试备注属性的 getter 和 setter
     */
    public function testGetSetRemark(): void
    {
        $discount = new Discount();
        $remark = '满100减20促销';
        
        $this->assertInstanceOf(Discount::class, $discount->setRemark($remark));
        $this->assertEquals($remark, $discount->getRemark());
        
        // 测试空值
        $discount->setRemark(null);
        $this->assertNull($discount->getRemark());
    }

    /**
     * 测试与活动的关联关系
     */
    public function testGetSetCampaign(): void
    {
        $discount = new Discount();
        $campaign = $this->createMock(Campaign::class);
        
        $this->assertInstanceOf(Discount::class, $discount->setCampaign($campaign));
        $this->assertSame($campaign, $discount->getCampaign());
        
        // 测试解除关联
        $discount->setCampaign(null);
        $this->assertNull($discount->getCampaign());
    }

    /**
     * 测试产品关系集合的管理
     */
    public function testProductRelationsCollection(): void
    {
        $discount = new Discount();
        
        // 创建一个具体的 ProductRelation 对象，而不是 mock
        $productRelation = new ProductRelation();
        
        // 添加产品关系并测试
        $result = $discount->addProductRelation($productRelation);
        $this->assertInstanceOf(Discount::class, $result);
        $this->assertTrue($discount->getProductRelations()->contains($productRelation));
        $this->assertCount(1, $discount->getProductRelations());
        $this->assertSame($discount, $productRelation->getDiscount());
        
        // 移除产品关系并测试
        $result = $discount->removeProductRelation($productRelation);
        $this->assertInstanceOf(Discount::class, $result);
        $this->assertFalse($discount->getProductRelations()->contains($productRelation));
        $this->assertCount(0, $discount->getProductRelations());
        $this->assertNull($productRelation->getDiscount());
    }

    /**
     * 测试 __toString 方法
     */
    public function testToString(): void
    {
        $discount = new Discount();
        
        // ID 为空时应返回空字符串
        $this->assertEquals('', (string)$discount);
        
        // 设置类型和值后应返回格式化字符串
        $type = DiscountType::REDUCTION;
        $value = '100.00';
        $discount->setType($type);
        $discount->setValue($value);
        
        // 使用反射设置 ID，因为 ID 是生成的
        $reflection = new \ReflectionClass($discount);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($discount, '123456789');
        
        $this->assertEquals("{$type->getLabel()} {$value}", (string)$discount);
    }

    /**
     * 测试时间戳相关方法
     */
    public function testTimestampMethods(): void
    {
        $discount = new Discount();
        $now = new DateTime();
        
        $discount->setCreateTime($now);
        $this->assertEquals($now, $discount->getCreateTime());
        
        $updateTime = new DateTime();
        $discount->setUpdateTime($updateTime);
        $this->assertEquals($updateTime, $discount->getUpdateTime());
    }

    /**
     * 测试跟踪人员相关方法
     */
    public function testTrackingMethods(): void
    {
        $discount = new Discount();
        $creator = 'admin';
        $updater = 'manager';
        
        $this->assertInstanceOf(Discount::class, $discount->setCreatedBy($creator));
        $this->assertEquals($creator, $discount->getCreatedBy());
        
        $this->assertInstanceOf(Discount::class, $discount->setUpdatedBy($updater));
        $this->assertEquals($updater, $discount->getUpdatedBy());
    }
    
    /**
     * 测试有效标志的 getter 和 setter
     */
    public function testGetSetValid(): void
    {
        $discount = new Discount();
        
        $this->assertInstanceOf(Discount::class, $discount->setValid(true));
        $this->assertTrue($discount->isValid());
        
        $discount->setValid(false);
        $this->assertFalse($discount->isValid());
        
        $discount->setValid(null);
        $this->assertNull($discount->isValid());
    }
    
    /**
     * 测试是否限量的 getter 和 setter
     */
    public function testGetSetIsLimited(): void
    {
        $discount = new Discount();
        
        $discount->setIsLimited(true);
        $this->assertTrue($discount->isLimited());
        
        $discount->setIsLimited(false);
        $this->assertFalse($discount->isLimited());
    }
    
    /**
     * 测试配额数的 getter 和 setter
     */
    public function testGetSetQuota(): void
    {
        $discount = new Discount();
        $quota = 100;
        
        $discount->setQuota($quota);
        $this->assertEquals($quota, $discount->getQuota());
    }
    
    /**
     * 测试参与数量的 getter 和 setter
     */
    public function testGetSetNumber(): void
    {
        $discount = new Discount();
        $number = 50;
        
        $discount->setNumber($number);
        $this->assertEquals($number, $discount->getNumber());
        
        $discount->setNumber(null);
        $this->assertNull($discount->getNumber());
    }
    
    /**
     * 测试 retrieveAdminArray 方法
     */
    public function testRetrieveAdminArray(): void
    {
        $discount = new Discount();
        $type = DiscountType::REDUCTION;
        $value = '100.00';
        
        $discount->setType($type);
        $discount->setValue($value);
        
        $adminArray = $discount->retrieveAdminArray();
        $this->assertIsArray($adminArray);
        $this->assertArrayHasKey('type', $adminArray);
        $this->assertEquals($type->value, $adminArray['type']);
        $this->assertArrayHasKey('value', $adminArray);
        $this->assertEquals($value, $adminArray['value']);
    }
} 