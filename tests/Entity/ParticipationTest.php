<?php

namespace PromotionEngineBundle\Tests\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\Attributes\CoversClass;
use PromotionEngineBundle\Entity\Campaign;
use PromotionEngineBundle\Entity\Participation;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(Participation::class)]
final class ParticipationTest extends AbstractEntityTestCase
{
    /**
     * 创建被测实体的实例
     */
    protected function createEntity(): object
    {
        return new Participation();
    }

    /**
     * 测试默认构造函数
     */
    public function testConstructDefault(): void
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

        $participation->setUser($user);
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
        $campaign = new Campaign();

        // 测试添加活动
        $participation->addCampaign($campaign);
        $this->assertTrue($participation->getCampaigns()->contains($campaign));
        $this->assertCount(1, $participation->getCampaigns());

        // 测试重复添加同一个活动
        $participation->addCampaign($campaign);
        $this->assertCount(1, $participation->getCampaigns());

        // 测试移除活动
        $participation->removeCampaign($campaign);
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
        $now = new \DateTimeImmutable();

        $participation->setCreateTime($now);
        $this->assertEquals($now, $participation->getCreateTime());

        $updateTime = new \DateTimeImmutable();
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

        $participation->setCreatedBy($creator);
        $this->assertEquals($creator, $participation->getCreatedBy());

        $participation->setUpdatedBy($updater);
        $this->assertEquals($updater, $participation->getUpdatedBy());
    }

    /**
     * 提供属性及其样本值的 Data Provider
     *
     * @return \Generator<string, array{string, mixed}>
     */
    public static function propertiesProvider(): \Generator
    {
        yield 'totalPrice' => ['totalPrice', '100.00'];
        yield 'discountPrice' => ['discountPrice', '20.00'];
    }
}
