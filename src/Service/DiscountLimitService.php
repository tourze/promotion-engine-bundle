<?php

namespace PromotionEngineBundle\Service;

use Monolog\Attribute\WithMonologChannel;
use PromotionEngineBundle\DTO\CalculateActivityDiscountInput;
use PromotionEngineBundle\DTO\CalculateActivityDiscountItem;
use PromotionEngineBundle\Entity\ActivityProduct;
use PromotionEngineBundle\Entity\TimeLimitActivity;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[Autoconfigure(public: true)]
#[WithMonologChannel(channel: 'promotion_engine')]
class DiscountLimitService
{
    private const MAX_DAILY_DISCOUNT_AMOUNT = 10000.0;
    private const MAX_SINGLE_ORDER_DISCOUNT_RATE = 80.0;
    private const MAX_USER_ACTIVITY_USAGE_PER_DAY = 10;

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @return array{valid: bool, adjustedDiscount: float, limitReasons: array<int, array<string, mixed>>, originalDiscount: float}
     */
    public function validateDiscountLimits(
        TimeLimitActivity $activity,
        ActivityProduct $activityProduct,
        CalculateActivityDiscountItem $item,
        float $calculatedDiscount,
        ?string $userId = null,
    ): array {
        $validationResult = [
            'valid' => true,
            'adjustedDiscount' => $calculatedDiscount,
            'limitReasons' => [],
            'originalDiscount' => $calculatedDiscount,
        ];

        $adjustedDiscount = $calculatedDiscount;

        $result = $this->applyPerUserLimits($activity, $activityProduct, $item, $adjustedDiscount, $userId, $validationResult);
        $adjustedDiscount = $result['discount'];
        $validationResult = $result['validationResult'];

        $result = $this->applyActivityStockLimits($activityProduct, $item, $adjustedDiscount, $validationResult);
        $adjustedDiscount = $result['discount'];
        $validationResult = $result['validationResult'];

        $result = $this->applyDiscountRateLimits($item, $adjustedDiscount, $validationResult);
        $adjustedDiscount = $result['discount'];
        $validationResult = $result['validationResult'];

        $result = $this->applyDailyLimits($adjustedDiscount, $userId, $validationResult);
        $adjustedDiscount = $result['discount'];
        $validationResult = $result['validationResult'];

        $validationResult['adjustedDiscount'] = max(0.0, $adjustedDiscount);
        $validationResult['valid'] = $adjustedDiscount > 0;

        if ($adjustedDiscount !== $calculatedDiscount) {
            $this->logger->info('优惠金额已调整', [
                'activityId' => $activity->getId(),
                'productId' => $item->productId,
                'originalDiscount' => $calculatedDiscount,
                'adjustedDiscount' => $adjustedDiscount,
                'reasons' => $validationResult['limitReasons'],
                'userId' => $userId,
            ]);
        }

        /** @var array{valid: bool, adjustedDiscount: float, limitReasons: array<int, array<string, mixed>>, originalDiscount: float} */
        return $validationResult;
    }

