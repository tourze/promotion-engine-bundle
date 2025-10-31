<?php

declare(strict_types=1);

namespace PromotionEngineBundle\Tests\DTO;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PromotionEngineBundle\DTO\CreateTimeLimitActivityInput;
use PromotionEngineBundle\Enum\ActivityType;

/**
 * @internal
 */
#[CoversClass(CreateTimeLimitActivityInput::class)]
class CreateTimeLimitActivityInputTest extends TestCase
{
    public function testConstructor(): void
    {
        $productIds = ['prod-1', 'prod-2', 'prod-3'];
        $input = new CreateTimeLimitActivityInput(
            name: '限时秒杀活动',
            description: '双11限时秒杀',
            startTime: '2024-11-11 00:00:00',
            endTime: '2024-11-11 23:59:59',
            activityType: ActivityType::LIMITED_TIME_SECKILL,
            productIds: $productIds,
            priority: 10,
            exclusive: true,
            totalLimit: 1000,
            preheatEnabled: true,
            preheatStartTime: '2024-11-10 18:00:00'
        );

        $this->assertEquals('限时秒杀活动', $input->name);
        $this->assertEquals('双11限时秒杀', $input->description);
        $this->assertEquals('2024-11-11 00:00:00', $input->startTime);
        $this->assertEquals('2024-11-11 23:59:59', $input->endTime);
        $this->assertEquals(ActivityType::LIMITED_TIME_SECKILL, $input->activityType);
        $this->assertEquals($productIds, $input->productIds);
        $this->assertEquals(10, $input->priority);
        $this->assertTrue($input->exclusive);
        $this->assertEquals(1000, $input->totalLimit);
        $this->assertTrue($input->preheatEnabled);
        $this->assertEquals('2024-11-10 18:00:00', $input->preheatStartTime);
    }

    public function testConstructorWithDefaults(): void
    {
        $input = new CreateTimeLimitActivityInput(
            name: '折扣活动',
            description: '周年庆折扣',
            startTime: '2024-12-01 00:00:00',
            endTime: '2024-12-31 23:59:59',
            activityType: ActivityType::LIMITED_TIME_DISCOUNT,
            productIds: []
        );

        $this->assertEquals('折扣活动', $input->name);
        $this->assertEquals('周年庆折扣', $input->description);
        $this->assertEquals('2024-12-01 00:00:00', $input->startTime);
        $this->assertEquals('2024-12-31 23:59:59', $input->endTime);
        $this->assertEquals(ActivityType::LIMITED_TIME_DISCOUNT, $input->activityType);
        $this->assertEquals([], $input->productIds);
        $this->assertEquals(0, $input->priority);
        $this->assertFalse($input->exclusive);
        $this->assertNull($input->totalLimit);
        $this->assertFalse($input->preheatEnabled);
        $this->assertNull($input->preheatStartTime);
    }

    public function testHasProductIdsWithProducts(): void
    {
        $input = new CreateTimeLimitActivityInput(
            name: '活动',
            description: '描述',
            startTime: '2024-01-01 00:00:00',
            endTime: '2024-01-01 23:59:59',
            activityType: ActivityType::LIMITED_TIME_DISCOUNT,
            productIds: ['prod-1', 'prod-2']
        );

        $this->assertTrue($input->hasProductIds());
    }

    public function testHasProductIdsWithoutProducts(): void
    {
        $input = new CreateTimeLimitActivityInput(
            name: '活动',
            description: '描述',
            startTime: '2024-01-01 00:00:00',
            endTime: '2024-01-01 23:59:59',
            activityType: ActivityType::LIMITED_TIME_DISCOUNT,
            productIds: []
        );

        $this->assertFalse($input->hasProductIds());
    }

    public function testIsValidTimeRangeWithValidRange(): void
    {
        $input = new CreateTimeLimitActivityInput(
            name: '活动',
            description: '描述',
            startTime: '2024-01-01 00:00:00',
            endTime: '2024-01-01 23:59:59',
            activityType: ActivityType::LIMITED_TIME_DISCOUNT,
            productIds: []
        );

        $this->assertTrue($input->isValidTimeRange());
    }

    public function testIsValidTimeRangeWithInvalidRange(): void
    {
        $input = new CreateTimeLimitActivityInput(
            name: '活动',
            description: '描述',
            startTime: '2024-01-02 00:00:00',
            endTime: '2024-01-01 23:59:59',
            activityType: ActivityType::LIMITED_TIME_DISCOUNT,
            productIds: []
        );

        $this->assertFalse($input->isValidTimeRange());
    }

    public function testIsValidTimeRangeWithSameTime(): void
    {
        $input = new CreateTimeLimitActivityInput(
            name: '活动',
            description: '描述',
            startTime: '2024-01-01 12:00:00',
            endTime: '2024-01-01 12:00:00',
            activityType: ActivityType::LIMITED_TIME_DISCOUNT,
            productIds: []
        );

        $this->assertFalse($input->isValidTimeRange());
    }

    public function testIsValidPreheatTimeWithoutPreheat(): void
    {
        $input = new CreateTimeLimitActivityInput(
            name: '活动',
            description: '描述',
            startTime: '2024-01-01 00:00:00',
            endTime: '2024-01-01 23:59:59',
            activityType: ActivityType::LIMITED_TIME_DISCOUNT,
            productIds: [],
            preheatEnabled: false
        );

        $this->assertTrue($input->isValidPreheatTime());
    }

