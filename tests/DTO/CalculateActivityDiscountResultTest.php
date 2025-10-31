<?php

declare(strict_types=1);

namespace PromotionEngineBundle\Tests\DTO;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PromotionEngineBundle\DTO\ActivityDiscountDetail;
use PromotionEngineBundle\DTO\CalculateActivityDiscountItemResult;
use PromotionEngineBundle\DTO\CalculateActivityDiscountResult;

/**
 * @internal
 */
#[CoversClass(CalculateActivityDiscountResult::class)]
class CalculateActivityDiscountResultTest extends TestCase
{
    public function testConstructor(): void
    {
        $items = [
            new CalculateActivityDiscountItemResult(
                'prod-1', 'sku-1', 1, 100.0, 100.0, 10.0, 90.0, 90.0
            ),
        ];
        $appliedActivities = [
            'activity-1' => [
                'activityId' => 'activity-1',
                'activityName' => 'Test Activity',
                'activityType' => 'LIMITED_TIME_DISCOUNT',
                'discountAmount' => 10.0,
                'originalDiscount' => 10.0,
                'limitReasons' => [],
            ],
        ];
        $discountDetails = [
            new ActivityDiscountDetail(
                activityId: 'activity-1',
                activityName: 'Test Activity',
                activityType: 'DISCOUNT',
                discountType: 'PERCENTAGE',
                discountValue: 10.0,
                discountAmount: 10.0,
                reason: 'Test reason'
            ),
        ];

        $result = new CalculateActivityDiscountResult(
            items: $items,
            originalTotalAmount: 100.0,
            discountTotalAmount: 10.0,
            finalTotalAmount: 90.0,
            appliedActivities: $appliedActivities,
            discountDetails: $discountDetails,
            success: true,
            message: 'Test message'
        );

        $this->assertEquals($items, $result->items);
        $this->assertEquals(100.0, $result->originalTotalAmount);
        $this->assertEquals(10.0, $result->discountTotalAmount);
        $this->assertEquals(90.0, $result->finalTotalAmount);
        $this->assertEquals($appliedActivities, $result->appliedActivities);
        $this->assertEquals($discountDetails, $result->discountDetails);
        $this->assertTrue($result->success);
        $this->assertEquals('Test message', $result->message);
    }

    public function testConstructorWithDefaults(): void
    {
        $items = [];

        $result = new CalculateActivityDiscountResult(
            items: $items,
            originalTotalAmount: 0.0,
            discountTotalAmount: 0.0,
            finalTotalAmount: 0.0
        );

        $this->assertEquals($items, $result->items);
        $this->assertEquals(0.0, $result->originalTotalAmount);
        $this->assertEquals(0.0, $result->discountTotalAmount);
        $this->assertEquals(0.0, $result->finalTotalAmount);
        $this->assertEquals([], $result->appliedActivities);
        $this->assertEquals([], $result->discountDetails);
        $this->assertTrue($result->success);
        $this->assertEquals('', $result->message);
    }

    public function testSuccess(): void
    {
        $items = [
            new CalculateActivityDiscountItemResult(
                'prod-1', 'sku-1', 2, 50.0, 100.0, 20.0, 40.0, 80.0
            ),
        ];
        $appliedActivities = [
            'activity-1' => [
                'activityId' => 'activity-1',
                'activityName' => 'Test Activity 1',
                'activityType' => 'LIMITED_TIME_DISCOUNT',
                'discountAmount' => 10.0,
                'originalDiscount' => 10.0,
                'limitReasons' => [],
            ],
            'activity-2' => [
                'activityId' => 'activity-2',
                'activityName' => 'Test Activity 2',
                'activityType' => 'LIMITED_TIME_SECKILL',
                'discountAmount' => 10.0,
                'originalDiscount' => 10.0,
                'limitReasons' => [],
            ],
        ];
        $discountDetails = [
            new ActivityDiscountDetail(
                activityId: 'activity-1',
                activityName: 'Test Activity 1',
                activityType: 'DISCOUNT',
                discountType: 'PERCENTAGE',
                discountValue: 10.0,
                discountAmount: 10.0,
                reason: 'Test reason 1'
            ),
            new ActivityDiscountDetail(
                activityId: 'activity-2',
                activityName: 'Test Activity 2',
                activityType: 'DISCOUNT',
                discountType: 'FIXED',
                discountValue: 10.0,
                discountAmount: 10.0,
                reason: 'Test reason 2'
            ),
        ];

        $result = CalculateActivityDiscountResult::success(
            items: $items,
            originalTotalAmount: 100.0,
            discountTotalAmount: 20.0,
            finalTotalAmount: 80.0,
            appliedActivities: $appliedActivities,
            discountDetails: $discountDetails
        );

        $this->assertEquals($items, $result->items);
        $this->assertEquals(100.0, $result->originalTotalAmount);
        $this->assertEquals(20.0, $result->discountTotalAmount);
        $this->assertEquals(80.0, $result->finalTotalAmount);
        $this->assertEquals($appliedActivities, $result->appliedActivities);
        $this->assertEquals($discountDetails, $result->discountDetails);
        $this->assertTrue($result->success);
        $this->assertEquals('计算成功', $result->message);
    }