    /**
     * @param array<string, mixed> $validationResult
     * @return array{discount: float, validationResult: array<string, mixed>}
     */
    private function applyPerUserLimits(
        TimeLimitActivity $activity,
        ActivityProduct $activityProduct,
        CalculateActivityDiscountItem $item,
        float $discount,
        ?string $userId,
        array $validationResult,
    ): array {
        if (null === $userId || '' === $userId) {
            return ['discount' => $discount, 'validationResult' => $validationResult];
        }

        $limitPerUser = $activityProduct->getLimitPerUser();
        if ($limitPerUser > 0 && $item->quantity > $limitPerUser) {
            $allowedQuantity = $limitPerUser;
            $adjustedDiscount = ($discount / $item->quantity) * $allowedQuantity;

            assert(isset($validationResult['limitReasons']) && is_array($validationResult['limitReasons']));
            $validationResult['limitReasons'][] = [
                'type' => 'per_user_quantity_limit',
                'limit' => $limitPerUser,
                'requested' => $item->quantity,
                'allowed' => $allowedQuantity,
            ];

            $this->logger->warning('用户购买数量超限', [
                'activityId' => $activity->getId(),
                'productId' => $item->productId,
                'userId' => $userId,
                'limitPerUser' => $limitPerUser,
                'requestedQuantity' => $item->quantity,
            ]);

            return ['discount' => $adjustedDiscount, 'validationResult' => $validationResult];
        }

        // Type is guaranteed by the null check above
        $activityId = $activity->getId();
        if (null === $activityId) {
            return ['discount' => $discount, 'validationResult' => $validationResult];
        }

        $userUsageToday = $this->getUserActivityUsageToday($activityId, $userId);
        if ($userUsageToday >= self::MAX_USER_ACTIVITY_USAGE_PER_DAY) {
            assert(isset($validationResult['limitReasons']) && is_array($validationResult['limitReasons']));
            $validationResult['limitReasons'][] = [
                'type' => 'daily_usage_limit',
                'limit' => self::MAX_USER_ACTIVITY_USAGE_PER_DAY,
                'current' => $userUsageToday,
            ];

            $this->logger->warning('用户日使用次数超限', [
                'activityId' => $activity->getId(),
                'userId' => $userId,
                'dailyUsage' => $userUsageToday,
                'limit' => self::MAX_USER_ACTIVITY_USAGE_PER_DAY,
            ]);

            return ['discount' => 0.0, 'validationResult' => $validationResult];
        }

        return ['discount' => $discount, 'validationResult' => $validationResult];
    }

    /**
     * 应用活动库存限制
     *
     * 注意：此方法涉及并发敏感操作，在高并发场景下库存检查可能不准确
     * 实际扣减库存时应再次验证
     */
    /**
     * @param array<string, mixed> $validationResult
     * @return array{discount: float, validationResult: array<string, mixed>}
     */
    private function applyActivityStockLimits(
        ActivityProduct $activityProduct,
        CalculateActivityDiscountItem $item,
        float $discount,
        array $validationResult,
    ): array {
        $remainingStock = $activityProduct->getRemainingStock();

        if ($item->quantity > $remainingStock) {
            if ($remainingStock <= 0) {
                if (!isset($validationResult['limitReasons']) || !is_array($validationResult['limitReasons'])) {
                    $validationResult['limitReasons'] = [];
                }

                $validationResult['limitReasons'][] = [
                    'type' => 'sold_out',
                    'remainingStock' => $remainingStock,
                ];

                $this->logger->warning('活动商品库存不足', [
                    'productId' => $item->productId,
                    'requestedQuantity' => $item->quantity,
                    'remainingStock' => $remainingStock,
                ]);

                return ['discount' => 0.0, 'validationResult' => $validationResult];
            }

            $adjustedDiscount = ($discount / $item->quantity) * $remainingStock;

            if (!isset($validationResult['limitReasons']) || !is_array($validationResult['limitReasons'])) {
                $validationResult['limitReasons'] = [];
            }
            $validationResult['limitReasons'][] = [
                'type' => 'stock_limit',
                'remainingStock' => $remainingStock,
                'requested' => $item->quantity,
                'allowed' => $remainingStock,
            ];

            $this->logger->warning('活动商品库存不足，调整优惠', [
                'productId' => $item->productId,
                'requestedQuantity' => $item->quantity,
                'remainingStock' => $remainingStock,
                'originalDiscount' => $discount,
                'adjustedDiscount' => $adjustedDiscount,
            ]);

            return ['discount' => $adjustedDiscount, 'validationResult' => $validationResult];
        }

        return ['discount' => $discount, 'validationResult' => $validationResult];
    }

