<?php

namespace PromotionEngineBundle\Service;

use Monolog\Attribute\WithMonologChannel;
use PromotionEngineBundle\DTO\ActivityDiscountDetail;
use PromotionEngineBundle\DTO\CalculateActivityDiscountInput;
use PromotionEngineBundle\DTO\CalculateActivityDiscountItem;
use PromotionEngineBundle\DTO\CalculateActivityDiscountItemResult;
use PromotionEngineBundle\DTO\CalculateActivityDiscountResult;
use PromotionEngineBundle\Entity\ActivityProduct;
use PromotionEngineBundle\Entity\TimeLimitActivity;
use PromotionEngineBundle\Enum\ActivityType;
use PromotionEngineBundle\Enum\DiscountType;
use PromotionEngineBundle\Repository\ActivityProductRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[Autoconfigure(public: true)]
#[WithMonologChannel(channel: 'promotion_engine')]
class ActivityDiscountService
{
    public function __construct(
        private readonly ActivityProductRepository $activityProductRepository,
        private readonly DiscountCalculatorService $discountCalculatorService,
        private readonly DiscountStackingService $discountStackingService,
        private readonly DiscountLimitService $discountLimitService,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function calculateDiscount(CalculateActivityDiscountInput $input): CalculateActivityDiscountResult
    {
        try {
            if (!$input->hasItems()) {
                return CalculateActivityDiscountResult::failure('商品列表为空');
            }

            $this->logCalculationStart($input);
            $activeActivities = $this->getActiveActivitiesForProducts($input->getProductIds());

            if ([] === $activeActivities) {
                return $this->buildNoDiscountResult($input);
            }

            $applicableActivities = $this->discountStackingService->filterStackableActivities($activeActivities, $input);

            return $this->processItemDiscounts($input, $applicableActivities);
        } catch (\Throwable $e) {
            $this->logger->error('活动优惠计算失败', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return CalculateActivityDiscountResult::failure('计算失败: ' . $e->getMessage());
        }
    }

    private function logCalculationStart(CalculateActivityDiscountInput $input): void
    {
        $this->logger->info('开始计算活动优惠', [
            'itemCount' => count($input->items),
            'totalAmount' => $input->getTotalAmount(),
            'userId' => $input->userId,
        ]);
    }

    /**
     * @param TimeLimitActivity[] $applicableActivities
     */
    private function processItemDiscounts(
        CalculateActivityDiscountInput $input,
        array $applicableActivities,
    ): CalculateActivityDiscountResult {
        /** @var CalculateActivityDiscountItemResult[] $itemResults */
        $itemResults = [];
        $totalDiscountAmount = 0.0;
        /** @var array<string, array{activityId: string, activityName: string, activityType: string, discountAmount: float, originalDiscount: float, limitReasons: array<string, mixed>}> $appliedActivities */
        $appliedActivities = [];
        /** @var ActivityDiscountDetail[] $discountDetails */
        $discountDetails = [];

        foreach ($input->items as $item) {
            $itemResult = $this->calculateItemDiscount($item, $applicableActivities, $input->userId);
            $itemResults[] = $itemResult;
            $totalDiscountAmount += $itemResult->discountAmount;

            $aggregateResult = $this->aggregateItemResults($itemResult, $appliedActivities, $discountDetails);
            $appliedActivities = $aggregateResult['appliedActivities'];
            $discountDetails = $aggregateResult['discountDetails'];
        }

        return $this->finalizeCalculation($input, $itemResults, $totalDiscountAmount, $appliedActivities, $discountDetails);
    }

    /**
     * @param array<string, array{activityId: string, activityName: string, activityType: string, discountAmount: float, originalDiscount: float, limitReasons: array<string, mixed>}> $appliedActivities
     * @param ActivityDiscountDetail[] $discountDetails
     * @return array{appliedActivities: array<string, array{activityId: string, activityName: string, activityType: string, discountAmount: float, originalDiscount: float, limitReasons: array<string, mixed>}>, discountDetails: ActivityDiscountDetail[]}
     */
    private function aggregateItemResults(
        CalculateActivityDiscountItemResult $itemResult,
        array $appliedActivities,
        array $discountDetails,
    ): array {
        foreach ($itemResult->appliedActivities as $activityInfo) {
            $appliedActivities[$activityInfo['activityId']] = $activityInfo;
        }

        foreach ($itemResult->discountDetails as $detail) {
            $discountDetails[] = $detail;
        }

        return ['appliedActivities' => $appliedActivities, 'discountDetails' => $discountDetails];
    }

    /**
     * @param CalculateActivityDiscountItemResult[] $itemResults
     * @param array<string, array{activityId: string, activityName: string, activityType: string, discountAmount: float, originalDiscount: float, limitReasons: array<string, mixed>}> $appliedActivities
     * @param ActivityDiscountDetail[] $discountDetails
     */
    private function finalizeCalculation(
        CalculateActivityDiscountInput $input,
        array $itemResults,
        float $totalDiscountAmount,
        array $appliedActivities,
        array $discountDetails,
    ): CalculateActivityDiscountResult {
        $orderLimitValidation = $this->discountLimitService->validateOrderLimits($input, $totalDiscountAmount);
        $adjustedTotalDiscountAmount = $orderLimitValidation['adjustedTotalDiscount'];
        $finalTotalAmount = max(0, $input->getTotalAmount() - $adjustedTotalDiscountAmount);

        if ($adjustedTotalDiscountAmount !== $totalDiscountAmount) {
            $discountDetails = $this->handleDiscountAdjustment($totalDiscountAmount, $adjustedTotalDiscountAmount, $orderLimitValidation, $discountDetails);
        }

        $this->logCalculationCompletion($input, $totalDiscountAmount, $adjustedTotalDiscountAmount, $finalTotalAmount, $appliedActivities);

        return CalculateActivityDiscountResult::success(
            items: $itemResults,
            originalTotalAmount: $input->getTotalAmount(),
            discountTotalAmount: $adjustedTotalDiscountAmount,
            finalTotalAmount: $finalTotalAmount,
            appliedActivities: $appliedActivities,
            discountDetails: $discountDetails
        );
    }

    /**
     * @param array<string, mixed> $orderLimitValidation
     * @param ActivityDiscountDetail[] $discountDetails
     * @return ActivityDiscountDetail[]
     */
    private function handleDiscountAdjustment(
        float $totalDiscountAmount,
        float $adjustedTotalDiscountAmount,
        array $orderLimitValidation,
        array $discountDetails,
    ): array {
        $this->logger->warning('订单总优惠金额已调整', [
            'originalDiscount' => $totalDiscountAmount,
            'adjustedDiscount' => $adjustedTotalDiscountAmount,
            'reasons' => $orderLimitValidation['limitReasons'],
        ]);

        if ([] !== $orderLimitValidation['limitReasons']) {
            $discountDetails[] = new ActivityDiscountDetail(
                activityId: 'system',
                activityName: '系统优惠上限控制',
                activityType: 'system_limit',
                discountType: 'reduction',
                discountValue: $adjustedTotalDiscountAmount,
                discountAmount: $adjustedTotalDiscountAmount - $totalDiscountAmount,
                reason: '订单优惠金额超出系统限制',
                metadata: $orderLimitValidation
            );
        }

        return $discountDetails;
    }

    /**
     * @param array<string, array{activityId: string, activityName: string, activityType: string, discountAmount: float, originalDiscount: float, limitReasons: array<string, mixed>}> $appliedActivities
     */
    private function logCalculationCompletion(
        CalculateActivityDiscountInput $input,
        float $totalDiscountAmount,
        float $adjustedTotalDiscountAmount,
        float $finalTotalAmount,
        array $appliedActivities,
    ): void {
        $this->logger->info('活动优惠计算完成', [
            'originalAmount' => $input->getTotalAmount(),
            'originalDiscountAmount' => $totalDiscountAmount,
            'adjustedDiscountAmount' => $adjustedTotalDiscountAmount,
            'finalAmount' => $finalTotalAmount,
            'appliedActivitiesCount' => count($appliedActivities),
        ]);
    }

    /**
     * @param string[] $productIds
     * @return TimeLimitActivity[]
     */
    private function getActiveActivitiesForProducts(array $productIds): array
    {
        $activityProducts = $this->activityProductRepository->findActiveByProductIds($productIds);
        $activities = [];

        foreach ($activityProducts as $activityProduct) {
            $activity = $activityProduct->getActivity();
            if (null !== $activity && true === $activity->isValid() && $activity->isActive(new \DateTimeImmutable())) {
                $activities[$activity->getId()] = $activity;
            }
        }

        return array_values($activities);
    }

    /**
     * @param TimeLimitActivity[] $applicableActivities
     */
    private function calculateItemDiscount(
        CalculateActivityDiscountItem $item,
        array $applicableActivities,
        ?string $userId,
    ): CalculateActivityDiscountItemResult {
        $originalAmount = $item->getTotalAmount();
        $discountAmount = 0.0;
        /** @var array<int, array{activityId: string, activityName: string, activityType: string, discountAmount: float, originalDiscount: float, limitReasons: array<string, mixed>}> $appliedActivities */
        $appliedActivities = [];
        /** @var ActivityDiscountDetail[] $discountDetails */
        $discountDetails = [];

        $activityProduct = $this->activityProductRepository->findActiveByProductId($item->productId);
        if (null === $activityProduct) {
            return new CalculateActivityDiscountItemResult(
                productId: $item->productId,
                skuId: $item->skuId,
                quantity: $item->quantity,
                originalPrice: $item->price,
                originalAmount: $originalAmount,
                discountAmount: 0.0,
                finalPrice: $item->price,
                finalAmount: $originalAmount
            );
        }

        foreach ($applicableActivities as $activity) {
            $result = $this->processActivityDiscount($activity, $activityProduct, $item, $userId);
            if (null !== $result) {
                $discountAmount += $result['discountAmount'];
                $appliedActivities[] = $result['appliedActivity'];
                $discountDetails[] = $result['discountDetail'];
            }
        }

        $finalAmount = max(0, $originalAmount - $discountAmount);
        $finalPrice = $item->quantity > 0 ? $finalAmount / $item->quantity : $item->price;

        return new CalculateActivityDiscountItemResult(
            productId: $item->productId,
            skuId: $item->skuId,
            quantity: $item->quantity,
            originalPrice: $item->price,
            originalAmount: $originalAmount,
            discountAmount: $discountAmount,
            finalPrice: $finalPrice,
            finalAmount: $finalAmount,
            appliedActivities: [],
            discountDetails: $discountDetails
        );
    }

    private function isActivityApplicableToProduct(TimeLimitActivity $activity, string $productId): bool
    {
        $productIds = $activity->getProductIds();

        return [] === $productIds || in_array($productId, $productIds, true);
    }

    private function calculateActivityDiscountForItem(
        TimeLimitActivity $activity,
        ActivityProduct $activityProduct,
        CalculateActivityDiscountItem $item,
        ?string $userId,
    ): float {
        return $this->discountCalculatorService->calculateDiscount($activity, $activityProduct, $item, $userId);
    }

    private function getDiscountTypeForActivity(TimeLimitActivity $activity): string
    {
        return match ($activity->getActivityType()) {
            ActivityType::LIMITED_TIME_DISCOUNT => DiscountType::DISCOUNT->value,
            ActivityType::LIMITED_TIME_SECKILL => DiscountType::REDUCTION->value,
            ActivityType::LIMITED_QUANTITY_PURCHASE => DiscountType::DISCOUNT->value,
        };
    }

    private function getDiscountValueForActivity(TimeLimitActivity $activity, ActivityProduct $activityProduct): float
    {
        return (float) $activityProduct->getActivityPrice();
    }

    /**
     * @return array{discountAmount: float, appliedActivity: array<string, mixed>, discountDetail: ActivityDiscountDetail}|null
     */
    private function processActivityDiscount(
        TimeLimitActivity $activity,
        ActivityProduct $activityProduct,
        CalculateActivityDiscountItem $item,
        ?string $userId,
    ): ?array {
        if (!$this->isActivityApplicableToProduct($activity, $item->productId)) {
            return null;
        }

        $calculatedDiscount = $this->calculateActivityDiscountForItem($activity, $activityProduct, $item, $userId);
        if ($calculatedDiscount <= 0) {
            return null;
        }

        $limitValidation = $this->discountLimitService->validateDiscountLimits(
            $activity, $activityProduct, $item, $calculatedDiscount, $userId
        );

        $activityDiscount = $limitValidation['adjustedDiscount'];
        if ($activityDiscount <= 0) {
            return null;
        }

        return [
            'discountAmount' => $activityDiscount,
            'appliedActivity' => [
                'activityId' => (string) $activity->getId(),
                'activityName' => $activity->getName(),
                'activityType' => $activity->getActivityType()->value,
                'discountAmount' => $activityDiscount,
                'originalDiscount' => $calculatedDiscount,
                'limitReasons' => $limitValidation['limitReasons'],
            ],
            'discountDetail' => new ActivityDiscountDetail(
                activityId: (string) $activity->getId(),
                activityName: $activity->getName(),
                activityType: $activity->getActivityType()->value,
                discountType: $this->getDiscountTypeForActivity($activity),
                discountValue: $this->getDiscountValueForActivity($activity, $activityProduct),
                discountAmount: $activityDiscount,
                reason: $this->getDiscountReason($activity, $item),
                metadata: array_merge(
                    $this->getDiscountMetadata($activity, $activityProduct, $item),
                    ['limitValidation' => $limitValidation]
                )
            ),
        ];
    }

    private function getDiscountReason(TimeLimitActivity $activity, CalculateActivityDiscountItem $item): string
    {
        return match ($activity->getActivityType()) {
            ActivityType::LIMITED_TIME_DISCOUNT => '参与限时折扣活动',
            ActivityType::LIMITED_TIME_SECKILL => '参与限时秒杀活动',
            ActivityType::LIMITED_QUANTITY_PURCHASE => '参与限量抢购活动',
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function getDiscountMetadata(
        TimeLimitActivity $activity,
        ActivityProduct $activityProduct,
        CalculateActivityDiscountItem $item,
    ): array {
        return [
            'activityStartTime' => $activity->getStartTime()->format('Y-m-d H:i:s'),
            'activityEndTime' => $activity->getEndTime()->format('Y-m-d H:i:s'),
            'activityPrice' => $activityProduct->getActivityPrice(),
            'originalPrice' => $item->price,
            'quantity' => $item->quantity,
            'limitPerUser' => $activityProduct->getLimitPerUser(),
            'remainingStock' => $activityProduct->getRemainingStock(),
        ];
    }

    private function buildNoDiscountResult(CalculateActivityDiscountInput $input): CalculateActivityDiscountResult
    {
        $itemResults = array_map(
            fn (CalculateActivityDiscountItem $item) => new CalculateActivityDiscountItemResult(
                productId: $item->productId,
                skuId: $item->skuId,
                quantity: $item->quantity,
                originalPrice: $item->price,
                originalAmount: $item->getTotalAmount(),
                discountAmount: 0.0,
                finalPrice: $item->price,
                finalAmount: $item->getTotalAmount()
            ),
            $input->items
        );

        return CalculateActivityDiscountResult::success(
            items: $itemResults,
            originalTotalAmount: $input->getTotalAmount(),
            discountTotalAmount: 0.0,
            finalTotalAmount: $input->getTotalAmount()
        );
    }
}