    public function testIsValidPreheatTimeWithPreheatButNoStartTime(): void
    {
        $input = new CreateTimeLimitActivityInput(
            name: '活动',
            description: '描述',
            startTime: '2024-01-01 00:00:00',
            endTime: '2024-01-01 23:59:59',
            activityType: ActivityType::LIMITED_TIME_DISCOUNT,
            productIds: [],
            preheatEnabled: true,
            preheatStartTime: null
        );

        $this->assertTrue($input->isValidPreheatTime());
    }

    public function testIsValidPreheatTimeWithValidPreheatTime(): void
    {
        $input = new CreateTimeLimitActivityInput(
            name: '活动',
            description: '描述',
            startTime: '2024-01-01 10:00:00',
            endTime: '2024-01-01 23:59:59',
            activityType: ActivityType::LIMITED_TIME_DISCOUNT,
            productIds: [],
            preheatEnabled: true,
            preheatStartTime: '2024-01-01 08:00:00'
        );

        $this->assertTrue($input->isValidPreheatTime());
    }

    public function testIsValidPreheatTimeWithInvalidPreheatTime(): void
    {
        $input = new CreateTimeLimitActivityInput(
            name: '活动',
            description: '描述',
            startTime: '2024-01-01 10:00:00',
            endTime: '2024-01-01 23:59:59',
            activityType: ActivityType::LIMITED_TIME_DISCOUNT,
            productIds: [],
            preheatEnabled: true,
            preheatStartTime: '2024-01-01 12:00:00'
        );

        $this->assertFalse($input->isValidPreheatTime());
    }

    public function testIsValidPreheatTimeWithSamePreheatAndStartTime(): void
    {
        $input = new CreateTimeLimitActivityInput(
            name: '活动',
            description: '描述',
            startTime: '2024-01-01 10:00:00',
            endTime: '2024-01-01 23:59:59',
            activityType: ActivityType::LIMITED_TIME_DISCOUNT,
            productIds: [],
            preheatEnabled: true,
            preheatStartTime: '2024-01-01 10:00:00'
        );

        $this->assertFalse($input->isValidPreheatTime());
    }

    public function testIsLimitedQuantityWithLimitedQuantityType(): void
    {
        $input = new CreateTimeLimitActivityInput(
            name: '抢购活动',
            description: '限量抢购',
            startTime: '2024-01-01 00:00:00',
            endTime: '2024-01-01 23:59:59',
            activityType: ActivityType::LIMITED_QUANTITY_PURCHASE,
            productIds: [],
            totalLimit: 500
        );

        $this->assertTrue($input->isLimitedQuantity());
    }

    public function testIsLimitedQuantityWithLimitedQuantityTypeButNoLimit(): void
    {
        $input = new CreateTimeLimitActivityInput(
            name: '抢购活动',
            description: '限量抢购',
            startTime: '2024-01-01 00:00:00',
            endTime: '2024-01-01 23:59:59',
            activityType: ActivityType::LIMITED_QUANTITY_PURCHASE,
            productIds: [],
            totalLimit: null
        );

        $this->assertFalse($input->isLimitedQuantity());
    }

    public function testIsLimitedQuantityWithOtherActivityType(): void
    {
        $input = new CreateTimeLimitActivityInput(
            name: '折扣活动',
            description: '限时折扣',
            startTime: '2024-01-01 00:00:00',
            endTime: '2024-01-01 23:59:59',
            activityType: ActivityType::LIMITED_TIME_DISCOUNT,
            productIds: [],
            totalLimit: 500
        );

        $this->assertFalse($input->isLimitedQuantity());
    }

    public function testIsLimitedQuantityWithSeckillType(): void
    {
        $input = new CreateTimeLimitActivityInput(
            name: '秒杀活动',
            description: '限时秒杀',
            startTime: '2024-01-01 00:00:00',
            endTime: '2024-01-01 23:59:59',
            activityType: ActivityType::LIMITED_TIME_SECKILL,
            productIds: [],
            totalLimit: 100
        );

        $this->assertFalse($input->isLimitedQuantity());
    }

    public function testReadonlyProperties(): void
    {
        $productIds = ['prod-1'];
        $input = new CreateTimeLimitActivityInput(
            name: '测试活动',
            description: '测试描述',
            startTime: '2024-01-01 00:00:00',
            endTime: '2024-01-01 23:59:59',
            activityType: ActivityType::LIMITED_TIME_DISCOUNT,
            productIds: $productIds
        );

        // Test that all properties are accessible (readonly)
        $this->assertEquals('测试活动', $input->name);
        $this->assertEquals('测试描述', $input->description);
        $this->assertEquals('2024-01-01 00:00:00', $input->startTime);
        $this->assertEquals('2024-01-01 23:59:59', $input->endTime);
        $this->assertEquals(ActivityType::LIMITED_TIME_DISCOUNT, $input->activityType);
        $this->assertEquals($productIds, $input->productIds);
        $this->assertEquals(0, $input->priority);
        $this->assertFalse($input->exclusive);
        $this->assertNull($input->totalLimit);
        $this->assertFalse($input->preheatEnabled);
        $this->assertNull($input->preheatStartTime);
    }
}
