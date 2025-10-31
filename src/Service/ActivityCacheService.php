<?php

namespace PromotionEngineBundle\Service;

use PromotionEngineBundle\Entity\ActivityProduct;
use PromotionEngineBundle\Entity\TimeLimitActivity;
use PromotionEngineBundle\Repository\ActivityProductRepository;
use PromotionEngineBundle\Repository\TimeLimitActivityRepository;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[Autoconfigure(public: true)]
class ActivityCacheService
{
    private const CACHE_PREFIX_ACTIVITY = 'activity_';
    private const CACHE_PREFIX_PRODUCT_ACTIVITY = 'product_activity_';
    private const CACHE_PREFIX_ACTIVITY_PRODUCTS = 'activity_products_';
    private const CACHE_TTL = 3600;

    public function __construct(
        private readonly CacheItemPoolInterface $cache,
        private readonly TimeLimitActivityRepository $activityRepository,
        private readonly ActivityProductRepository $activityProductRepository,
        private readonly ProductActivityService $productActivityService,
    ) {
    }

    public function warmupActivityCache(string $activityId): void
    {
        $activity = $this->activityRepository->find($activityId);
        if (null === $activity || true !== $activity->isValid()) {
            return;
        }

        $cacheKey = self::CACHE_PREFIX_ACTIVITY . $activityId;
        $cacheItem = $this->cache->getItem($cacheKey);
        $cacheItem->set($activity->retrieveAdminArray());
        $cacheItem->expiresAfter(self::CACHE_TTL);
        $this->cache->save($cacheItem);

        $activityProducts = $this->activityProductRepository->findByActivityId($activityId);
        $productsCacheKey = self::CACHE_PREFIX_ACTIVITY_PRODUCTS . $activityId;
        $productsCacheItem = $this->cache->getItem($productsCacheKey);
        $productsCacheItem->set(array_map(fn (ActivityProduct $ap) => $ap->retrieveAdminArray(), $activityProducts));
        $productsCacheItem->expiresAfter(self::CACHE_TTL);
        $this->cache->save($productsCacheItem);

        foreach ($activityProducts as $activityProduct) {
            $this->warmupProductActivityCache($activityProduct->getProductId());
        }
    }

    public function warmupProductActivityCache(string $productId): void
    {
        $activityInfo = $this->productActivityService->getProductActivityInfo($productId);
        if (null === $activityInfo) {
            return;
        }

        $cacheKey = self::CACHE_PREFIX_PRODUCT_ACTIVITY . $productId;
        $cacheItem = $this->cache->getItem($cacheKey);
        $cacheItem->set($activityInfo);
        $cacheItem->expiresAfter(self::CACHE_TTL);
        $this->cache->save($cacheItem);
    }

