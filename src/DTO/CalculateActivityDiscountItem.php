<?php

namespace PromotionEngineBundle\DTO;

readonly class CalculateActivityDiscountItem
{
    public function __construct(
        public string $productId,
        public string $skuId,
        public int $quantity,
        public float $price,
    ) {
    }

    public function getTotalAmount(): float
    {
        return $this->price * $this->quantity;
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
            'price' => $this->price,
            'totalAmount' => $this->getTotalAmount(),
        ];
    }
}
