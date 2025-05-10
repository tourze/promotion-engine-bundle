<?php

namespace PromotionEngineBundle\Tests\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use PromotionEngineBundle\Entity\Campaign;
use PromotionEngineBundle\Entity\Constraint;
use PromotionEngineBundle\Entity\Discount;
use PromotionEngineBundle\Entity\Participation;

class CampaignTest extends TestCase
{
    /**
     * 测试默认构造函数
     */
    public function testConstruct_default(): void
    {
        $campaign = new Campaign();
        
        $this->assertInstanceOf(ArrayCollection::class, $campaign->getConstraints());
        $this->assertCount(0, $campaign->getConstraints());
        
        $this->assertInstanceOf(ArrayCollection::class, $campaign->getDiscounts());
        $this->assertCount(0, $campaign->getDiscounts());
        
        $this->assertInstanceOf(ArrayCollection::class, $campaign->getParticipations());
        $this->assertCount(0, $campaign->getParticipations());
    }

    /**
     * 测试标题属性的 getter 和 setter
     */
    public function testGetSetTitle(): void
    {
        $campaign = new Campaign();
        $title = '双十一促销活动';
        
        $this->assertInstanceOf(Campaign::class, $campaign->setTitle($title));
        $this->assertEquals($title, $campaign->getTitle());
    }

    /**
     * 测试描述属性的 getter 和 setter
     */
    public function testGetSetDescription(): void
    {
        $campaign = new Campaign();
        $description = '双十一全场促销活动，满减优惠';
        
        $this->assertInstanceOf(Campaign::class, $campaign->setDescription($description));
        $this->assertEquals($description, $campaign->getDescription());
        
        // 测试空描述
        $campaign->setDescription(null);
        $this->assertNull($campaign->getDescription());
    }

    /**
     * 测试开始时间属性的 getter 和 setter
     */
    public function testGetSetStartTime(): void
    {
        $campaign = new Campaign();
        $startTime = new DateTime('2023-11-01 00:00:00');
        
        $this->assertInstanceOf(Campaign::class, $campaign->setStartTime($startTime));
        $this->assertEquals($startTime, $campaign->getStartTime());
    }

    /**
     * 测试结束时间属性的 getter 和 setter
     */
    public function testGetSetEndTime(): void
    {
        $campaign = new Campaign();
        $endTime = new DateTime('2023-11-12 23:59:59');
        
        $this->assertInstanceOf(Campaign::class, $campaign->setEndTime($endTime));
        $this->assertEquals($endTime, $campaign->getEndTime());
    }

    /**
     * 测试排他属性的 getter 和 setter
     */
    public function testGetSetExclusive(): void
    {
        $campaign = new Campaign();
        
        $this->assertInstanceOf(Campaign::class, $campaign->setExclusive(true));
        $this->assertTrue($campaign->isExclusive());
        
        $campaign->setExclusive(false);
        $this->assertFalse($campaign->isExclusive());
        
        $campaign->setExclusive(null);
        $this->assertNull($campaign->isExclusive());
    }

    /**
     * 测试权重属性的 getter 和 setter
     */
    public function testGetSetWeight(): void
    {
        $campaign = new Campaign();
        $weight = 100;
        
        $this->assertInstanceOf(Campaign::class, $campaign->setWeight($weight));
        $this->assertEquals($weight, $campaign->getWeight());
    }

    /**
     * 测试有效属性的 getter 和 setter
     */
    public function testGetSetValid(): void
    {
        $campaign = new Campaign();
        
        $this->assertInstanceOf(Campaign::class, $campaign->setValid(true));
        $this->assertTrue($campaign->isValid());
        
        $campaign->setValid(false);
        $this->assertFalse($campaign->isValid());
        
        $campaign->setValid(null);
        $this->assertNull($campaign->isValid());
    }

    /**
     * 测试约束集合的管理
     */
    public function testConstraintsCollection(): void
    {
        $campaign = new Campaign();
        
        // 创建一个具体的 Constraint 对象，而不是 mock
        $constraint = new Constraint();
        
        // 添加约束并测试
        $result = $campaign->addConstraint($constraint);
        $this->assertInstanceOf(Campaign::class, $result);
        $this->assertTrue($campaign->getConstraints()->contains($constraint));
        $this->assertCount(1, $campaign->getConstraints());
        $this->assertSame($campaign, $constraint->getCampaign());
        
        // 移除约束并测试
        $result = $campaign->removeConstraint($constraint);
        $this->assertInstanceOf(Campaign::class, $result);
        $this->assertFalse($campaign->getConstraints()->contains($constraint));
        $this->assertCount(0, $campaign->getConstraints());
        $this->assertNull($constraint->getCampaign());
    }