    /**
     * @param string[] $productIds
     */
    public function warmupBatchProductActivityCache(array $productIds): void
    {
        if ([] === $productIds) {
            return;
        }

        $batchActivityInfo = $this->productActivityService->getBatchProductActivityInfo($productIds);

        foreach ($productIds as $productId) {
            $cacheKey = self::CACHE_PREFIX_PRODUCT_ACTIVITY . $productId;
            $cacheItem = $this->cache->getItem($cacheKey);

            $activityInfo = $batchActivityInfo[$productId] ?? null;
            $cacheItem->set($activityInfo);
            $cacheItem->expiresAfter(self::CACHE_TTL);
            $this->cache->save($cacheItem);
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getCachedActivityInfo(string $activityId): ?array
    {
        $cacheKey = self::CACHE_PREFIX_ACTIVITY . $activityId;
        $cacheItem = $this->cache->getItem($cacheKey);

        if ($cacheItem->isHit()) {
            $value = $cacheItem->get();

            if (!is_array($value)) {
                return null;
            }

            /** @var array<string, mixed> */
            return $value;
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getCachedProductActivityInfo(string $productId): ?array
    {
        $cacheKey = self::CACHE_PREFIX_PRODUCT_ACTIVITY . $productId;
        $cacheItem = $this->cache->getItem($cacheKey);

        if ($cacheItem->isHit()) {
            $value = $cacheItem->get();

            if (!is_array($value)) {
                return null;
            }

            /** @var array<string, mixed> */
            return $value;
        }

        return null;
    }

    /**
     * @return array<int, array<string, mixed>>|null
     */
    public function getCachedActivityProducts(string $activityId): ?array
    {
        $cacheKey = self::CACHE_PREFIX_ACTIVITY_PRODUCTS . $activityId;
        $cacheItem = $this->cache->getItem($cacheKey);

        if ($cacheItem->isHit()) {
            $value = $cacheItem->get();

            if (!is_array($value)) {
                return null;
            }

            /** @var array<int, array<string, mixed>> */
            return $value;
        }

        return null;
    }

    public function invalidateActivityCache(string $activityId): void
    {
        $cacheKeys = [
            self::CACHE_PREFIX_ACTIVITY . $activityId,
            self::CACHE_PREFIX_ACTIVITY_PRODUCTS . $activityId,
        ];

        $this->cache->deleteItems($cacheKeys);

        $activityProducts = $this->activityProductRepository->findByActivityId($activityId);
        $productCacheKeys = array_map(
            fn (ActivityProduct $ap) => self::CACHE_PREFIX_PRODUCT_ACTIVITY . $ap->getProductId(),
            $activityProducts
        );

        if ([] !== $productCacheKeys) {
            $this->cache->deleteItems($productCacheKeys);
        }
    }

    public function invalidateProductActivityCache(string $productId): void
    {
        $cacheKey = self::CACHE_PREFIX_PRODUCT_ACTIVITY . $productId;
        $this->cache->deleteItem($cacheKey);
    }

    /**
     * @param string[] $productIds
     */
    public function invalidateBatchProductActivityCache(array $productIds): void
    {
        if ([] === $productIds) {
            return;
        }

        $cacheKeys = array_map(
            fn (string $productId) => self::CACHE_PREFIX_PRODUCT_ACTIVITY . $productId,
            $productIds
        );

        $this->cache->deleteItems($cacheKeys);
    }

    public function warmupAllActiveActivities(): int
    {
        $now = new \DateTimeImmutable();
        $activeActivities = $this->activityRepository->createQueryBuilder('a')
            ->andWhere('a.valid = :valid')
            ->andWhere('a.startTime <= :now')
            ->andWhere('a.endTime >= :now')
            ->setParameter('valid', true)
            ->setParameter('now', $now)
            ->getQuery()
            ->getResult()
        ;

        $count = 0;
        if (is_iterable($activeActivities)) {
            foreach ($activeActivities as $activity) {
                if ($activity instanceof TimeLimitActivity) {
                    $activityId = $activity->getId();
                    if (null !== $activityId) {
                        $this->warmupActivityCache($activityId);
                        ++$count;
                    }
                }
            }
        }

        return $count;
    }

    public function warmupPreheatingActivities(): int
    {
        $now = new \DateTimeImmutable();
        $preheatingActivities = $this->activityRepository->createQueryBuilder('a')
            ->andWhere('a.valid = :valid')
            ->andWhere('a.preheatEnabled = :preheatEnabled')
            ->andWhere('a.preheatStartTime <= :now')
            ->andWhere('a.startTime > :now')
            ->setParameter('valid', true)
            ->setParameter('preheatEnabled', true)
            ->setParameter('now', $now)
            ->getQuery()
            ->getResult()
        ;

        $count = 0;
        if (is_iterable($preheatingActivities)) {
            foreach ($preheatingActivities as $activity) {
                if ($activity instanceof TimeLimitActivity) {
                    $activityId = $activity->getId();
                    if (null !== $activityId) {
                        $this->warmupActivityCache($activityId);
                        ++$count;
                    }
                }
            }
        }

        return $count;
    }

    public function clearAllActivityCache(): void
    {
        $this->cache->clear();
    }

    /**
     * @return array<string, mixed>
     */
    public function getCacheStats(): array
    {
        return [
            'cachePrefix' => [
                'activity' => self::CACHE_PREFIX_ACTIVITY,
                'productActivity' => self::CACHE_PREFIX_PRODUCT_ACTIVITY,
                'activityProducts' => self::CACHE_PREFIX_ACTIVITY_PRODUCTS,
            ],
            'cacheTtl' => self::CACHE_TTL,
        ];
    }
}
