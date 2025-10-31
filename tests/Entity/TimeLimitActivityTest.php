<?php

namespace PromotionEngineBundle\Tests\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PromotionEngineBundle\Entity\Campaign;
use PromotionEngineBundle\Entity\TimeLimitActivity;
use PromotionEngineBundle\Enum\ActivityStatus;
use PromotionEngineBundle\Enum\ActivityType;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(TimeLimitActivity::class)]
final class TimeLimitActivityTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new TimeLimitActivity();
    }

    public function testConstructDefault(): void
    {
        $activity = new TimeLimitActivity();

        $this->assertInstanceOf(ArrayCollection::class, $activity->getCampaigns());
        $this->assertCount(0, $activity->getCampaigns());
        $this->assertEquals(ActivityStatus::PENDING, $activity->getStatus());
        $this->assertFalse($activity->isPreheatEnabled());
        $this->assertEquals(0, $activity->getPriority());
        $this->assertFalse($activity->isExclusive());
        $this->assertEquals(0, $activity->getSoldQuantity());
        $this->assertEquals([], $activity->getProductIds());
        $this->assertFalse($activity->isValid());
    }

    public function testGetSetName(): void
    {
        $activity = new TimeLimitActivity();
        $name = '双十一限时秒杀';

        $activity->setName($name);
        $this->assertEquals($name, $activity->getName());
    }

    public function testGetSetDescription(): void
    {
        $activity = new TimeLimitActivity();
        $description = '双十一限时秒杀活动，超值优惠';

        $activity->setDescription($description);
        $this->assertEquals($description, $activity->getDescription());

        $activity->setDescription(null);
        $this->assertNull($activity->getDescription());
    }

    public function testGetSetStartTime(): void
    {
        $activity = new TimeLimitActivity();
        $startTime = new \DateTimeImmutable('2023-11-01 00:00:00');

        $activity->setStartTime($startTime);
        $this->assertEquals($startTime, $activity->getStartTime());
    }

    public function testGetSetEndTime(): void
    {
        $activity = new TimeLimitActivity();
        $endTime = new \DateTimeImmutable('2023-11-11 23:59:59');

        $activity->setEndTime($endTime);
        $this->assertEquals($endTime, $activity->getEndTime());
    }

    public function testGetSetActivityType(): void
    {
        $activity = new TimeLimitActivity();
        $type = ActivityType::LIMITED_TIME_SECKILL;

        $activity->setActivityType($type);
        $this->assertEquals($type, $activity->getActivityType());
    }

    public function testGetSetStatus(): void
    {
        $activity = new TimeLimitActivity();
        $status = ActivityStatus::ACTIVE;

        $activity->setStatus($status);
        $this->assertEquals($status, $activity->getStatus());
    }

    public function testGetSetPreheatEnabled(): void
    {
        $activity = new TimeLimitActivity();

        $activity->setPreheatEnabled(true);
        $this->assertTrue($activity->isPreheatEnabled());

        $activity->setPreheatEnabled(false);
        $this->assertFalse($activity->isPreheatEnabled());
    }

    public function testGetSetPreheatStartTime(): void
    {
        $activity = new TimeLimitActivity();
        $preheatTime = new \DateTimeImmutable('2023-10-25 00:00:00');

        $activity->setPreheatStartTime($preheatTime);
        $this->assertEquals($preheatTime, $activity->getPreheatStartTime());

        $activity->setPreheatStartTime(null);
        $this->assertNull($activity->getPreheatStartTime());
    }

    public function testGetSetPriority(): void
    {
        $activity = new TimeLimitActivity();
        $priority = 100;

        $activity->setPriority($priority);
        $this->assertEquals($priority, $activity->getPriority());
    }

    public function testGetSetExclusive(): void
    {
        $activity = new TimeLimitActivity();

        $activity->setExclusive(true);
        $this->assertTrue($activity->isExclusive());

        $activity->setExclusive(false);
        $this->assertFalse($activity->isExclusive());
    }

    public function testGetSetTotalLimit(): void
    {
        $activity = new TimeLimitActivity();
        $limit = 1000;

        $activity->setTotalLimit($limit);
        $this->assertEquals($limit, $activity->getTotalLimit());

        $activity->setTotalLimit(null);
        $this->assertNull($activity->getTotalLimit());
    }

    public function testGetSetSoldQuantity(): void
    {
        $activity = new TimeLimitActivity();
        $quantity = 50;

        $activity->setSoldQuantity($quantity);
        $this->assertEquals($quantity, $activity->getSoldQuantity());
    }

    public function testGetSetProductIds(): void
    {
        $activity = new TimeLimitActivity();
        $productIds = ['product1', 'product2', 'product3'];

        $activity->setProductIds($productIds);
        $this->assertEquals($productIds, $activity->getProductIds());
    }

    public function testAddRemoveProductId(): void
    {
        $activity = new TimeLimitActivity();
        $productId = 'product123';

        $activity->addProductId($productId);
        $this->assertContains($productId, $activity->getProductIds());

        $activity->addProductId($productId);
        $this->assertCount(1, $activity->getProductIds());

        $activity->removeProductId($productId);
        $this->assertNotContains($productId, $activity->getProductIds());
    }

    public function testGetSetValid(): void
    {
        $activity = new TimeLimitActivity();

        $activity->setValid(true);
        $this->assertTrue($activity->isValid());

        $activity->setValid(false);
        $this->assertFalse($activity->isValid());

        $activity->setValid(null);
        $this->assertNull($activity->isValid());
    }

    public function testCampaignManagement(): void
    {
        $activity = new TimeLimitActivity();
        $campaign = new Campaign();

        $activity->addCampaign($campaign);
        $this->assertCount(1, $activity->getCampaigns());
        $this->assertTrue($activity->getCampaigns()->contains($campaign));

        $activity->addCampaign($campaign);
        $this->assertCount(1, $activity->getCampaigns());

        $activity->removeCampaign($campaign);
        $this->assertCount(0, $activity->getCampaigns());
        $this->assertFalse($activity->getCampaigns()->contains($campaign));
    }

    #[DataProvider('remainingQuantityProvider')]
    public function testGetRemainingQuantity(?int $totalLimit, int $soldQuantity, ?int $expected): void
    {
        $activity = new TimeLimitActivity();
        $activity->setTotalLimit($totalLimit);
        $activity->setSoldQuantity($soldQuantity);

        $this->assertEquals($expected, $activity->getRemainingQuantity());
    }

    /**
     * @return array<string, array{int|null, int, int|null}>
     */
    public static function remainingQuantityProvider(): array
    {
        return [
            'no_limit' => [null, 50, null],
            'has_remaining' => [1000, 300, 700],
            'sold_out' => [100, 100, 0],
            'over_sold' => [100, 150, 0],
        ];
    }

    #[DataProvider('soldOutProvider')]
    public function testIsSoldOut(?int $totalLimit, int $soldQuantity, bool $expected): void
    {
        $activity = new TimeLimitActivity();
        $activity->setTotalLimit($totalLimit);
        $activity->setSoldQuantity($soldQuantity);

        $this->assertEquals($expected, $activity->isSoldOut());
    }

    /**
     * @return array<string, array{int|null, int, bool}>
     */
    public static function soldOutProvider(): array
    {
        return [
            'no_limit' => [null, 50, false],
            'not_sold_out' => [1000, 300, false],
            'exactly_sold_out' => [100, 100, true],
            'over_sold' => [100, 150, true],
        ];
    }

    public function testIsInPreheatPeriod(): void
    {
        $activity = new TimeLimitActivity();
        $now = new \DateTimeImmutable('2023-10-30 12:00:00');
        $preheatStart = new \DateTimeImmutable('2023-10-25 00:00:00');
        $activityStart = new \DateTimeImmutable('2023-11-01 00:00:00');

        $activity->setStartTime($activityStart);

        $this->assertFalse($activity->isInPreheatPeriod($now));

        $activity->setPreheatEnabled(true);
        $this->assertFalse($activity->isInPreheatPeriod($now));

        $activity->setPreheatStartTime($preheatStart);
        $this->assertTrue($activity->isInPreheatPeriod($now));

        $laterTime = new \DateTimeImmutable('2023-11-02 00:00:00');
        $this->assertFalse($activity->isInPreheatPeriod($laterTime));
    }

    public function testIsActive(): void
    {
        $activity = new TimeLimitActivity();
        $start = new \DateTimeImmutable('2023-11-01 00:00:00');
        $end = new \DateTimeImmutable('2023-11-11 23:59:59');
        $activity->setStartTime($start);
        $activity->setEndTime($end);

        $beforeStart = new \DateTimeImmutable('2023-10-31 23:59:59');
        $duringActivity = new \DateTimeImmutable('2023-11-05 12:00:00');
        $afterEnd = new \DateTimeImmutable('2023-11-12 00:00:00');

        $this->assertFalse($activity->isActive($beforeStart));
        $this->assertTrue($activity->isActive($duringActivity));
        $this->assertFalse($activity->isActive($afterEnd));
    }

    public function testIsFinished(): void
    {
        $activity = new TimeLimitActivity();
        $end = new \DateTimeImmutable('2023-11-11 23:59:59');
        $activity->setEndTime($end);

        $beforeEnd = new \DateTimeImmutable('2023-11-11 12:00:00');
        $afterEnd = new \DateTimeImmutable('2023-11-12 00:00:00');

        $this->assertFalse($activity->isFinished($beforeEnd));
        $this->assertTrue($activity->isFinished($afterEnd));
    }

    #[DataProvider('statusCalculationProvider')]
    public function testCalculateCurrentStatus(\DateTimeImmutable $now, ActivityStatus $expected): void
    {
        $activity = new TimeLimitActivity();
        $start = new \DateTimeImmutable('2023-11-01 00:00:00');
        $end = new \DateTimeImmutable('2023-11-11 23:59:59');
        $activity->setStartTime($start);
        $activity->setEndTime($end);

        $this->assertEquals($expected, $activity->calculateCurrentStatus($now));
    }

    /**
     * @return array<string, array{\DateTimeImmutable, ActivityStatus}>
     */
    public static function statusCalculationProvider(): array
    {
        return [
            'before_start' => [new \DateTimeImmutable('2023-10-31 23:59:59'), ActivityStatus::PENDING],
            'during_activity' => [new \DateTimeImmutable('2023-11-05 12:00:00'), ActivityStatus::ACTIVE],
            'after_end' => [new \DateTimeImmutable('2023-11-12 00:00:00'), ActivityStatus::FINISHED],
        ];
    }

    public function testRetrieveAdminArray(): void
    {
        $activity = new TimeLimitActivity();
        $activity->setName('测试活动');
        $activity->setDescription('测试描述');
        $activity->setStartTime(new \DateTimeImmutable('2023-11-01 00:00:00'));
        $activity->setEndTime(new \DateTimeImmutable('2023-11-11 23:59:59'));
        $activity->setActivityType(ActivityType::LIMITED_TIME_SECKILL);
        $activity->setStatus(ActivityStatus::ACTIVE);
        $activity->setPriority(100);
        $activity->setExclusive(true);
        $activity->setTotalLimit(1000);
        $activity->setSoldQuantity(300);
        $activity->setProductIds(['product1', 'product2']);
        $activity->setValid(true);

        $array = $activity->retrieveAdminArray();

        $this->assertIsArray($array);
        $this->assertEquals('测试活动', $array['name']);
        $this->assertEquals('测试描述', $array['description']);
        $this->assertEquals('2023-11-01 00:00:00', $array['startTime']);
        $this->assertEquals('2023-11-11 23:59:59', $array['endTime']);
        $this->assertEquals('limited_time_seckill', $array['activityType']);
        $this->assertEquals('限时秒杀', $array['activityTypeLabel']);
        $this->assertEquals('active', $array['status']);
        $this->assertEquals('进行中', $array['statusLabel']);
        $this->assertEquals(100, $array['priority']);
        $this->assertTrue($array['exclusive']);
        $this->assertEquals(1000, $array['totalLimit']);
        $this->assertEquals(300, $array['soldQuantity']);
        $this->assertEquals(700, $array['remainingQuantity']);
        $this->assertEquals(['product1', 'product2'], $array['productIds']);
        $this->assertTrue($array['valid']);
    }

    public function testToString(): void
    {
        $activity = new TimeLimitActivity();
        $this->assertEquals('', (string) $activity);

        $activity->setName('测试活动');
        $this->assertEquals('测试活动', (string) $activity);
    }

    /**
     * 为AbstractEntityTestCase提供属性样本值
     *
     * @return array<string, array{string, mixed}>
     */
    public static function propertiesProvider(): array
    {
        return [
            'name' => ['name', '测试活动名称'],
            'description' => ['description', '测试活动描述'],
            'startTime' => ['startTime', new \DateTimeImmutable('2023-11-01 00:00:00')],
            'endTime' => ['endTime', new \DateTimeImmutable('2023-11-11 23:59:59')],
            'activityType' => ['activityType', ActivityType::LIMITED_TIME_SECKILL],
            'status' => ['status', ActivityStatus::ACTIVE],
            'priority' => ['priority', 100],
            'exclusive' => ['exclusive', true],
            'totalLimit' => ['totalLimit', 1000],
            'soldQuantity' => ['soldQuantity', 300],
            'valid' => ['valid', true],
            'preheatEnabled' => ['preheatEnabled', true],
            'preheatStartTime' => ['preheatStartTime', new \DateTimeImmutable('2023-10-25 00:00:00')],
        ];
    }
}