    /**
     * 测试折扣集合的管理
     */
    public function testDiscountsCollection(): void
    {
        $campaign = new Campaign();
        
        // 创建一个具体的 Discount 对象，而不是 mock
        $discount = new Discount();
        
        // 添加折扣并测试
        $result = $campaign->addDiscount($discount);
        $this->assertInstanceOf(Campaign::class, $result);
        $this->assertTrue($campaign->getDiscounts()->contains($discount));
        $this->assertCount(1, $campaign->getDiscounts());
        $this->assertSame($campaign, $discount->getCampaign());
        
        // 移除折扣并测试
        $result = $campaign->removeDiscount($discount);
        $this->assertInstanceOf(Campaign::class, $result);
        $this->assertFalse($campaign->getDiscounts()->contains($discount));
        $this->assertCount(0, $campaign->getDiscounts());
        $this->assertNull($discount->getCampaign());
    }

    /**
     * 测试参与记录集合的管理
     */
    public function testParticipationsCollection(): void
    {
        $campaign = new Campaign();
        $participation = $this->createMock(Participation::class);
        
        // 测试添加参与记录
        $participation->expects($this->once())
            ->method('addCampaign')
            ->with($this->identicalTo($campaign));
            
        $this->assertInstanceOf(Campaign::class, $campaign->addParticipation($participation));
        $this->assertTrue($campaign->getParticipations()->contains($participation));
        $this->assertCount(1, $campaign->getParticipations());
        
        // 测试移除参与记录
        $participation->expects($this->once())
            ->method('removeCampaign')
            ->with($this->identicalTo($campaign));
            
        $this->assertInstanceOf(Campaign::class, $campaign->removeParticipation($participation));
        $this->assertFalse($campaign->getParticipations()->contains($participation));
        $this->assertCount(0, $campaign->getParticipations());
    }

    /**
     * 测试 __toString 方法
     */
    public function testToString(): void
    {
        $campaign = new Campaign();
        
        // ID 为空时应返回空字符串
        $this->assertEquals('', (string)$campaign);
        
        // 设置标题后应返回标题
        $title = '双十一促销活动';
        $campaign->setTitle($title);
        
        // 使用反射设置 ID，因为 ID 是生成的
        $reflection = new \ReflectionClass($campaign);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($campaign, '123456789');
        
        $this->assertEquals($title, (string)$campaign);
    }

    /**
     * 测试时间戳相关方法
     */
    public function testTimestampMethods(): void
    {
        $campaign = new Campaign();
        $now = new DateTime();
        
        $campaign->setCreateTime($now);
        $this->assertEquals($now, $campaign->getCreateTime());
        
        $updateTime = new DateTime();
        $campaign->setUpdateTime($updateTime);
        $this->assertEquals($updateTime, $campaign->getUpdateTime());
    }

    /**
     * 测试跟踪人员相关方法
     */
    public function testTrackingMethods(): void
    {
        $campaign = new Campaign();
        $creator = 'admin';
        $updater = 'manager';
        
        $this->assertInstanceOf(Campaign::class, $campaign->setCreatedBy($creator));
        $this->assertEquals($creator, $campaign->getCreatedBy());
        
        $this->assertInstanceOf(Campaign::class, $campaign->setUpdatedBy($updater));
        $this->assertEquals($updater, $campaign->getUpdatedBy());
    }
    
    /**
     * 测试 retrieveAdminArray 方法
     */
    public function testRetrieveAdminArray(): void
    {
        $campaign = new Campaign();
        $title = '双十一促销活动';
        $campaign->setTitle($title);
        
        // 设置必需的开始时间和结束时间
        $campaign->setStartTime(new DateTime('2023-11-01'));
        $campaign->setEndTime(new DateTime('2023-11-12'));
        
        $adminArray = $campaign->retrieveAdminArray();
        $this->assertIsArray($adminArray);
        $this->assertArrayHasKey('title', $adminArray);
        $this->assertEquals($title, $adminArray['title']);
    }
} 