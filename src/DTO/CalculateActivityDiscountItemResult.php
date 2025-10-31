<?php

namespace PromotionEngineBundle\DTO;

readonly class CalculateActivityDiscountItemResult
{
    /**
     * @param array<int, array{activityId: string, activityName: string, activityType: string, discountAmount: float, originalDiscount: float, limitReasons: array<string, mixed>}> $appliedActivities
     * @param ActivityDiscountDetail[] $discountDetails
     */
    public function __construct(
        public string $productId,
        public string $skuId,
        public int $quantity,
        public float $originalPrice,
        public float $originalAmount,
        public float $discountAmount,
        public float $finalPrice,
        public float $finalAmount,
        public array $appliedActivities = [],
        public array $discountDetails = [],
    ) {
    }

    public function getSavings(): float
    {
        return $this->originalAmount - $this->finalAmount;
    }

    public function getDiscountRate(): float
    {
        if ($this->originalAmount <= 0) {
            return 0.0;
        }

        return ($this->getSavings() / $this->originalAmount) * 100;
    }

    public function hasDiscount(): bool
    {
        return $this->discountAmount > 0;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'productId' => $this->productId,
            'skuId' => $this->skuId,
            'quantity' => $this->quantity,
            'originalPrice' => $this->originalPrice,
            'originalAmount' => $this->originalAmount,
            'discountAmount' => $this->discountAmount,
            'finalPrice' => $this->finalPrice,
            'finalAmount' => $this->finalAmount,
            'savings' => $this->getSavings(),
            'discountRate' => $this->getDiscountRate(),
            'appliedActivities' => $this->appliedActivities,
            'discountDetails' => $this->discountDetails,
        ];
    }
}
