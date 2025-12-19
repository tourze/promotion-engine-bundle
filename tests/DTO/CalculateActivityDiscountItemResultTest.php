<?php

declare(strict_types=1);

namespace PromotionEngineBundle\Tests\DTO;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PromotionEngineBundle\DTO\ActivityDiscountDetail;
use PromotionEngineBundle\DTO\CalculateActivityDiscountItemResult;

/**
 * @internal
 */
#[CoversClass(CalculateActivityDiscountItemResult::class)]
final class CalculateActivityDiscountItemResultTest extends TestCase
{
    public function testConstructor(): void
    {
        $appliedActivities = [
            [
                'activityId' => 'activity-1',
                'activityName' => 'Test Activity 1',
                'activityType' => 'LIMITED_TIME_DISCOUNT',
                'discountAmount' => 20.0,
                'originalDiscount' => 25.0,
                'limitReasons' => ['limit1' => 'reason1'],
            ],
            [
                'activityId' => 'activity-2',
                'activityName' => 'Test Activity 2',
                'activityType' => 'LIMITED_TIME_SECKILL',
                'discountAmount' => 10.0,
                'originalDiscount' => 15.0,
                'limitReasons' => ['limit2' => 'reason2'],
            ],
        ];
        $discountDetails = [
            new ActivityDiscountDetail(
                activityId: 'activity-1',
                activityName: 'Test Activity 1',
                activityType: 'DISCOUNT',
                discountType: 'PERCENTAGE',
                discountValue: 10.0,
                discountAmount: 20.0,
                reason: 'Test reason 1'
            ),
            new ActivityDiscountDetail(
                activityId: 'activity-2',
                activityName: 'Test Activity 2',
                activityType: 'DISCOUNT',
                discountType: 'FIXED',
                discountValue: 5.0,
                discountAmount: 10.0,
                reason: 'Test reason 2'
            ),
        ];

        $result = new CalculateActivityDiscountItemResult(
            productId: 'prod-123',
            skuId: 'sku-456',
            quantity: 2,
            originalPrice: 100.0,
            originalAmount: 200.0,
            discountAmount: 30.0,
            finalPrice: 85.0,
            finalAmount: 170.0,
            appliedActivities: $appliedActivities,
            discountDetails: $discountDetails
        );

        $this->assertEquals('prod-123', $result->productId);
        $this->assertEquals('sku-456', $result->skuId);
        $this->assertEquals(2, $result->quantity);
        $this->assertEquals(100.0, $result->originalPrice);
        $this->assertEquals(200.0, $result->originalAmount);
        $this->assertEquals(30.0, $result->discountAmount);
        $this->assertEquals(85.0, $result->finalPrice);
        $this->assertEquals(170.0, $result->finalAmount);
        $this->assertEquals($appliedActivities, $result->appliedActivities);
        $this->assertEquals($discountDetails, $result->discountDetails);
    }

    public function testConstructorWithDefaults(): void
    {
        $result = new CalculateActivityDiscountItemResult(
            productId: 'prod-1',
            skuId: 'sku-1',
            quantity: 1,
            originalPrice: 50.0,
            originalAmount: 50.0,
            discountAmount: 0.0,
            finalPrice: 50.0,
            finalAmount: 50.0
        );

        $this->assertEquals([], $result->appliedActivities);
        $this->assertEquals([], $result->discountDetails);
    }

    public function testGetSavings(): void
    {
        $result = new CalculateActivityDiscountItemResult(
            productId: 'prod-1',
            skuId: 'sku-1',
            quantity: 1,
            originalPrice: 100.0,
            originalAmount: 100.0,
            discountAmount: 20.0,
            finalPrice: 80.0,
            finalAmount: 80.0
        );

        $this->assertEquals(20.0, $result->getSavings());
    }

    public function testGetSavingsWithNoDiscount(): void
    {
        $result = new CalculateActivityDiscountItemResult(
            productId: 'prod-1',
            skuId: 'sku-1',
            quantity: 1,
            originalPrice: 100.0,
            originalAmount: 100.0,
            discountAmount: 0.0,
            finalPrice: 100.0,
            finalAmount: 100.0
        );

        $this->assertEquals(0.0, $result->getSavings());
    }

    public function testGetSavingsWithNegativeSavings(): void
    {
        // Edge case where finalAmount > originalAmount (should not happen normally)
        $result = new CalculateActivityDiscountItemResult(
            productId: 'prod-1',
            skuId: 'sku-1',
            quantity: 1,
            originalPrice: 100.0,
            originalAmount: 100.0,
            discountAmount: 0.0,
            finalPrice: 110.0,
            finalAmount: 110.0
        );

        $this->assertEquals(-10.0, $result->getSavings());
    }

    public function testGetDiscountRate(): void
    {
        $result = new CalculateActivityDiscountItemResult(
            productId: 'prod-1',
            skuId: 'sku-1',
            quantity: 1,
            originalPrice: 100.0,
            originalAmount: 100.0,
            discountAmount: 25.0,
            finalPrice: 75.0,
            finalAmount: 75.0
        );

        $this->assertEquals(25.0, $result->getDiscountRate());
    }

