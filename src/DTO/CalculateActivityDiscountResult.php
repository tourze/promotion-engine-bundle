<?php

namespace PromotionEngineBundle\DTO;

readonly class CalculateActivityDiscountResult
{
    /**
     * @param CalculateActivityDiscountItemResult[] $items
     * @param array<string, array{activityId: string, activityName: string, activityType: string, discountAmount: float, originalDiscount: float, limitReasons: array<string, mixed>}> $appliedActivities
     * @param ActivityDiscountDetail[] $discountDetails
     */
    public function __construct(
        public array $items,
        public float $originalTotalAmount,
        public float $discountTotalAmount,
        public float $finalTotalAmount,
        public array $appliedActivities = [],
        public array $discountDetails = [],
        public bool $success = true,
        public string $message = '',
    ) {
    }

    /**
     * @param CalculateActivityDiscountItemResult[] $items
     * @param array<string, array{activityId: string, activityName: string, activityType: string, discountAmount: float, originalDiscount: float, limitReasons: array<string, mixed>}> $appliedActivities
     * @param ActivityDiscountDetail[] $discountDetails
     */
    public static function success(
        array $items,
        float $originalTotalAmount,
        float $discountTotalAmount,
        float $finalTotalAmount,
        array $appliedActivities = [],
        array $discountDetails = [],
    ): self {
        return new self(
            items: $items,
            originalTotalAmount: $originalTotalAmount,
            discountTotalAmount: $discountTotalAmount,
            finalTotalAmount: $finalTotalAmount,
            appliedActivities: $appliedActivities,
            discountDetails: $discountDetails,
            success: true,
            message: '计算成功'
        );
    }

    public static function failure(string $message): self
    {
        return new self(
            items: [],
            originalTotalAmount: 0.0,
            discountTotalAmount: 0.0,
            finalTotalAmount: 0.0,
            success: false,
            message: $message
        );
    }

    public function getTotalSavings(): float
    {
        return $this->originalTotalAmount - $this->finalTotalAmount;
    }

    public function getDiscountRate(): float
    {
        if ($this->originalTotalAmount <= 0) {
            return 0.0;
        }

        return ($this->getTotalSavings() / $this->originalTotalAmount) * 100;
    }

    public function hasDiscount(): bool
    {
        return $this->discountTotalAmount > 0;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'items' => array_map(fn ($item) => $item->toArray(), $this->items),
            'originalTotalAmount' => $this->originalTotalAmount,
            'discountTotalAmount' => $this->discountTotalAmount,
            'finalTotalAmount' => $this->finalTotalAmount,
            'totalSavings' => $this->getTotalSavings(),
            'discountRate' => $this->getDiscountRate(),
            'appliedActivities' => $this->appliedActivities,
            'discountDetails' => $this->discountDetails,
        ];
    }
}
