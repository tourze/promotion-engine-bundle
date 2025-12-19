<?php

namespace PromotionEngineBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PromotionEngineBundle\Service\ActivityCacheService;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[RunTestsInSeparateProcesses]
#[CoversClass(ActivityCacheService::class)]
final class ActivityCacheServiceTest extends AbstractIntegrationTestCase
{
    private ActivityCacheService $service;

    protected function onSetUp(): void
    {
        $this->service = self::getService(ActivityCacheService::class);
    }

    public function testWarmupActivityCache(): void
    {
        // 测试预热不存在的活动不会抛出异常
        try {
            $this->service->warmupActivityCache('non_existent_activity');
            $success = true;
        } catch (\Exception $e) {
            $success = false;
        }

        $this->assertTrue($success, '预热不存在的活动应该能够正常处理');
    }

    public function testWarmupProductActivityCache(): void
    {
        try {
            $this->service->warmupProductActivityCache('non_existent_product');
            $success = true;
        } catch (\Exception $e) {
            $success = false;
        }

        $this->assertTrue($success, '预热不存在的商品活动应该能够正常处理');
    }

    public function testInvalidateActivityCache(): void
    {
        try {
            $this->service->invalidateActivityCache('some_activity_id');
            $success = true;
        } catch (\Exception $e) {
            $success = false;
        }

        $this->assertTrue($success, '清除活动缓存应该能够正常执行');
    }

    public function testClearAllActivityCache(): void
    {
        try {
            $this->service->clearAllActivityCache();
            $success = true;
        } catch (\Exception $e) {
            $success = false;
        }

        $this->assertTrue($success, '清除所有活动缓存应该能够正常执行');
    }

    public function testGetCacheStats(): void
    {
        $stats = $this->service->getCacheStats();

        $this->assertIsArray($stats, '缓存统计应该返回数组');
        $this->assertArrayHasKey('cachePrefix', $stats);
        $this->assertArrayHasKey('cacheTtl', $stats);
        $this->assertIsArray($stats['cachePrefix']);
        $this->assertIsInt($stats['cacheTtl']);
    }

    public function testGetCachedActivityInfo(): void
    {
        $info = $this->service->getCachedActivityInfo('non_existent');
        $this->assertNull($info, '不存在的活动应该返回null');
    }

    public function testGetCachedProductActivityInfo(): void
    {
        $info = $this->service->getCachedProductActivityInfo('non_existent_product');
        $this->assertNull($info, '不存在的商品活动信息应该返回null');
    }

    public function testInvalidateProductActivityCache(): void
    {
        try {
            $this->service->invalidateProductActivityCache('test_product_id');
            $success = true;
        } catch (\Exception $e) {
            $success = false;
        }

        $this->assertTrue($success, '清除单个商品活动缓存应该能够正常执行');
    }

    public function testInvalidateBatchProductActivityCache(): void
    {
        $productIds = ['product_1', 'product_2', 'product_3'];

        try {
            $this->service->invalidateBatchProductActivityCache($productIds);
            $success = true;
        } catch (\Exception $e) {
            $success = false;
        }

        $this->assertTrue($success, '批量清除商品活动缓存应该能够正常执行');
    }

    public function testInvalidateBatchProductActivityCacheWithEmptyArray(): void
    {
        try {
            $this->service->invalidateBatchProductActivityCache([]);
            $success = true;
        } catch (\Exception $e) {
            $success = false;
        }

        $this->assertTrue($success, '空商品ID数组应该能够正常处理');
    }

    public function testWarmupBatchProductActivityCache(): void
    {
        $productIds = ['batch_product_1', 'batch_product_2'];

        try {
            $this->service->warmupBatchProductActivityCache($productIds);
            $success = true;
        } catch (\Exception $e) {
            $success = false;
        }

        $this->assertTrue($success, '批量预热商品活动缓存应该能够正常执行');
    }

    public function testWarmupBatchProductActivityCacheWithEmptyArray(): void
    {
        try {
            $this->service->warmupBatchProductActivityCache([]);
            $success = true;
        } catch (\Exception $e) {
            $success = false;
        }

        $this->assertTrue($success, '空商品ID数组应该能够正常处理');
    }

    public function testWarmupAllActiveActivities(): void
    {
        try {
            $count = $this->service->warmupAllActiveActivities();
            $success = true;
        } catch (\Exception $e) {
            $success = false;
            $count = 0;
        }

        $this->assertTrue($success, '预热所有活跃活动应该能够正常执行');
        $this->assertIsInt($count, '应该返回预热的活动数量');
        $this->assertGreaterThanOrEqual(0, $count, '预热数量应该大于等于0');
    }

    public function testWarmupPreheatingActivities(): void
    {
        try {
            $count = $this->service->warmupPreheatingActivities();
            $success = true;
        } catch (\Exception $e) {
            $success = false;
            $count = 0;
        }

        $this->assertTrue($success, '预热所有预热中的活动应该能够正常执行');
        $this->assertIsInt($count, '应该返回预热的活动数量');
        $this->assertGreaterThanOrEqual(0, $count, '预热数量应该大于等于0');
    }
}
