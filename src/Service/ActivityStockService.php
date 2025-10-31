<?php

namespace PromotionEngineBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use PromotionEngineBundle\Entity\ActivityProduct;
use PromotionEngineBundle\Exception\ActivityException;
use PromotionEngineBundle\Repository\ActivityProductRepository;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[Autoconfigure(public: true)]
class ActivityStockService
{
    public function __construct(
        private readonly ActivityProductRepository $activityProductRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * 扣减活动库存
     *
     * 注意：此方法涉及并发敏感操作，已通过数据库事务提供基础并发控制
     * 在高并发场景下，可能需要额外的分布式锁机制
     */
    public function decreaseStock(string $activityId, string $productId, int $quantity = 1): void
    {
        if ($quantity <= 0) {
            throw ActivityException::invalidProductIds('库存扣减数量必须大于0');
        }

        $activityProduct = $this->activityProductRepository->findByActivityAndProduct($activityId, $productId);
        if (null === $activityProduct) {
            throw ActivityException::invalidProductIds('活动商品不存在');
        }

        if (!$activityProduct->isStockAvailable($quantity)) {
            throw ActivityException::insufficientStock("活动库存不足，当前库存: {$activityProduct->getRemainingStock()}，需要: {$quantity}");
        }

        $this->entityManager->beginTransaction();

        try {
            $activityProduct->increaseSoldQuantity($quantity);
            $this->activityProductRepository->save($activityProduct, true);

            $this->entityManager->commit();
        } catch (\Throwable $e) {
            $this->entityManager->rollback();
            throw ActivityException::stockOperationFailed("库存扣减失败: {$e->getMessage()}");
        }
    }

    /**
     * 增加活动库存
     *
     * 注意：此方法涉及并发敏感操作，已通过数据库事务提供基础并发控制
     * 在高并发场景下，可能需要额外的分布式锁机制
     */
    public function increaseStock(string $activityId, string $productId, int $quantity = 1): void
    {
        if ($quantity <= 0) {
            throw ActivityException::invalidProductIds('库存增加数量必须大于0');
        }

        $activityProduct = $this->activityProductRepository->findByActivityAndProduct($activityId, $productId);
        if (null === $activityProduct) {
            throw ActivityException::invalidProductIds('活动商品不存在');
        }

        $this->entityManager->beginTransaction();

        try {
            $activityProduct->decreaseSoldQuantity($quantity);
            $this->activityProductRepository->save($activityProduct, true);

            $this->entityManager->commit();
        } catch (\Throwable $e) {
            $this->entityManager->rollback();
            throw ActivityException::stockOperationFailed("库存增加失败: {$e->getMessage()}");
        }
    }

    /**
     * 设置活动库存
     *
     * 注意：此方法涉及并发敏感操作，已通过数据库事务提供基础并发控制
     * 在高并发场景下，可能需要额外的分布式锁机制
     */
    public function setActivityStock(string $activityId, string $productId, int $stock): void
    {
        if ($stock < 0) {
            throw ActivityException::invalidProductIds('活动库存不能为负数');
        }

        $activityProduct = $this->activityProductRepository->findByActivityAndProduct($activityId, $productId);
        if (null === $activityProduct) {
            throw ActivityException::invalidProductIds('活动商品不存在');
        }

        $this->entityManager->beginTransaction();

        try {
            $activityProduct->setActivityStock($stock);
            $this->activityProductRepository->save($activityProduct, true);

            $this->entityManager->commit();
        } catch (\Throwable $e) {
            $this->entityManager->rollback();
            throw ActivityException::stockOperationFailed("设置活动库存失败: {$e->getMessage()}");
        }
    }

    public function getActivityStock(string $activityId, string $productId): ?ActivityProduct
    {
        return $this->activityProductRepository->findByActivityAndProduct($activityId, $productId);
    }

    /**
     * @param string[] $productIds
     * @return ActivityProduct[]
     */
    public function getActiveActivityStocks(array $productIds): array
    {
        if ([] === $productIds) {
            return [];
        }

        return $this->activityProductRepository->findActiveByProductIds($productIds);
    }

    public function getActiveActivityStock(string $productId): ?ActivityProduct
    {
        return $this->activityProductRepository->findActiveByProductId($productId);
    }

    /**
     * @return ActivityProduct[]
     */
    public function getLowStockProducts(int $threshold = 10): array
    {
        return $this->activityProductRepository->findLowStockProducts($threshold);
    }

    /**
     * @return ActivityProduct[]
     */
    public function getSoldOutProducts(): array
    {
        return $this->activityProductRepository->findSoldOutProducts();
    }

    public function checkStockAvailability(string $activityId, string $productId, int $quantity = 1): bool
    {
        $activityProduct = $this->activityProductRepository->findByActivityAndProduct($activityId, $productId);
        if (null === $activityProduct) {
            return false;
        }

        return $activityProduct->isStockAvailable($quantity);
    }

    /**
     * @param array<string, int> $items 商品ID => 数量
     */
    public function batchCheckStockAvailability(string $activityId, array $items): bool
    {
        foreach ($items as $productId => $quantity) {
            if (!$this->checkStockAvailability($activityId, $productId, $quantity)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<string, int> $items 商品ID => 数量
     */
    public function batchDecreaseStock(string $activityId, array $items): void
    {
        $this->entityManager->beginTransaction();

        try {
            foreach ($items as $productId => $quantity) {
                $activityProduct = $this->activityProductRepository->findByActivityAndProduct($activityId, $productId);
                if (null === $activityProduct) {
                    throw ActivityException::invalidProductIds("活动商品不存在: {$productId}");
                }

                if (!$activityProduct->isStockAvailable($quantity)) {
                    throw ActivityException::insufficientStock("商品 {$productId} 活动库存不足，当前库存: {$activityProduct->getRemainingStock()}，需要: {$quantity}");
                }

                $activityProduct->increaseSoldQuantity($quantity);
                $this->activityProductRepository->save($activityProduct, false);
            }

            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (\Throwable $e) {
            $this->entityManager->rollback();
            throw ActivityException::stockOperationFailed("批量库存扣减失败: {$e->getMessage()}");
        }
    }

    /**
     * @param array<string, int> $items 商品ID => 数量
     */
    public function batchIncreaseStock(string $activityId, array $items): void
    {
        $this->entityManager->beginTransaction();

        try {
            foreach ($items as $productId => $quantity) {
                $activityProduct = $this->activityProductRepository->findByActivityAndProduct($activityId, $productId);
                if (null === $activityProduct) {
                    throw ActivityException::invalidProductIds("活动商品不存在: {$productId}");
                }

                $activityProduct->decreaseSoldQuantity($quantity);
                $this->activityProductRepository->save($activityProduct, false);
            }

            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (\Throwable $e) {
            $this->entityManager->rollback();
            throw ActivityException::stockOperationFailed("批量库存恢复失败: {$e->getMessage()}");
        }
    }
}
