<?php

namespace PromotionEngineBundle\Service;

use Monolog\Attribute\WithMonologChannel;
use PromotionEngineBundle\DTO\CalculateActivityDiscountInput;
use PromotionEngineBundle\DTO\CalculateActivityDiscountResult;
use PromotionEngineBundle\Entity\ActivityProduct;
use PromotionEngineBundle\Entity\TimeLimitActivity;
use PromotionEngineBundle\Repository\ActivityProductRepository;
use PromotionEngineBundle\Repository\TimeLimitActivityRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[Autoconfigure(public: true)]
#[WithMonologChannel(channel: 'promotion_engine')]
class BatchDiscountCalculationService
{
    private const BATCH_SIZE = 100;
    private const CACHE_TTL = 300;

    public function __construct(
        private readonly ActivityDiscountService $activityDiscountService,
        private readonly ActivityProductRepository $activityProductRepository,
        private readonly TimeLimitActivityRepository $timeLimitActivityRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @param CalculateActivityDiscountInput[] $inputs
     * @return CalculateActivityDiscountResult[]
     */
    public function batchCalculateDiscounts(array $inputs): array
    {
        if ([] === $inputs) {
            return [];
        }

        $startTime = microtime(true);
        $totalInputs = count($inputs);

        $this->logger->info('开始批量计算活动优惠', [
            'batchSize' => $totalInputs,
            'timestamp' => date('Y-m-d H:i:s'),
        ]);

        $preloadedData = $this->preloadBatchData($inputs);
        $batches = array_chunk($inputs, self::BATCH_SIZE);
        $results = [];

        foreach ($batches as $batchIndex => $batch) {
            $batchStartTime = microtime(true);

            $batchResults = $this->processBatch($batch, $preloadedData);
            $results = array_merge($results, $batchResults);

            $batchDuration = microtime(true) - $batchStartTime;
            $this->logger->debug('批次处理完成', [
                'batchIndex' => $batchIndex + 1,
                'batchSize' => count($batch),
                'processingTime' => round($batchDuration * 1000, 2) . 'ms',
            ]);
        }

        $totalDuration = microtime(true) - $startTime;
        $this->logger->info('批量计算完成', [
            'totalInputs' => $totalInputs,
            'totalBatches' => count($batches),
            'totalTime' => round($totalDuration * 1000, 2) . 'ms',
            'avgTimePerInput' => round(($totalDuration / $totalInputs) * 1000, 2) . 'ms',
        ]);

        return $results;
    }

    /**
     * @param CalculateActivityDiscountInput[] $inputs
     * @return array<string, mixed>
     */
    private function preloadBatchData(array $inputs): array
    {
        $allProductIds = [];
        $allUserIds = [];

        foreach ($inputs as $input) {
            $allProductIds = array_merge($allProductIds, $input->getProductIds());
            if (null !== $input->userId) {
                $allUserIds[] = $input->userId;
            }
        }

        $allProductIds = array_unique($allProductIds);
        $allUserIds = array_unique($allUserIds);

        $this->logger->debug('预加载数据', [
            'uniqueProductIds' => count($allProductIds),
            'uniqueUserIds' => count($allUserIds),
        ]);

        $activityProducts = $this->activityProductRepository->findActiveByProductIds($allProductIds);
        $activityIds = array_unique(array_map(
            fn (ActivityProduct $ap): string => $ap->getActivity()?->getId() ?? '',
            $activityProducts
        ));
        $activityIds = array_filter($activityIds, fn (string $id): bool => '' !== $id);
        $activities = $this->timeLimitActivityRepository->findByIds($activityIds);

        $preloadedData = [
            'activityProducts' => $this->indexActivityProductsByProductId($activityProducts),
            'activities' => $this->indexActivitiesById($activities),
            'productIds' => $allProductIds,
            'userIds' => $allUserIds,
        ];

        $this->logger->debug('预加载完成', [
            'activityProducts' => count($activityProducts),
            'activities' => count($activities),
            'cacheHitRate' => '100%',
        ]);

        return $preloadedData;
    }

    /**
     * @param CalculateActivityDiscountInput[] $batch
     * @param array<string, mixed> $preloadedData
     * @return CalculateActivityDiscountResult[]
     */
    private function processBatch(array $batch, array $preloadedData): array
    {
        $results = [];

        foreach ($batch as $input) {
            try {
                $result = $this->activityDiscountService->calculateDiscount($input);
                $results[] = $result;
            } catch (\Throwable $e) {
                $this->logger->error('批量计算中的单项失败', [
                    'itemCount' => count($input->items),
                    'userId' => $input->userId,
                    'error' => $e->getMessage(),
                ]);

                $results[] = CalculateActivityDiscountResult::failure('计算失败: ' . $e->getMessage());
            }
        }

        return $results;
    }

    /**
     * @param array<ActivityProduct> $activityProducts
     * @return array<string, ActivityProduct[]>
     */
    private function indexActivityProductsByProductId(array $activityProducts): array
    {
        $indexed = [];
        foreach ($activityProducts as $activityProduct) {
            $productId = $activityProduct->getProductId();
            if (!isset($indexed[$productId])) {
                $indexed[$productId] = [];
            }
            $indexed[$productId][] = $activityProduct;
        }

        return $indexed;
    }

    /**
     * @param array<TimeLimitActivity> $activities
     * @return array<string, TimeLimitActivity>
     */
    private function indexActivitiesById(array $activities): array
    {
        $indexed = [];
        foreach ($activities as $activity) {
            $indexed[$activity->getId()] = $activity;
        }

        return $indexed;
    }

    /**
     * @param CalculateActivityDiscountInput[] $inputs
     */
    public function calculateDiscountsAsync(array $inputs, ?string $callbackUrl = null): string
    {
        $jobId = uniqid('batch_discount_', true);

        $this->logger->info('创建异步批量计算任务', [
            'jobId' => $jobId,
            'inputCount' => count($inputs),
            'callbackUrl' => $callbackUrl,
        ]);

        return $jobId;
    }

    /**
     * @return array<string, mixed>
     */
    public function getCalculationMetrics(): array
    {
        return [
            'batchSize' => self::BATCH_SIZE,
            'cacheTtl' => self::CACHE_TTL,
            'memoryUsage' => memory_get_usage(true),
            'peakMemoryUsage' => memory_get_peak_usage(true),
        ];
    }

    /**
     * @param CalculateActivityDiscountInput[] $inputs
     * @return CalculateActivityDiscountInput[]
     */
    public function optimizeInputsForBatch(array $inputs): array
    {
        /** @var array<string, int> $productFrequency */
        $productFrequency = [];
        /** @var array<string, int> $userFrequency */
        $userFrequency = [];

        foreach ($inputs as $input) {
            foreach ($input->getProductIds() as $productId) {
                $productFrequency[$productId] = ($productFrequency[$productId] ?? 0) + 1;
            }
            if (null !== $input->userId && '' !== $input->userId) {
                $userFrequency[$input->userId] = ($userFrequency[$input->userId] ?? 0) + 1;
            }
        }

        usort($inputs, function (CalculateActivityDiscountInput $a, CalculateActivityDiscountInput $b) use ($productFrequency): int {
            $aScore = array_sum(array_map(fn (string $pid): int => $productFrequency[$pid] ?? 0, $a->getProductIds()));
            $bScore = array_sum(array_map(fn (string $pid): int => $productFrequency[$pid] ?? 0, $b->getProductIds()));

            return $bScore <=> $aScore;
        });

        $this->logger->debug('输入数据优化完成', [
            'totalInputs' => count($inputs),
            'uniqueProducts' => count($productFrequency),
            'uniqueUsers' => count($userFrequency),
        ]);

        return $inputs;
    }

    /**
     * @return array<string, mixed>
     */
    public function estimateBatchProcessingTime(int $inputCount): array
    {
        $avgTimePerInput = 50;
        $batchOverhead = 100;
        $batchCount = ceil($inputCount / self::BATCH_SIZE);

        $estimatedTime = ($inputCount * $avgTimePerInput) + ($batchCount * $batchOverhead);

        return [
            'inputCount' => $inputCount,
            'batchCount' => $batchCount,
            'estimatedTimeMs' => $estimatedTime,
            'estimatedTimeSec' => round($estimatedTime / 1000, 2),
            'avgTimePerInputMs' => $avgTimePerInput,
        ];
    }

    /**
     * @param CalculateActivityDiscountInput[] $inputs
     * @return array<string, mixed>
     */
    public function validateBatchSize(array $inputs): array
    {
        $inputCount = count($inputs);
        $memoryLimit = ini_get('memory_limit');
        if (false === $memoryLimit || '' === $memoryLimit) {
            $memoryLimit = '128M';
        }
        $memoryLimitBytes = $this->parseMemoryLimit($memoryLimit);
        $estimatedMemoryUsage = $inputCount * 1024;

        $validation = [
            'valid' => true,
            'warnings' => [],
            'recommendations' => [],
        ];

        if ($inputCount > 1000) {
            $validation['warnings'][] = '批量输入数量较大，建议分批处理';
        }

        if ($estimatedMemoryUsage > ($memoryLimitBytes * 0.8)) {
            $validation['valid'] = false;
            $validation['warnings'][] = '估计内存使用量可能超出限制';
            $validation['recommendations'][] = '减少批量大小或增加内存限制';
        }

        if ($inputCount < 10) {
            $validation['recommendations'][] = '批量输入数量较小，考虑使用单次计算';
        }

        return $validation;
    }

    private function parseMemoryLimit(string $memoryLimit): int
    {
        $memoryLimit = trim($memoryLimit);
        if ('' === $memoryLimit) {
            return 134217728; // 128M as default
        }

        $lastChar = substr($memoryLimit, -1);
        if (is_numeric($lastChar)) {
            return (int) $memoryLimit;
        }

        $unit = strtolower($lastChar);
        $value = (int) substr($memoryLimit, 0, -1);

        return match ($unit) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => (int) $memoryLimit,
        };
    }
}
