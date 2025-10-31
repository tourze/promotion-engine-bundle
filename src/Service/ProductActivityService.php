<?php

namespace PromotionEngineBundle\Service;

use PromotionEngineBundle\Entity\ActivityProduct;
use PromotionEngineBundle\Entity\TimeLimitActivity;
use PromotionEngineBundle\Repository\ActivityProductRepository;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[Autoconfigure(public: true)]
class ProductActivityService
{
    public function __construct(
        private readonly ActivityProductRepository $activityProductRepository,
    ) {
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getProductActivityInfo(string $productId): ?array
    {
        $activityProduct = $this->activityProductRepository->findActiveByProductId($productId);
        if (null === $activityProduct) {
            return null;
        }

        $activity = $activityProduct->getActivity();
        if (null === $activity || true !== $activity->isValid()) {
            return null;
        }

        return $this->buildActivityInfo($activity, $activityProduct);
    }

    /**
     * @param string[] $productIds
     * @return array<string, array<string, mixed>>
     */
    public function getBatchProductActivityInfo(array $productIds): array
    {
        if (0 === count($productIds)) {
            return [];
        }

        $activityProducts = $this->activityProductRepository->findActiveByProductIds($productIds);
        $result = [];

        foreach ($activityProducts as $activityProduct) {
            $activity = $activityProduct->getActivity();
            if (null === $activity || true !== $activity->isValid()) {
                continue;
            }

            $productId = $activityProduct->getProductId();
            $result[$productId] = $this->buildActivityInfo($activity, $activityProduct);
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildActivityInfo(TimeLimitActivity $activity, ActivityProduct $activityProduct): array
    {
        $now = new \DateTimeImmutable();

        return [
            'activityId' => $activity->getId(),
            'activityName' => $activity->getName(),
            'activityType' => $activity->getActivityType()->value,
            'activityTypeLabel' => $activity->getActivityType()->getLabel(),
            'status' => $activity->getStatus()->value,
            'statusLabel' => $activity->getStatus()->getLabel(),
            'startTime' => $activity->getStartTime()->format('Y-m-d H:i:s'),
            'endTime' => $activity->getEndTime()->format('Y-m-d H:i:s'),
            'priority' => $activity->getPriority(),
            'exclusive' => $activity->isExclusive(),
            'activityPrice' => $activityProduct->getActivityPrice(),
            'limitPerUser' => $activityProduct->getLimitPerUser(),
            'activityStock' => $activityProduct->getActivityStock(),
            'soldQuantity' => $activityProduct->getSoldQuantity(),
            'remainingStock' => $activityProduct->getRemainingStock(),
            'stockUtilization' => $activityProduct->getStockUtilization(),
            'isSoldOut' => $activityProduct->isSoldOut(),
            'isActive' => $activity->isActive($now),
            'isFinished' => $activity->isFinished($now),
            'isInPreheatPeriod' => $activity->isInPreheatPeriod($now),
            'preheatEnabled' => $activity->isPreheatEnabled(),
            'preheatStartTime' => $activity->getPreheatStartTime()?->format('Y-m-d H:i:s'),
            'totalLimit' => $activity->getTotalLimit(),
            'activitySoldQuantity' => $activity->getSoldQuantity(),
            'activityRemainingQuantity' => $activity->getRemainingQuantity(),
            'activityIsSoldOut' => $activity->isSoldOut(),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getActiveActivityForProduct(string $productId): ?array
    {
        return $this->getProductActivityInfo($productId);
    }

    public function hasActiveActivity(string $productId): bool
    {
        $activityProduct = $this->activityProductRepository->findActiveByProductId($productId);

        return null !== $activityProduct;
    }

    /**
     * @param string[] $productIds
     * @return string[]
     */
    public function filterProductsWithActiveActivity(array $productIds): array
    {
        if (0 === count($productIds)) {
            return [];
        }

        $activityProducts = $this->activityProductRepository->findActiveByProductIds($productIds);

        return array_map(fn (ActivityProduct $ap) => $ap->getProductId(), $activityProducts);
    }

    /**
     * @param string[] $productIds
     * @return string[]
     */
    public function filterProductsWithoutActiveActivity(array $productIds): array
    {
        if (0 === count($productIds)) {
            return [];
        }

        $productsWithActivity = $this->filterProductsWithActiveActivity($productIds);

        return array_diff($productIds, $productsWithActivity);
    }

    public function getActivityPrice(string $productId): ?string
    {
        $activityProduct = $this->activityProductRepository->findActiveByProductId($productId);

        return $activityProduct?->getActivityPrice();
    }

    public function getLimitPerUser(string $productId): ?int
    {
        $activityProduct = $this->activityProductRepository->findActiveByProductId($productId);

        return $activityProduct?->getLimitPerUser();
    }

    /**
     * 获取剩余库存
     *
     * 注意：此方法涉及并发敏感操作，在高并发场景下返回值可能不准确
     * 实际扣减库存时应再次验证
     */
    public function getRemainingStock(string $productId): ?int
    {
        $activityProduct = $this->activityProductRepository->findActiveByProductId($productId);

        return $activityProduct?->getRemainingStock();
    }

    /**
     * 检查商品库存是否可用
     *
     * 注意：此方法涉及并发敏感操作，在高并发场景下检查结果可能不准确
     * 实际扣减库存时应再次验证
     */
    public function isProductStockAvailable(string $productId, int $quantity = 1): bool
    {
        $activityProduct = $this->activityProductRepository->findActiveByProductId($productId);
        if (null === $activityProduct) {
            return false;
        }

        return $activityProduct->isStockAvailable($quantity);
    }
}
