<?php

namespace PromotionEngineBundle\DTO;

readonly class ApplyActivityToProductsInput
{
    /**
     * @param array<ApplyActivityProductInput> $products
     */
    public function __construct(
        public string $activityId,
        public array $products,
    ) {
    }

    public function hasProducts(): bool
    {
        return [] !== $this->products;
    }

    /**
     * @return string[]
     */
    public function getProductIds(): array
    {
        return array_map(fn (ApplyActivityProductInput $product) => $product->productId, $this->products);
    }

    public function getProductById(string $productId): ?ApplyActivityProductInput
    {
        foreach ($this->products as $product) {
            if ($product->productId === $productId) {
                return $product;
            }
        }

        return null;
    }
}