    public function testSuccessWithMinimalParameters(): void
    {
        $items = [];

        $result = CalculateActivityDiscountResult::success(
            items: $items,
            originalTotalAmount: 50.0,
            discountTotalAmount: 5.0,
            finalTotalAmount: 45.0
        );

        $this->assertEquals($items, $result->items);
        $this->assertEquals(50.0, $result->originalTotalAmount);
        $this->assertEquals(5.0, $result->discountTotalAmount);
        $this->assertEquals(45.0, $result->finalTotalAmount);
        $this->assertEquals([], $result->appliedActivities);
        $this->assertEquals([], $result->discountDetails);
        $this->assertTrue($result->success);
        $this->assertEquals('计算成功', $result->message);
    }

    public function testFailure(): void
    {
        $errorMessage = '计算失败：活动已过期';
        $result = CalculateActivityDiscountResult::failure($errorMessage);

        $this->assertEquals([], $result->items);
        $this->assertEquals(0.0, $result->originalTotalAmount);
        $this->assertEquals(0.0, $result->discountTotalAmount);
        $this->assertEquals(0.0, $result->finalTotalAmount);
        $this->assertEquals([], $result->appliedActivities);
        $this->assertEquals([], $result->discountDetails);
        $this->assertFalse($result->success);
        $this->assertEquals($errorMessage, $result->message);
    }

    public function testGetTotalSavings(): void
    {
        $result = new CalculateActivityDiscountResult(
            items: [],
            originalTotalAmount: 100.0,
            discountTotalAmount: 25.0,
            finalTotalAmount: 75.0
        );

        $this->assertEquals(25.0, $result->getTotalSavings());
    }

    public function testGetTotalSavingsWithNoSavings(): void
    {
        $result = new CalculateActivityDiscountResult(
            items: [],
            originalTotalAmount: 100.0,
            discountTotalAmount: 0.0,
            finalTotalAmount: 100.0
        );

        $this->assertEquals(0.0, $result->getTotalSavings());
    }

    public function testGetTotalSavingsWithNegativeSavings(): void
    {
        // Edge case where finalTotalAmount > originalTotalAmount
        $result = new CalculateActivityDiscountResult(
            items: [],
            originalTotalAmount: 100.0,
            discountTotalAmount: 0.0,
            finalTotalAmount: 110.0
        );

        $this->assertEquals(-10.0, $result->getTotalSavings());
    }

    public function testGetDiscountRate(): void
    {
        $result = new CalculateActivityDiscountResult(
            items: [],
            originalTotalAmount: 200.0,
            discountTotalAmount: 50.0,
            finalTotalAmount: 150.0
        );

        $this->assertEquals(25.0, $result->getDiscountRate());
    }

    public function testGetDiscountRateWithZeroOriginalAmount(): void
    {
        $result = new CalculateActivityDiscountResult(
            items: [],
            originalTotalAmount: 0.0,
            discountTotalAmount: 0.0,
            finalTotalAmount: 0.0
        );

        $this->assertEquals(0.0, $result->getDiscountRate());
    }

    public function testGetDiscountRateWithNegativeOriginalAmount(): void
    {
        // Edge case
        $result = new CalculateActivityDiscountResult(
            items: [],
            originalTotalAmount: -100.0,
            discountTotalAmount: 0.0,
            finalTotalAmount: -100.0
        );

        $this->assertEquals(0.0, $result->getDiscountRate());
    }

