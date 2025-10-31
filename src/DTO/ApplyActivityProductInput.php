<?php

namespace PromotionEngineBundle\DTO;

readonly class ApplyActivityProductInput
{
    public function __construct(
        public string $productId,
        public string $activityPrice,
        public int $limitPerUser = 1,
        public int $activityStock = 0,
    ) {
    }

    public function isValidPrice(): bool
    {
        return is_numeric($this->activityPrice) && bccomp($this->activityPrice, '0', 2) >= 0;
    }

    public function isValidLimitPerUser(): bool
    {
        return $this->limitPerUser > 0;
    }

    public function isValidActivityStock(): bool
    {
        return $this->activityStock >= 0;
    }

    public function isValid(): bool
    {
        return $this->isValidPrice() && $this->isValidLimitPerUser() && $this->isValidActivityStock();
    }
}
