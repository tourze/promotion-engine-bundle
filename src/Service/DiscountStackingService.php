<?php

namespace PromotionEngineBundle\Service;

use Monolog\Attribute\WithMonologChannel;
use PromotionEngineBundle\DTO\CalculateActivityDiscountInput;
use PromotionEngineBundle\DTO\CalculateActivityDiscountItem;
use PromotionEngineBundle\Entity\TimeLimitActivity;
use PromotionEngineBundle\Enum\ActivityType;
use PromotionEngineBundle\Enum\DiscountType;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[Autoconfigure(public: true)]
#[WithMonologChannel(channel: 'promotion_engine')]
class DiscountStackingService
{
    private const MAX_STACKABLE_ACTIVITIES = 5;
    private const EXCLUSIVE_ACTIVITY_PRIORITY_THRESHOLD = 100;

    public function __construct(
        private readonly ActivityConflictService $activityConflictService,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @param TimeLimitActivity[] $activities
     * @return TimeLimitActivity[]
     */
    public function filterStackableActivities(
        array $activities,
        CalculateActivityDiscountInput $input,
    ): array {
        if (0 === count($activities)) {
            return [];
        }

        $this->logger->debug('开始过滤可叠加活动', [
            'totalActivities' => count($activities),
            'totalItems' => count($input->items),
        ]);

        $sortedActivities = $this->sortActivitiesByPriority($activities);
        $applicableActivities = $this->applyStackingRules($sortedActivities, $input);

        $this->logger->info('活动叠加规则应用完成', [
            'originalCount' => count($activities),
            'applicableCount' => count($applicableActivities),
            'activityIds' => array_map(fn ($a) => $a->getId(), $applicableActivities),
        ]);

        return $applicableActivities;
    }

    /**
     * @param TimeLimitActivity[] $activities
     * @return TimeLimitActivity[]
     */
    private function sortActivitiesByPriority(array $activities): array
    {
        usort($activities, function (TimeLimitActivity $a, TimeLimitActivity $b) {
            if ($a->getPriority() === $b->getPriority()) {
                return $a->getStartTime() <=> $b->getStartTime();
            }

            return $b->getPriority() <=> $a->getPriority();
        });

        return $activities;
    }

    /**
     * @param TimeLimitActivity[] $activities
     * @return TimeLimitActivity[]
     */
    private function applyStackingRules(
        array $activities,
        CalculateActivityDiscountInput $input,
    ): array {
        $applicable = [];
        $exclusiveApplied = false;

        foreach ($activities as $activity) {
            if (count($applicable) >= self::MAX_STACKABLE_ACTIVITIES) {
                $this->logger->debug('达到最大叠加活动数量限制', [
                    'limit' => self::MAX_STACKABLE_ACTIVITIES,
                ]);
                break;
            }

            if ($this->shouldApplyExclusively($activity)) {
                $this->logger->info('应用独占活动', [
                    'activityId' => $activity->getId(),
                    'priority' => $activity->getPriority(),
                ]);
                $applicable = [$activity];
                $exclusiveApplied = true;
                break;
            }

            if ($this->canStackWithExisting($activity, $applicable, $input)) {
                $applicable[] = $activity;
                $this->logger->debug('活动可叠加', [
                    'activityId' => $activity->getId(),
                    'stackedCount' => count($applicable),
                ]);
            } else {
                $this->logger->debug('活动无法叠加', [
                    'activityId' => $activity->getId(),
                    'reason' => '与已有活动冲突',
                ]);
            }
        }

        return $applicable;
    }

    private function shouldApplyExclusively(TimeLimitActivity $activity): bool
    {
        return $activity->isExclusive()
            || $activity->getPriority() >= self::EXCLUSIVE_ACTIVITY_PRIORITY_THRESHOLD;
    }

    /**
     * @param TimeLimitActivity[] $existingActivities
     */
    private function canStackWithExisting(
        TimeLimitActivity $activity,
        array $existingActivities,
        CalculateActivityDiscountInput $input,
    ): bool {
        if (0 === count($existingActivities)) {
            return true;
        }

        foreach ($existingActivities as $existingActivity) {
            if (!$this->areActivitiesCompatible($activity, $existingActivity)) {
                return false;
            }

            if ($this->activityConflictService->hasConflict($activity, [$existingActivity])) {
                return false;
            }
        }

        return $this->validateDiscountStacking($activity, $existingActivities, $input);
    }

    private function areActivitiesCompatible(
        TimeLimitActivity $activity1,
        TimeLimitActivity $activity2,
    ): bool {
        if ($activity1->isExclusive() || $activity2->isExclusive()) {
            return false;
        }

        $incompatibleCombinations = [
            [ActivityType::LIMITED_TIME_SECKILL, ActivityType::LIMITED_QUANTITY_PURCHASE],
            [ActivityType::LIMITED_QUANTITY_PURCHASE, ActivityType::LIMITED_TIME_SECKILL],
        ];

        $type1 = $activity1->getActivityType();
        $type2 = $activity2->getActivityType();

        foreach ($incompatibleCombinations as $combination) {
            if (($type1 === $combination[0] && $type2 === $combination[1])
                || ($type1 === $combination[1] && $type2 === $combination[0])) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param TimeLimitActivity[] $existingActivities
     */
    private function validateDiscountStacking(
        TimeLimitActivity $activity,
        array $existingActivities,
        CalculateActivityDiscountInput $input,
    ): bool {
        $totalEstimatedDiscount = 0.0;
        $totalAmount = $input->getTotalAmount();

        foreach ($existingActivities as $existingActivity) {
            $totalEstimatedDiscount += $this->estimateActivityDiscount($existingActivity, $input);
        }

        $newActivityDiscount = $this->estimateActivityDiscount($activity, $input);
        $combinedDiscount = $totalEstimatedDiscount + $newActivityDiscount;

        if ($combinedDiscount > $totalAmount) {
            $this->logger->warning('优惠叠加超过总金额', [
                'totalAmount' => $totalAmount,
                'combinedDiscount' => $combinedDiscount,
                'newActivityId' => $activity->getId(),
            ]);

            return false;
        }

        $discountRate = ($combinedDiscount / $totalAmount) * 100;
        if ($discountRate > 95.0) {
            $this->logger->warning('优惠叠加折扣率过高', [
                'discountRate' => $discountRate,
                'activityId' => $activity->getId(),
            ]);

            return false;
        }

        return true;
    }

    private function estimateActivityDiscount(
        TimeLimitActivity $activity,
        CalculateActivityDiscountInput $input,
    ): float {
        $estimate = 0.0;
        $activityType = $activity->getActivityType();

        foreach ($input->items as $item) {
            $itemDiscount = match ($activityType) {
                ActivityType::LIMITED_TIME_DISCOUNT => $item->getTotalAmount() * 0.1,
                ActivityType::LIMITED_TIME_SECKILL => $item->getTotalAmount() * 0.2,
                ActivityType::LIMITED_QUANTITY_PURCHASE => $item->getTotalAmount() * 0.15,
            };

            $estimate += $itemDiscount;
        }

        return $estimate;
    }

    /**
     * @param TimeLimitActivity[] $activities
     * @return TimeLimitActivity[]
     */
    public function optimizeActivityCombination(
        array $activities,
        CalculateActivityDiscountInput $input,
    ): array {
        if (count($activities) <= 1) {
            return $activities;
        }

        $bestCombination = [];
        $bestDiscount = 0.0;
        $totalAmount = $input->getTotalAmount();

        $combinations = $this->generateCombinations($activities);

        foreach ($combinations as $combination) {
            $estimatedDiscount = 0.0;
            foreach ($combination as $activity) {
                $estimatedDiscount += $this->estimateActivityDiscount($activity, $input);
            }

            if ($estimatedDiscount > $bestDiscount && $estimatedDiscount <= $totalAmount) {
                $bestDiscount = $estimatedDiscount;
                $bestCombination = $combination;
            }
        }

        $this->logger->info('优化活动组合完成', [
            'originalCount' => count($activities),
            'optimizedCount' => count($bestCombination),
            'estimatedDiscount' => $bestDiscount,
        ]);

        return $bestCombination;
    }

    /**
     * @param TimeLimitActivity[] $activities
     * @return array<TimeLimitActivity[]>
     */
    private function generateCombinations(array $activities): array
    {
        $combinations = [];
        $maxCombinations = min(pow(2, count($activities)), 32);

        for ($i = 1; $i < $maxCombinations; ++$i) {
            $combination = [];
            for ($j = 0; $j < count($activities); ++$j) {
                if (($i & (1 << $j)) !== 0) {
                    $combination[] = $activities[$j];
                }
            }

            if (count($combination) <= self::MAX_STACKABLE_ACTIVITIES) {
                $combinations[] = $combination;
            }
        }

        return $combinations;
    }

    /**
     * @return array<string, float|int>
     */
    public function getStackingLimits(): array
    {
        return [
            'maxStackableActivities' => self::MAX_STACKABLE_ACTIVITIES,
            'exclusivePriorityThreshold' => self::EXCLUSIVE_ACTIVITY_PRIORITY_THRESHOLD,
            'maxDiscountRate' => 95.0,
        ];
    }
}
