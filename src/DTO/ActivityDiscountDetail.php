<?php

namespace PromotionEngineBundle\DTO;

readonly class ActivityDiscountDetail
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public string $activityId,
        public string $activityName,
        public string $activityType,
        public string $discountType,
        public float $discountValue,
        public float $discountAmount,
        public string $reason = '',
        public array $metadata = [],
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'activityId' => $this->activityId,
            'activityName' => $this->activityName,
            'activityType' => $this->activityType,
            'discountType' => $this->discountType,
            'discountValue' => $this->discountValue,
            'discountAmount' => $this->discountAmount,
            'reason' => $this->reason,
            'metadata' => $this->metadata,
        ];
    }
}
