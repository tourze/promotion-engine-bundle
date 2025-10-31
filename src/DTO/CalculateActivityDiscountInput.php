<?php

namespace PromotionEngineBundle\DTO;

readonly class CalculateActivityDiscountInput
{
    /**
     * @param CalculateActivityDiscountItem[] $items
     */
    public function __construct(
        public array $items,
        public ?string $userId = null,
    ) {
    }

    public function hasItems(): bool
    {
        return [] !== $this->items;
    }

    /**
     * @return string[]
     */
    public function getProductIds(): array
    {
        return array_map(fn ($item) => $item->productId, $this->items);
    }

    /**
     * @return string[]
     */
    public function getSkuIds(): array
    {
        return array_map(fn ($item) => $item->skuId, $this->items);
    }

    public function getItemByProductId(string $productId): ?CalculateActivityDiscountItem
    {
        foreach ($this->items as $item) {
            if ($item->productId === $productId) {
                return $item;
            }
        }

        return null;
    }

    public function getTotalAmount(): float
    {
        return array_reduce($this->items, fn ($total, $item) => $total + ($item->price * $item->quantity), 0.0);
    }

    public function getTotalQuantity(): int
    {
        return array_reduce($this->items, fn ($total, $item) => $total + $item->quantity, 0);
    }
}