    public function testGetDiscountRateWithZeroOriginalAmount(): void
    {
        $result = new CalculateActivityDiscountItemResult(
            productId: 'prod-1',
            skuId: 'sku-1',
            quantity: 0,
            originalPrice: 0.0,
            originalAmount: 0.0,
            discountAmount: 0.0,
            finalPrice: 0.0,
            finalAmount: 0.0
        );

        $this->assertEquals(0.0, $result->getDiscountRate());
    }

    public function testGetDiscountRateWithNegativeOriginalAmount(): void
    {
        // Edge case
        $result = new CalculateActivityDiscountItemResult(
            productId: 'prod-1',
            skuId: 'sku-1',
            quantity: 1,
            originalPrice: -50.0,
            originalAmount: -50.0,
            discountAmount: 0.0,
            finalPrice: -50.0,
            finalAmount: -50.0
        );

        $this->assertEquals(0.0, $result->getDiscountRate());
    }

    public function testGetDiscountRateWithComplexCalculation(): void
    {
        $result = new CalculateActivityDiscountItemResult(
            productId: 'prod-1',
            skuId: 'sku-1',
            quantity: 3,
            originalPrice: 33.33,
            originalAmount: 99.99,
            discountAmount: 33.33,
            finalPrice: 22.22,
            finalAmount: 66.66
        );

        $expected = ((99.99 - 66.66) / 99.99) * 100;
        $this->assertEquals($expected, $result->getDiscountRate());
    }

    public function testHasDiscountWithDiscount(): void
    {
        $result = new CalculateActivityDiscountItemResult(
            productId: 'prod-1',
            skuId: 'sku-1',
            quantity: 1,
            originalPrice: 100.0,
            originalAmount: 100.0,
            discountAmount: 10.0,
            finalPrice: 90.0,
            finalAmount: 90.0
        );

        $this->assertTrue($result->hasDiscount());
    }

    public function testHasDiscountWithZeroDiscount(): void
    {
        $result = new CalculateActivityDiscountItemResult(
            productId: 'prod-1',
            skuId: 'sku-1',
            quantity: 1,
            originalPrice: 100.0,
            originalAmount: 100.0,
            discountAmount: 0.0,
            finalPrice: 100.0,
            finalAmount: 100.0
        );

        $this->assertFalse($result->hasDiscount());
    }

    public function testHasDiscountWithNegativeDiscount(): void
    {
        // Edge case
        $result = new CalculateActivityDiscountItemResult(
            productId: 'prod-1',
            skuId: 'sku-1',
            quantity: 1,
            originalPrice: 100.0,
            originalAmount: 100.0,
            discountAmount: -5.0,
            finalPrice: 105.0,
            finalAmount: 105.0
        );

        $this->assertFalse($result->hasDiscount());
    }

    public function testToArray(): void
    {
        $appliedActivities = [
            [
                'activityId' => 'activity-1',
                'activityName' => 'Test Activity',
                'activityType' => 'LIMITED_TIME_DISCOUNT',
                'discountAmount' => 50.0,
                'originalDiscount' => 50.0,
                'limitReasons' => [],
            ],
        ];
        $discountDetails = [
            new ActivityDiscountDetail(
                activityId: 'activity-1',
                activityName: 'Test Activity',
                activityType: 'DISCOUNT',
                discountType: 'PERCENTAGE',
                discountValue: 50.0,
                discountAmount: 50.0,
                reason: '50% off'
            ),
        ];

        $result = new CalculateActivityDiscountItemResult(
            productId: 'prod-123',
            skuId: 'sku-456',
            quantity: 2,
            originalPrice: 50.0,
            originalAmount: 100.0,
            discountAmount: 20.0,
            finalPrice: 40.0,
            finalAmount: 80.0,
            appliedActivities: $appliedActivities,
            discountDetails: $discountDetails
        );

        $expected = [
            'productId' => 'prod-123',
            'skuId' => 'sku-456',
            'quantity' => 2,
            'originalPrice' => 50.0,
            'originalAmount' => 100.0,
            'discountAmount' => 20.0,
            'finalPrice' => 40.0,
            'finalAmount' => 80.0,
            'savings' => 20.0,
            'discountRate' => 20.0,
            'appliedActivities' => $appliedActivities,
            'discountDetails' => $discountDetails,
        ];

        $this->assertEquals($expected, $result->toArray());
    }

    public function testToArrayWithEmptyArrays(): void
    {
        $result = new CalculateActivityDiscountItemResult(
            productId: 'prod-1',
            skuId: 'sku-1',
            quantity: 1,
            originalPrice: 100.0,
            originalAmount: 100.0,
            discountAmount: 0.0,
            finalPrice: 100.0,
            finalAmount: 100.0
        );

        $expected = [
            'productId' => 'prod-1',
            'skuId' => 'sku-1',
            'quantity' => 1,
            'originalPrice' => 100.0,
            'originalAmount' => 100.0,
            'discountAmount' => 0.0,
            'finalPrice' => 100.0,
            'finalAmount' => 100.0,
            'savings' => 0.0,
            'discountRate' => 0.0,
            'appliedActivities' => [],
            'discountDetails' => [],
        ];

        $this->assertEquals($expected, $result->toArray());
    }
}
