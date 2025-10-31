<?php

namespace PromotionEngineBundle\DTO;

use PromotionEngineBundle\Enum\ActivityType;

readonly class CreateTimeLimitActivityInput
{
    /**
     * @param string[] $productIds
     */
    public function __construct(
        public string $name,
        public string $description,
        public string $startTime,
        public string $endTime,
        public ActivityType $activityType,
        public array $productIds,
        public int $priority = 0,
        public bool $exclusive = false,
        public ?int $totalLimit = null,
        public bool $preheatEnabled = false,
        public ?string $preheatStartTime = null,
    ) {
    }

    public function hasProductIds(): bool
    {
        return [] !== $this->productIds;
    }

    public function isValidTimeRange(): bool
    {
        $start = new \DateTimeImmutable($this->startTime);
        $end = new \DateTimeImmutable($this->endTime);

        return $start < $end;
    }

    public function isValidPreheatTime(): bool
    {
        if (!$this->preheatEnabled || null === $this->preheatStartTime) {
            return true;
        }

        $preheat = new \DateTimeImmutable($this->preheatStartTime);
        $start = new \DateTimeImmutable($this->startTime);

        return $preheat < $start;
    }

    public function isLimitedQuantity(): bool
    {
        return ActivityType::LIMITED_QUANTITY_PURCHASE === $this->activityType && null !== $this->totalLimit;
    }
}