    /**
     * @param array<string, mixed> $validationResult
     * @return array{discount: float, validationResult: array<string, mixed>}
     */
    private function applyDiscountRateLimits(
        CalculateActivityDiscountItem $item,
        float $discount,
        array $validationResult,
    ): array {
        $originalAmount = $item->getTotalAmount();
        $discountRate = $originalAmount > 0 ? ($discount / $originalAmount) * 100 : 0;

        if ($discountRate > self::MAX_SINGLE_ORDER_DISCOUNT_RATE) {
            $maxAllowedDiscount = $originalAmount * (self::MAX_SINGLE_ORDER_DISCOUNT_RATE / 100);

            assert(isset($validationResult['limitReasons']) && is_array($validationResult['limitReasons']));
            $validationResult['limitReasons'][] = [
                'type' => 'discount_rate_limit',
                'maxRate' => self::MAX_SINGLE_ORDER_DISCOUNT_RATE,
                'currentRate' => $discountRate,
                'maxAllowedDiscount' => $maxAllowedDiscount,
            ];

            $this->logger->warning('单笔订单优惠率超限', [
                'productId' => $item->productId,
                'originalAmount' => $originalAmount,
                'discountRate' => $discountRate,
                'maxRate' => self::MAX_SINGLE_ORDER_DISCOUNT_RATE,
                'originalDiscount' => $discount,
                'adjustedDiscount' => $maxAllowedDiscount,
            ]);

            return ['discount' => $maxAllowedDiscount, 'validationResult' => $validationResult];
        }

        return ['discount' => $discount, 'validationResult' => $validationResult];
    }

    /**
     * @param array<string, mixed> $validationResult
     * @return array{discount: float, validationResult: array<string, mixed>}
     */
    private function applyDailyLimits(
        float $discount,
        ?string $userId,
        array $validationResult,
    ): array {
        if (null === $userId || '' === $userId) {
            return ['discount' => $discount, 'validationResult' => $validationResult];
        }

        $dailyUsedAmount = $this->getUserDailyDiscountAmount($userId);
        $remainingDailyQuota = self::MAX_DAILY_DISCOUNT_AMOUNT - $dailyUsedAmount;

        if ($discount > $remainingDailyQuota) {
            $adjustedDiscount = max(0.0, $remainingDailyQuota);

            assert(isset($validationResult['limitReasons']) && is_array($validationResult['limitReasons']));
            $validationResult['limitReasons'][] = [
                'type' => 'daily_amount_limit',
                'dailyLimit' => self::MAX_DAILY_DISCOUNT_AMOUNT,
                'usedAmount' => $dailyUsedAmount,
                'remainingQuota' => $remainingDailyQuota,
                'requestedDiscount' => $discount,
                'allowedDiscount' => $adjustedDiscount,
            ];

            $this->logger->warning('用户日优惠金额超限', [
                'userId' => $userId,
                'dailyLimit' => self::MAX_DAILY_DISCOUNT_AMOUNT,
                'usedAmount' => $dailyUsedAmount,
                'requestedDiscount' => $discount,
                'adjustedDiscount' => $adjustedDiscount,
            ]);

            return ['discount' => $adjustedDiscount, 'validationResult' => $validationResult];
        }

        return ['discount' => $discount, 'validationResult' => $validationResult];
    }