    public function testHasDiscountWithDiscount(): void
    {
        $result = new CalculateActivityDiscountResult(
            items: [],
            originalTotalAmount: 100.0,
            discountTotalAmount: 15.0,
            finalTotalAmount: 85.0
        );

        $this->assertTrue($result->hasDiscount());
    }

    public function testHasDiscountWithZeroDiscount(): void
    {
        $result = new CalculateActivityDiscountResult(
            items: [],
            originalTotalAmount: 100.0,
            discountTotalAmount: 0.0,
            finalTotalAmount: 100.0
        );

        $this->assertFalse($result->hasDiscount());
    }

    public function testHasDiscountWithNegativeDiscount(): void
    {
        // Edge case
        $result = new CalculateActivityDiscountResult(
            items: [],
            originalTotalAmount: 100.0,
            discountTotalAmount: -10.0,
            finalTotalAmount: 110.0
        );

        $this->assertFalse($result->hasDiscount());
    }

    public function testToArray(): void
    {
        $item = new CalculateActivityDiscountItemResult(
            'prod-1', 'sku-1', 1, 100.0, 100.0, 20.0, 80.0, 80.0
        );
        $items = [$item];
        $appliedActivities = [
            'activity-1' => [
                'activityId' => 'activity-1',
                'activityName' => 'Test Activity',
                'activityType' => 'LIMITED_TIME_DISCOUNT',
                'discountAmount' => 20.0,
                'originalDiscount' => 20.0,
                'limitReasons' => [],
            ],
        ];
        $discountDetails = [
            new ActivityDiscountDetail(
                activityId: 'activity-1',
                activityName: 'Test Activity',
                activityType: 'DISCOUNT',
                discountType: 'PERCENTAGE',
                discountValue: 20.0,
                discountAmount: 20.0,
                reason: '20% off'
            ),
        ];

        $result = new CalculateActivityDiscountResult(
            items: $items,
            originalTotalAmount: 100.0,
            discountTotalAmount: 20.0,
            finalTotalAmount: 80.0,
            appliedActivities: $appliedActivities,
            discountDetails: $discountDetails,
            success: true,
            message: 'Success'
        );

        $expected = [
            'success' => true,
            'message' => 'Success',
            'items' => [$item->toArray()],
            'originalTotalAmount' => 100.0,
            'discountTotalAmount' => 20.0,
            'finalTotalAmount' => 80.0,
            'totalSavings' => 20.0,
            'discountRate' => 20.0,
            'appliedActivities' => $appliedActivities,
            'discountDetails' => $discountDetails,
        ];

        $this->assertEquals($expected, $result->toArray());
    }

    public function testToArrayWithEmptyItems(): void
    {
        $result = new CalculateActivityDiscountResult(
            items: [],
            originalTotalAmount: 0.0,
            discountTotalAmount: 0.0,
            finalTotalAmount: 0.0,
            success: false,
            message: 'No items'
        );

        $expected = [
            'success' => false,
            'message' => 'No items',
            'items' => [],
            'originalTotalAmount' => 0.0,
            'discountTotalAmount' => 0.0,
            'finalTotalAmount' => 0.0,
            'totalSavings' => 0.0,
            'discountRate' => 0.0,
            'appliedActivities' => [],
            'discountDetails' => [],
        ];

        $this->assertEquals($expected, $result->toArray());
    }

    public function testToArrayWithMultipleItems(): void
    {
        $item1 = new CalculateActivityDiscountItemResult(
            'prod-1', 'sku-1', 1, 50.0, 50.0, 10.0, 40.0, 40.0
        );
        $item2 = new CalculateActivityDiscountItemResult(
            'prod-2', 'sku-2', 2, 30.0, 60.0, 12.0, 24.0, 48.0
        );
        $items = [$item1, $item2];

        $result = new CalculateActivityDiscountResult(
            items: $items,
            originalTotalAmount: 110.0,
            discountTotalAmount: 22.0,
            finalTotalAmount: 88.0,
            success: true,
            message: 'Multiple items'
        );

        $expected = [
            'success' => true,
            'message' => 'Multiple items',
            'items' => [$item1->toArray(), $item2->toArray()],
            'originalTotalAmount' => 110.0,
            'discountTotalAmount' => 22.0,
            'finalTotalAmount' => 88.0,
            'totalSavings' => 22.0,
            'discountRate' => 20.0,
            'appliedActivities' => [],
            'discountDetails' => [],
        ];

        $this->assertEquals($expected, $result->toArray());
    }
}
