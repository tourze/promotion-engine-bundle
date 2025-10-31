<?php

namespace PromotionEngineBundle\Tests\DTO;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PromotionEngineBundle\DTO\ActivityDiscountDetail;
use PromotionEngineBundle\Entity\TimeLimitActivity;
use PromotionEngineBundle\Enum\ActivityStatus;
use PromotionEngineBundle\Enum\ActivityType;
use PromotionEngineBundle\Enum\DiscountType;

/**
 * @internal
 */
#[CoversClass(ActivityDiscountDetail::class)]
final class ActivityDiscountDetailTest extends TestCase
{
    public function testConstructorWithRequiredParameters(): void
    {
        $detail = new ActivityDiscountDetail(
            activityId: 'test-activity-1',
            activityName: '测试活动',
            activityType: ActivityType::LIMITED_TIME_SECKILL->value,
            discountType: DiscountType::REDUCTION->value,
            discountValue: 20.0,
            discountAmount: 20.0
        );

        $this->assertEquals('test-activity-1', $detail->activityId);
        $this->assertEquals('测试活动', $detail->activityName);
        $this->assertEquals(ActivityType::LIMITED_TIME_SECKILL->value, $detail->activityType);
        $this->assertEquals(DiscountType::REDUCTION->value, $detail->discountType);
        $this->assertEquals(20.0, $detail->discountValue);
        $this->assertEquals(20.0, $detail->discountAmount);
        $this->assertEquals('', $detail->reason);
        $this->assertEquals([], $detail->metadata);
    }

    public function testConstructorWithAllParameters(): void
    {
        $metadata = ['rule1' => 'value1', 'rule2' => 'value2'];

        $detail = new ActivityDiscountDetail(
            activityId: 'test-activity-2',
            activityName: '测试活动',
            activityType: ActivityType::LIMITED_TIME_DISCOUNT->value,
            discountType: DiscountType::DISCOUNT->value,
            discountValue: 0.75, // 75% 折扣
            discountAmount: 50.0,
            reason: '测试优惠原因',
            metadata: $metadata
        );

        $this->assertEquals('test-activity-2', $detail->activityId);
        $this->assertEquals('测试活动', $detail->activityName);
        $this->assertEquals(ActivityType::LIMITED_TIME_DISCOUNT->value, $detail->activityType);
        $this->assertEquals(DiscountType::DISCOUNT->value, $detail->discountType);
        $this->assertEquals(0.75, $detail->discountValue);
        $this->assertEquals(50.0, $detail->discountAmount);
        $this->assertEquals('测试优惠原因', $detail->reason);
        $this->assertEquals($metadata, $detail->metadata);
    }

    public function testToArrayMethod(): void
    {
        $metadata = ['test_key' => 'test_value'];

        $detail = new ActivityDiscountDetail(
            activityId: 'activity-123',
            activityName: '计算测试活动',
            activityType: ActivityType::LIMITED_TIME_SECKILL->value,
            discountType: DiscountType::REDUCTION->value,
            discountValue: 30.0,
            discountAmount: 30.0,
            reason: '正常优惠',
            metadata: $metadata
        );

        $array = $detail->toArray();

        $this->assertIsArray($array);
        $this->assertEquals('activity-123', $array['activityId']);
        $this->assertEquals('计算测试活动', $array['activityName']);
        $this->assertEquals(ActivityType::LIMITED_TIME_SECKILL->value, $array['activityType']);
        $this->assertEquals(DiscountType::REDUCTION->value, $array['discountType']);
        $this->assertEquals(30.0, $array['discountValue']);
        $this->assertEquals(30.0, $array['discountAmount']);
        $this->assertEquals('正常优惠', $array['reason']);
        $this->assertEquals($metadata, $array['metadata']);
    }