    /**
     * @return array{valid: bool, adjustedTotalDiscount: float, limitReasons: array<int, array<string, mixed>>}
     */
    public function validateOrderLimits(CalculateActivityDiscountInput $input, float $totalDiscount): array
    {
        $validationResult = [
            'valid' => true,
            'adjustedTotalDiscount' => $totalDiscount,
            'limitReasons' => [],
        ];

        $originalAmount = $input->getTotalAmount();
        $discountRate = $originalAmount > 0 ? ($totalDiscount / $originalAmount) * 100 : 0;

        if ($discountRate > self::MAX_SINGLE_ORDER_DISCOUNT_RATE) {
            $maxAllowedDiscount = $originalAmount * (self::MAX_SINGLE_ORDER_DISCOUNT_RATE / 100);

            $validationResult['adjustedTotalDiscount'] = $maxAllowedDiscount;
            $validationResult['limitReasons'][] = [
                'type' => 'order_discount_rate_limit',
                'maxRate' => self::MAX_SINGLE_ORDER_DISCOUNT_RATE,
                'currentRate' => $discountRate,
                'originalDiscount' => $totalDiscount,
                'adjustedDiscount' => $maxAllowedDiscount,
            ];

            $this->logger->warning('订单总优惠率超限', [
                'originalAmount' => $originalAmount,
                'totalDiscount' => $totalDiscount,
                'discountRate' => $discountRate,
                'maxRate' => self::MAX_SINGLE_ORDER_DISCOUNT_RATE,
                'adjustedDiscount' => $maxAllowedDiscount,
                'userId' => $input->userId,
            ]);
        }

        if (null !== $input->userId && '' !== $input->userId) {
            $dailyUsedAmount = $this->getUserDailyDiscountAmount($input->userId);
            $remainingDailyQuota = self::MAX_DAILY_DISCOUNT_AMOUNT - $dailyUsedAmount;

            if ($validationResult['adjustedTotalDiscount'] > $remainingDailyQuota) {
                $finalDiscount = max(0.0, $remainingDailyQuota);

                $validationResult['adjustedTotalDiscount'] = $finalDiscount;
                $validationResult['limitReasons'][] = [
                    'type' => 'order_daily_amount_limit',
                    'dailyLimit' => self::MAX_DAILY_DISCOUNT_AMOUNT,
                    'usedAmount' => $dailyUsedAmount,
                    'remainingQuota' => $remainingDailyQuota,
                    'originalDiscount' => $totalDiscount,
                    'adjustedDiscount' => $finalDiscount,
                ];
            }
        }

        $validationResult['valid'] = $validationResult['adjustedTotalDiscount'] > 0;

        return $validationResult;
    }

    private function getUserActivityUsageToday(string $activityId, string $userId): int
    {
        return 0;
    }

    private function getUserDailyDiscountAmount(string $userId): float
    {
        return 0.0;
    }

    public function recordDiscountUsage(
        string $activityId,
        string $productId,
        float $discountAmount,
        ?string $userId = null,
    ): void {
        if (null === $userId || '' === $userId) {
            return;
        }

        $this->logger->info('记录优惠使用', [
            'activityId' => $activityId,
            'productId' => $productId,
            'discountAmount' => $discountAmount,
            'userId' => $userId,
            'timestamp' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function getDiscountLimits(): array
    {
        return [
            'maxDailyDiscountAmount' => self::MAX_DAILY_DISCOUNT_AMOUNT,
            'maxSingleOrderDiscountRate' => self::MAX_SINGLE_ORDER_DISCOUNT_RATE,
            'maxUserActivityUsagePerDay' => self::MAX_USER_ACTIVITY_USAGE_PER_DAY,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getUserDiscountSummary(?string $userId = null): array
    {
        if (null === $userId || '' === $userId) {
            return [
                'dailyUsedAmount' => 0.0,
                'dailyRemainingQuota' => self::MAX_DAILY_DISCOUNT_AMOUNT,
                'dailyUsageCount' => 0,
                'dailyRemainingUsage' => self::MAX_USER_ACTIVITY_USAGE_PER_DAY,
            ];
        }

        $dailyUsedAmount = $this->getUserDailyDiscountAmount($userId);

        return [
            'dailyUsedAmount' => $dailyUsedAmount,
            'dailyRemainingQuota' => max(0.0, self::MAX_DAILY_DISCOUNT_AMOUNT - $dailyUsedAmount),
            'dailyUsageCount' => 0,
            'dailyRemainingUsage' => self::MAX_USER_ACTIVITY_USAGE_PER_DAY,
        ];
    }
}
