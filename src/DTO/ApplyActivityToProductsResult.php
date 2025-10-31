<?php

namespace PromotionEngineBundle\DTO;

readonly class ApplyActivityToProductsResult
{
    /**
     * @param string[] $addedProductIds
     * @param string[] $failedProductIds
     */
    public function __construct(
        public bool $success,
        public string $message,
        public array $addedProductIds = [],
        public array $failedProductIds = [],
    ) {
    }

    public static function success(int $addedCount, int $totalCount): self
    {
        if ($addedCount === $totalCount) {
            $message = "成功将 {$addedCount} 个商品添加到活动中";
        } else {
            $failedCount = $totalCount - $addedCount;
            $message = "成功添加 {$addedCount} 个商品，{$failedCount} 个商品添加失败";
        }

        return new self(
            success: true,
            message: $message,
        );
    }

    /**
     * @param string[] $addedProductIds
     * @param string[] $failedProductIds
     */
    public static function partial(array $addedProductIds, array $failedProductIds): self
    {
        $addedCount = count($addedProductIds);
        $failedCount = count($failedProductIds);

        return new self(
            success: true,
            message: "成功添加 {$addedCount} 个商品，{$failedCount} 个商品添加失败",
            addedProductIds: $addedProductIds,
            failedProductIds: $failedProductIds,
        );
    }

    public static function failure(string $message): self
    {
        return new self(
            success: false,
            message: $message,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'addedProductIds' => $this->addedProductIds,
            'failedProductIds' => $this->failedProductIds,
            'addedCount' => count($this->addedProductIds),
            'failedCount' => count($this->failedProductIds),
        ];
    }
}