    public function testWithDifferentActivityTypes(): void
    {
        // 测试限时折扣活动
        $discountDetail = new ActivityDiscountDetail(
            activityId: 'discount-001',
            activityName: '折扣活动',
            activityType: ActivityType::LIMITED_TIME_DISCOUNT->value,
            discountType: DiscountType::DISCOUNT->value,
            discountValue: 0.9, // 90%折扣
            discountAmount: 10.0,
            reason: '限时折扣',
            metadata: ['discount_rate' => 10]
        );

        $this->assertEquals(ActivityType::LIMITED_TIME_DISCOUNT->value, $discountDetail->activityType);
        $this->assertEquals(DiscountType::DISCOUNT->value, $discountDetail->discountType);
        $this->assertEquals(0.9, $discountDetail->discountValue);

        // 测试限时秒杀活动
        $seckillDetail = new ActivityDiscountDetail(
            activityId: 'seckill-001',
            activityName: '秒杀活动',
            activityType: ActivityType::LIMITED_TIME_SECKILL->value,
            discountType: DiscountType::REDUCTION->value,
            discountValue: 100.0,
            discountAmount: 100.0,
            reason: '秒杀价格',
            metadata: ['seckill_price' => 100]
        );

        $this->assertEquals(ActivityType::LIMITED_TIME_SECKILL->value, $seckillDetail->activityType);
        $this->assertEquals(DiscountType::REDUCTION->value, $seckillDetail->discountType);
        $this->assertEquals(100.0, $seckillDetail->discountAmount);

        // 测试限量购买活动
        $limitedDetail = new ActivityDiscountDetail(
            activityId: 'limited-001',
            activityName: '限量购买',
            activityType: ActivityType::LIMITED_QUANTITY_PURCHASE->value,
            discountType: DiscountType::DISCOUNT->value,
            discountValue: 0.75, // 75%折扣
            discountAmount: 75.0
        );

        $this->assertEquals(ActivityType::LIMITED_QUANTITY_PURCHASE->value, $limitedDetail->activityType);
        $this->assertEquals(75.0, $limitedDetail->discountAmount);
    }

    public function testWithComplexMetadata(): void
    {
        $complexMetadata = [
            'min_quantity' => 2,
            'max_quantity' => 10,
            'tier_discounts' => [
                ['min_qty' => 2, 'discount_rate' => 10],
                ['min_qty' => 5, 'discount_rate' => 15],
                ['min_qty' => 10, 'discount_rate' => 20],
            ],
            'user_limits' => [
                'max_discount_per_user' => 100.0,
                'max_usage_per_day' => 3,
            ],
        ];

        $detail = new ActivityDiscountDetail(
            activityId: 'complex-001',
            activityName: '复杂规则活动',
            activityType: ActivityType::LIMITED_TIME_DISCOUNT->value,
            discountType: DiscountType::PROGRESSIVE_DISCOUNT_SCHEME->value,
            discountValue: 15.0,
            discountAmount: 75.0,
            reason: '累进折扣规则',
            metadata: $complexMetadata
        );

        $this->assertEquals($complexMetadata, $detail->metadata);
        $this->assertEquals(DiscountType::PROGRESSIVE_DISCOUNT_SCHEME->value, $detail->discountType);
        $this->assertEquals(15.0, $detail->discountValue);
        $this->assertEquals(75.0, $detail->discountAmount);

        // 验证metadata中的特定值
        $this->assertEquals(2, $detail->metadata['min_quantity']);
        $this->assertEquals(10, $detail->metadata['max_quantity']);
        $this->assertCount(3, $detail->metadata['tier_discounts']);
    }

    public function testZeroAmounts(): void
    {
        // 测试优惠金额为0
        $detail1 = new ActivityDiscountDetail(
            activityId: 'zero-test-1',
            activityName: '零金额测试',
            activityType: ActivityType::LIMITED_TIME_SECKILL->value,
            discountType: DiscountType::REDUCTION->value,
            discountValue: 0.0,
            discountAmount: 0.0
        );
        $this->assertEquals(0.0, $detail1->discountAmount);

        // 测试正常优惠金额
        $detail2 = new ActivityDiscountDetail(
            activityId: 'zero-test-2',
            activityName: '正常优惠测试',
            activityType: ActivityType::LIMITED_TIME_DISCOUNT->value,
            discountType: DiscountType::DISCOUNT->value,
            discountValue: 0.9,
            discountAmount: 10.0
        );
        $this->assertEquals(10.0, $detail2->discountAmount);
        $this->assertEquals(0.9, $detail2->discountValue);
    }

    public function testDecimalPrecision(): void
    {
        // 测试小数精度
        $detail = new ActivityDiscountDetail(
            activityId: 'decimal-test-1',
            activityName: '精度测试',
            activityType: ActivityType::LIMITED_TIME_DISCOUNT->value,
            discountType: DiscountType::DISCOUNT->value,
            discountValue: 0.8999, // 89.99%折扣
            discountAmount: 9.999
        );
        $this->assertEquals(9.999, $detail->discountAmount);
        $this->assertEquals(0.8999, $detail->discountValue);

        // 测试更复杂的小数
        $detail2 = new ActivityDiscountDetail(
            activityId: 'decimal-test-2',
            activityName: '复杂精度测试',
            activityType: ActivityType::LIMITED_TIME_SECKILL->value,
            discountType: DiscountType::REDUCTION->value,
            discountValue: 23.456,
            discountAmount: 23.456
        );
        $this->assertEquals(23.456, $detail2->discountAmount);
    }
}
