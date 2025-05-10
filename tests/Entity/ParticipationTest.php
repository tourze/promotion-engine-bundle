<?php

namespace PromotionEngineBundle\Tests\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use PromotionEngineBundle\Entity\Campaign;
use PromotionEngineBundle\Entity\Participation;
use Symfony\Component\Security\Core\User\UserInterface;

class ParticipationTest extends TestCase
{
    /**
     * 测试默认构造函数
     */
    public function testConstruct_default(): void
    {
        $participation = new Participation();
        
        $this->assertInstanceOf(ArrayCollection::class, $participation->getCampaigns());
        $this->assertCount(0, $participation->getCampaigns());
    }

    /**
     * 测试用户关联的 getter 和 setter
     */
    public function testGetSetUser(): void
    {
        $participation = new Participation();
        $user = $this->createMock(UserInterface::class);
        
        $this->assertInstanceOf(Participation::class, $participation->setUser($user));
        $this->assertSame($user, $participation->getUser());
        
        // 测试空用户
        $participation->setUser(null);
        $this->assertNull($participation->getUser());
    }

    /**
     * 测试总价属性的 getter 和 setter
     */
    public function testGetSetTotalPrice(): void
    {
        $participation = new Participation();
        $totalPrice = '100.00';
        
        $participation->setTotalPrice($totalPrice);
        $this->assertEquals($totalPrice, $participation->getTotalPrice());
        
        // 测试空值
        $participation->setTotalPrice(null);
        $this->assertNull($participation->getTotalPrice());
    }

    /**
     * 测试优惠扣减属性的 getter 和 setter
     */
    public function testGetSetDiscountPrice(): void
    {
        $participation = new Participation();
        $discountPrice = '20.00';
        
        $participation->setDiscountPrice($discountPrice);
        $this->assertEquals($discountPrice, $participation->getDiscountPrice());
        
        // 测试空值
        $participation->setDiscountPrice(null);
        $this->assertNull($participation->getDiscountPrice());
    }

    /**
     * 测试活动集合的管理
     */
    public function testCampaignsCollection(): void
    {
        $participation = new Participation();
        $campaign = $this->createMock(Campaign::class);
        
        // 测试添加活动
        $this->assertInstanceOf(Participation::class, $participation->addCampaign($campaign));
        $this->assertTrue($participation->getCampaigns()->contains($campaign));
        $this->assertCount(1, $participation->getCampaigns());
        
        // 测试重复添加同一个活动
        $participation->addCampaign($campaign);
        $this->assertCount(1, $participation->getCampaigns());
        
        // 测试移除活动
        $this->assertInstanceOf(Participation::class, $participation->removeCampaign($campaign));
        $this->assertFalse($participation->getCampaigns()->contains($campaign));
        $this->assertCount(0, $participation->getCampaigns());
    }

    /**
     * 测试 ID 属性的 getter
     */
    public function testGetId(): void
    {
        $participation = new Participation();
        
        // 使用反射设置 ID，因为 ID 是生成的
        $reflection = new \ReflectionClass($participation);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($participation, '123456789');
        
        $this->assertEquals('123456789', $participation->getId());
    }

    /**
     * 测试时间戳相关方法
     */
    public function testTimestampMethods(): void
    {
        $participation = new Participation();
        $now = new DateTime();
        
        $participation->setCreateTime($now);
        $this->assertEquals($now, $participation->getCreateTime());
        
        $updateTime = new DateTime();
        $participation->setUpdateTime($updateTime);
        $this->assertEquals($updateTime, $participation->getUpdateTime());
    }

    /**
     * 测试跟踪人员相关方法
     */
    public function testTrackingMethods(): void
    {
        $participation = new Participation();
        $creator = 'admin';
        $updater = 'manager';
        
        $this->assertInstanceOf(Participation::class, $participation->setCreatedBy($creator));
        $this->assertEquals($creator, $participation->getCreatedBy());
        
        $this->assertInstanceOf(Participation::class, $participation->setUpdatedBy($updater));
        $this->assertEquals($updater, $participation->getUpdatedBy());
    }
} 